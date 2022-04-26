<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2022 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace think\admin\server;

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
        $this->commands([
            'xadmin:server' => '\\think\\admin\\server\\command\\Worker',
        ]);
    }
}