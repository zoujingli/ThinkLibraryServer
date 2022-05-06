<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2022 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站:https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace think\admin\server;

use Workerman\Worker;

/**
 * Worker 控制器扩展类
 * Class Server
 * @package think\admin\server
 */
abstract class Server
{
    protected $worker;
    protected $socket = '';
    protected $protocol = 'http';
    protected $host = '0.0.0.0';
    protected $port = '2346';
    protected $option = [];
    protected $context = [];
    protected $event = ['onWorkerStart', 'onConnect', 'onMessage', 'onClose', 'onError', 'onBufferFull', 'onBufferDrain', 'onWorkerReload', 'onWebSocketConnect'];

    /**
     * Server constructor.
     */
    public function __construct()
    {
        // 实例化 Websocket 服务
        $this->worker = new Worker($this->socket ?: $this->protocol . '://' . $this->host . ':' . $this->port, $this->context);
        // 设置参数
        if (!empty($this->option)) foreach ($this->option as $key => $value) $this->worker->$key = $value;
        // 设置回调
        foreach ($this->event as $event) if (method_exists($this, $event)) $this->worker->$event = [$this, $event];
        // 初始化
        $this->init();
    }

    protected function init()
    {
    }

    public function start()
    {
        Worker::runAll();
    }

    public function __set($name, $value)
    {
        $this->worker->$name = $value;
    }

    public function __call($method, $args)
    {
        call_user_func_array([$this->worker, $method], $args);
    }
}
