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

namespace think\admin\server\bindmap;

use think\Cookie as ThinkCookie;
use Workerman\Protocols\Http as WorkerHttp;

/**
 * WorkermanCookie 类
 * Class Cookie
 * @package think\admin\server\bindmap
 */
class Cookie extends ThinkCookie
{
    /**
     * 保存Cookie
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param integer $expire cookie过期时间
     * @param string $path 有效的服务器路径
     * @param string $domain 有效域名/子域名
     * @param boolean $secure 是否仅仅通过HTTPS
     * @param boolean $httponly 仅可通过HTTP访问
     * @param string $samesite 防止CSRF攻击和用户追踪
     * @return void
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly, string $samesite): void
    {
        WorkerHttp::setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}
