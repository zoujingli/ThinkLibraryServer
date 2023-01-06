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

namespace think\admin\server\command;

use think\admin\server\HttpServer;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * Worker 命令行类
 * Class Worker
 * @package think\admin\server\command
 */
class Worker extends Command
{
    protected $config = [];

    public function configure()
    {
        $this->setName('xadmin:server')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of workerman server.')
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of workerman server.')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
            ->setDescription('Workerman HTTP Server for ThinkAdmin');
    }

    public function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        if (DIRECTORY_SEPARATOR !== '\\') {
            if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
                $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
                return false;
            }
            global $argv;
            [array_shift($argv), array_shift($argv), array_unshift($argv, 'think', $action)];
        } elseif ('start' != $action) {
            $output->writeln("<error>Not Support action:{$action} on Windows.</error>");
            return false;
        }
        if ('start' == $action) {
            $output->writeln('Starting Workerman http server...');
        }
        $this->config = $this->app->config->get('server', []);
        if (isset($this->config['context'])) {
            $context = $this->config['context'];
            unset($this->config['context']);
        } else {
            $context = [];
        }
        [$host, $port] = [$this->getHost(), $this->getPort()];
        $worker = new HttpServer($host, $port, $context);
        if (empty($this->config['pidFile'])) {
            $this->config['pidFile'] = $this->app->getRootPath() . 'runtime/worker.pid';
        }
        // 避免pid混乱
        $this->config['pidFile'] .= '_' . $port;
        // 设置应用根目录
        $worker->setRootPath($this->app->getRootPath());
        // 应用设置
        if (!empty($this->config['app_init'])) {
            $worker->appInit($this->config['app_init']);
            unset($this->config['app_init']);
        }
        // 开启守护进程模式
        if ($this->input->hasOption('daemon')) {
            $worker->setStaticOption('daemonize', true);
        }
        // 开启HTTPS访问
        if (!empty($this->config['ssl'])) {
            $this->config['transport'] = 'ssl';
            unset($this->config['ssl']);
        }
        // 设置网站目录
        if (empty($this->config['root'])) {
            $this->config['root'] = $this->app->getRootPath() . 'public';
        }
        $worker->setRoot($this->config['root']);
        $worker->setStaticOption('logFile', $this->app->getRuntimePath() . 'server.log');
        unset($this->config['root']);
        // 设置文件监控
        if (DIRECTORY_SEPARATOR !== '\\' && ($this->app->isDebug() || !empty($this->config['file_monitor']))) {
            $interval = $this->config['file_monitor_interval'] ?? 2;
            $paths = !empty($this->config['file_monitor_path']) ? $this->config['file_monitor_path'] : [$this->app->getAppPath(), $this->app->getConfigPath()];
            $worker->setMonitor($interval, $paths);
            unset($this->config['file_monitor'], $this->config['file_monitor_interval'], $this->config['file_monitor_path']);
        }
        // 全局静态属性设置
        foreach ($this->config as $name => $val) {
            if (in_array($name, ['stdoutFile', 'daemonize', 'pidFile', 'logFile'])) {
                $worker->setStaticOption($name, $val);
                unset($this->config[$name]);
            }
        }
        // 设置服务器参数
        $worker->option($this->config);
        if (DIRECTORY_SEPARATOR == '\\') {
            $output->writeln('You can exit with <info>`CTRL-C`</info>');
        }
        $worker->start();
    }

    /**
     * 获取 HttpServer 主机
     * @param string $default
     * @return string
     */
    protected function getHost(string $default = '0.0.0.0'): string
    {
        if ($this->input->hasOption('host')) {
            return $this->input->getOption('host');
        } else {
            return empty($this->config['host']) ? $default : $this->config['host'];
        }
    }

    /**
     * 获取 HttpServer 端口
     * @param string $default
     * @return string
     */
    protected function getPort(string $default = '80'): string
    {
        if ($this->input->hasOption('port')) {
            return $this->input->getOption('port');
        } else {
            return empty($this->config['port']) ? $default : $this->config['port'];
        }
    }
}
