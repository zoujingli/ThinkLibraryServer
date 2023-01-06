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

namespace think\admin\server\support;

use think\Cookie as ThinkCookie;
use Workerman\Protocols\Http as WorkerHttp;

/**
 * WorkermanCookie 类
 * Class Cookie
 * @package think\admin\server\support
 */
class Cookie extends ThinkCookie
{
    /**
     * 保存 Cookie
     * @param string $name cookie 名称
     * @param string $value cookie 值
     * @param integer $expire cookie 过期时间
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
