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

use think\admin\server\bindmap\Cookie;
use think\admin\server\bindmap\Request;
use think\admin\server\bindmap\Think;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;
use Workerman\Worker;

/**
 * HttpServer 命令行服务类
 * Class HttpServer
 * @package think\admin\server
 */
class HttpServer extends Server
{
    /**
     * @var HttpApp
     */
    protected $app;

    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var string
     */
    protected $appInit;

    /**
     * @var string
     */
    protected $lastMtime;

    /**
     * HttpServer constructor.
     * @param string $host 监听地址
     * @param integer $port 监听端口
     * @param array $context 监听参数
     */
    public function __construct($host, $port, $context = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->protocol = 'http';
        $this->context = $context;
        parent::__construct();
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

    /**
     * 设置参数
     * @param array $option 参数
     */
    public function option(array $option)
    {
        if (!empty($option)) foreach ($option as $name => $value) {
            $this->worker->$name = $value;
        }
    }

    /**
     * onWorkerStart 事件回调
     * @param Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {
        $this->app = new HttpApp($this->rootPath);
        if ($this->appInit) call_user_func_array($this->appInit, [$this->app]);
        $this->app->initialize();
        [$this->lastMtime, $this->app->worker] = [time(), $worker];
        class_alias(Think::class, 'think\view\driver\Think');
        $this->app->bind(['think\Cookie' => Cookie::class, 'think\Request' => Request::class]);
    }

    /**
     * onMessage 事件回调
     * @param TcpConnection $connection
     * @param WorkerRequest $request
     */
    public function onMessage(TcpConnection $connection, WorkerRequest $request)
    {
        $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SERVER_SOFTWARE'] = 'HttpServer';
        $_SERVER['SCRIPT_FILENAME'] = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'index.php';
        $response = new WorkerResponse();
        $this->app->cookie = $this->app->make(Cookie::class, [], true)->setResponse($response);
        if (is_file($file = $this->root . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'))) {
            if (!empty($ifModifiedSince = $request->header('if-modified-since'))) {
                $modifiedTime = date('D, d M Y H:i:s', filemtime($file)) . ' ' . date_default_timezone_get();
                if ($modifiedTime === $ifModifiedSince) {
                    $connection->send($response->withStatus(304));
                    return;
                }
            }
            $connection->send($response->withFile($file));
        } else {
            $this->app->workerResponse($connection, $response);
        }
        unset($this->app->cookie);
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
