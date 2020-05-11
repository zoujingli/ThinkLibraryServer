<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
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
        } catch (HttpException | \Exception | \Throwable $exception) {
            $this->exception($connection, $exception);
        }
    }

    /**
     * 是否运行在命令行下
     * @return boolean
     */
    public function runningInConsole()
    {
        return false;
    }

    /**
     * 输出HTTP状态码
     * @param integer $code
     */
    protected function httpResponseCode($code = 200)
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
