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

use think\admin\server\command\Worker;
use think\Service as ThinkService;

/**
 * 模块注册服务
 * Class Service
 * @package think\admin\server
 */
class Service extends ThinkService
{
    public function register()
    {
        $this->commands(['xadmin:server' => Worker::class]);
    }
}