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
use think\App;
use think\exception\Handle;
use think\exception\HttpException;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Response as WorkerResponse;
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
    public $worker;

    /**
     * 返回当前请求结果
     * @param TcpConnection $connection
     * @param WorkerResponse $wResponse
     */
    public function workerResponse(TcpConnection $connection, WorkerResponse $wResponse)
    {
        try {
            $this->db->clearQueryTimes();
            Cookie::$response = $wResponse;
            [$this->beginTime, $this->beginMem] = [microtime(true), memory_get_usage()];
            $pathinfo = ltrim(strpos($_SERVER['REQUEST_URI'], '?') ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'], '/');
            $this->request->setPathinfo($pathinfo)->withInput($GLOBALS['HTTP_RAW_POST_DATA']);
            while (ob_get_level() > 1) ob_end_clean();
            ob_start();
            $tResponse = $this->http->run();
            $content = ob_get_clean();
            ob_start();
            $tResponse->send();
            $this->http->end($tResponse);
            $content .= ob_get_clean() ?: '';
            $wResponse->withStatus($tResponse->getCode());
            foreach ($tResponse->getHeader() as $name => $value) {
                $wResponse->header($name, $value);
            }
            $connection->send($wResponse->withBody($content));
            if (strtolower($_SERVER['HTTP_CONNECTION']) !== "keep-alive") {
                $connection->close();
            }
        } catch (HttpException | \Exception | \Throwable $exception) {
            if ($exception instanceof \Exception) {
                $handler = $this->make(Handle::class);
                $handler->report($exception);
                $response = $handler->render($this->request, $exception);
                $connection->send($wResponse->withStatus($response->getCode)->withBody($response->getContent()));
            } else {
                $connection->send($wResponse->withStatus(500)->withBody($exception->getMessage()));
            }
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
}
