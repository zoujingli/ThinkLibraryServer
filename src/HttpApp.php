<?php

// +----------------------------------------------------------------------
// | Workerman HttpServer for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2022 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站:https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免费声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | github 代码仓库：https://github.com/zoujingli/ThinkLibraryServer
// +----------------------------------------------------------------------

namespace think\admin\server;

use think\App;
use think\exception\Handle;
use think\exception\HttpException;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;
use Workerman\Worker;

/**
 * Worker 应用对象
 * Class HttpApp
 * @package think\admin\server
 */
class HttpApp extends App
{
    /**
     * @var Worker
     */
    protected $worker;

    /**
     * 处理 Worker 请求
     * @param TcpConnection $connection
     * @param mixed $data
     */
    public function worker(TcpConnection $connection, $data = [])
    {
        try {
            $pathinfo = ltrim(strpos($_SERVER['REQUEST_URI'], '?') ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'], '/');
            [$this->beginTime, $this->beginMem] = [microtime(true), memory_get_usage(), $this->db->clearQueryTimes()];
            $this->request->setPathinfo($pathinfo)->withInput($GLOBALS['HTTP_RAW_POST_DATA']);
            while (ob_get_level() > 1) ob_end_clean();
            ob_start();
            $response = $this->http->run();
            $content = ob_get_clean();
            ob_start();
            $response->send();
            $this->http->end($response);
            $content .= ob_get_clean() ?: '';
            $this->httpResponseCode($response->getCode());
            foreach ($response->getHeader() as $name => $val) {
                Http::header($name . (!is_null($val) ? ':' . $val : ''));
            }
            if (strtolower($_SERVER['HTTP_CONNECTION']) === "keep-alive") {
                $connection->send($content);
            } else {
                $connection->close($content);
            }
        } catch (HttpException|\Exception|\Throwable $exception) {
            $this->exception($connection, $exception);
        }
    }

    /**
     * 是否运行在命令行下
     * @return boolean
     */
    public function runningInConsole(): bool
    {
        return false;
    }

    /**
     * 输出HTTP状态码
     * @param integer $code
     */
    protected function httpResponseCode(int $code = 200)
    {
        Http::responseCode($code);
    }

    protected function exception($connection, $exception)
    {
        if ($exception instanceof \Exception) {
            $handler = $this->make(Handle::class);
            $handler->report($exception);
            $resp = $handler->render($this->request, $exception);
            $content = $resp->getContent();
            $code = $resp->getCode();
            $this->httpResponseCode($code);
            $connection->send($content);
        } else {
            $this->httpResponseCode(500);
            $connection->send($exception->getMessage());
        }
    }

}
