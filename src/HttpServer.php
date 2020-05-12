<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace think\admin\server;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\admin\server\bindmap\Cookie;
use think\admin\server\bindmap\Request;
use think\admin\server\bindmap\Think;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Protocols\Http as WorkerHttp;
use Workerman\Worker;

/**
 * HttpServer 命令行服务类
 * Class HttpServer
 * @package think\admin\server
 */
class HttpServer extends Server
{
    protected $app;
    protected $root;
    protected $rootPath;
    protected $appInit;
    protected $monitor;
    protected $lastMtime;
    protected static $mimeTypeMap = [];

    /**
     * HttpServer constructor.
     * @param string $host 监听地址
     * @param integer $port 监听端口
     * @param array $context 参数
     */
    public function __construct($host, $port, $context = [])
    {
        $this->worker = new Worker('http://' . $host . ':' . $port, $context);
        $this->worker->name = 'HttpServer';
        // 设置回调
        foreach ($this->event as $event) if (method_exists($this, $event)) $this->worker->$event = [$this, $event];
    }

    public function setRootPath($path)
    {
        $this->rootPath = $path;
    }

    public function appInit(\Closure $closure)
    {
        $this->appInit = $closure;
    }

    public function setRoot($path)
    {
        $this->root = $path;
    }

    public function setStaticOption($name, $value)
    {
        Worker::${$name} = $value;
    }

    public function setMonitor($interval = 2, $path = [])
    {
        $this->monitor['path'] = (array)$path;
        $this->monitor['interval'] = $interval;
    }

    /**
     * 设置参数
     * @param array $option 参数
     */
    public function option(array $option)
    {
        if (!empty($option)) foreach ($option as $key => $val) {
            $this->worker->$key = $val;
        }
    }

    /**
     * onWorkerStart 事件回调
     * @param Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {
        $this->initMimeTypeMap();
        $this->app = new HttpApp($this->rootPath);
        if ($this->appInit) call_user_func_array($this->appInit, [$this->app]);
        $this->app->initialize();
        $this->lastMtime = time();
        $this->app->worker = $worker;
        $this->app->bind([
            'think\Cookie'  => Cookie::class,
            'think\Request' => Request::class,
        ]);
        class_alias(Think::class, 'think\view\driver\Think');
        if (0 == $worker->id && $this->monitor) {
            $paths = $this->monitor['path'];
            Timer::add($this->monitor['interval'] ?: 2, function () use ($paths) {
                foreach ($paths as $path) foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') if ($this->lastMtime < $file->getMTime()) {
                        echo '[update]' . $file . "\n";
                        posix_kill(posix_getppid(), SIGUSR1);
                        $this->lastMtime = $file->getMTime();
                        return;
                    }
                }
            });
        }
    }

    /**
     * onMessage 事件回调
     * @param TcpConnection $connection
     * @param mixed $data
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SERVER_SOFTWARE'] = 'HttpServer';
        $_SERVER['SCRIPT_FILENAME'] = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'index.php';
        $file = $this->root . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
        if (!is_file($file)) {
            $this->app->worker($connection, $data);
        } else {
            $this->sendFile($connection, $file);
        }
    }

    /**
     * 访问资源文件
     * @param TcpConnection $connection
     * @param string $file 文件名
     * @return string
     */
    protected function sendFile(TcpConnection $connection, $file)
    {
        $info = stat($file);
        $modifiyTime = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' ' . date_default_timezone_get() : '';
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info) {
            // Http 304.
            if ($modifiyTime === $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
                // 304
                WorkerHttp::header('HTTP/1.1 304 Not Modified');
                // Send nothing but http headers..
                return $connection->close('');
            }
        }
        $mimeType = $this->getMimeType($file);
        WorkerHttp::header('HTTP/1.1 200 OK');
        WorkerHttp::header('Connection: keep-alive');
        if ($mimeType) {
            WorkerHttp::header('Content-Type: ' . $mimeType);
        } else {
            WorkerHttp::header('Content-Type: application/octet-stream');
            $fileinfo = pathinfo($file);
            $filename = $fileinfo['filename'] ?? '';
            WorkerHttp::header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        if ($modifiyTime) {
            WorkerHttp::header('Last-Modified: ' . $modifiyTime);
        }
        WorkerHttp::header('Content-Length: ' . filesize($file));
        ob_start();
        readfile($file);
        $content = ob_get_clean();
        return $connection->send($content);
    }

    /**
     * 获取文件类型信息
     * @param string $filename 文件名
     * @return string
     */
    protected function getMimeType(string $filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION) ?? '';
        if (isset(self::$mimeTypeMap[$extension])) {
            return self::$mimeTypeMap[$extension];
        } else {
            return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filename);
        }
    }

    /**
     * Init mime map.
     */
    protected function initMimeTypeMap()
    {
        $mimeFile = WorkerHttp::getMimeTypesFile();
        if (!is_file($mimeFile)) {
            Worker::log("{$mimeFile} mime.type file not fond");
            return;
        }
        $items = file($mimeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($items)) {
            Worker::log("get {$mimeFile} mime.type content fail");
            return;
        }
        foreach ($items as $content) if (preg_match("/\s*(\S+)\s+(\S.+)/", $content, $match)) {
            [$mimetype, $workermanFileExtensionVar] = [$match[1], $match[2]];
            $workermanFileExtensionArr = explode(' ', substr($workermanFileExtensionVar, 0, -1));
            foreach ($workermanFileExtensionArr as $workermanFileExtension) {
                self::$mimeTypeMap[$workermanFileExtension] = $mimetype;
            }
        }
    }

    /**
     * 启动 HttpServer
     */
    public function start()
    {
        Worker::runAll();
    }

    /**
     * 停止 HttpServer
     */
    public function stop()
    {
        Worker::stopAll();
    }
}
