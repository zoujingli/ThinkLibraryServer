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

namespace think\admin\server\bindmap;

use think\App;
use think\Request as ThinkRequest;

/**
 * WorkermanRequest 请求对象
 * Class Request
 * @package think\admin\server\bindmap
 */
class Request extends ThinkRequest
{
    /**
     * 获取当前执行的文件 SCRIPT_NAME
     * @access public
     * @param bool $complete 是否包含完整域名
     * @return string
     */
    public function baseFile(bool $complete = false): string
    {
        if (empty($this->baseFile) && isset($_SERVER['SCRIPT_FILENAME']) && isset($_SERVER['PHP_SELF'])) {
            $script_name = basename($this->server('SCRIPT_FILENAME'));
            if (basename($this->server('SCRIPT_NAME')) === $script_name) {
                $url = $this->server('SCRIPT_NAME');
            } elseif (basename($this->server('PHP_SELF')) === $script_name) {
                $url = $this->server('PHP_SELF');
            } elseif (basename($this->server('ORIG_SCRIPT_NAME')) === $script_name) {
                $url = $this->server('ORIG_SCRIPT_NAME');
            } elseif (($pos = strpos($this->server('PHP_SELF'), '/' . $script_name)) !== false) {
                $url = substr($this->server('SCRIPT_NAME'), 0, $pos) . '/' . $script_name;
            } elseif ($this->server('DOCUMENT_ROOT') && strpos($this->server('SCRIPT_FILENAME'), $this->server('DOCUMENT_ROOT')) === 0) {
                $url = str_replace('\\', '/', str_replace($this->server('DOCUMENT_ROOT'), '', $this->server('SCRIPT_FILENAME')));
            }
            $this->baseFile = $url ?? '';
        }
        return $complete ? $this->domain() . $this->baseFile : $this->baseFile;
    }

    public static function __make(App $app)
    {
        $request = parent::__make($app);
        [$request->file, $tmpfile] = [[], tempnam('', 'file_')];
        if (count($_FILES) > 0) foreach ($_FILES as $item) {
            file_put_contents($tmpfile, $item['file_data']);
            $request->file[$item['name']] = [
                'name' => $item['file_name'], 'type' => $item['file_type'],
                'size' => $item['file_size'], 'error' => UPLOAD_ERR_OK, 'tmp_name' => $tmpfile,
            ];
        }
        return $request;
    }
}