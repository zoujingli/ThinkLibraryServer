# ThinkLibraryServer

#### 介绍

ThinkAdmin Workerman HttpServer

基于 Workerman 实现 HttpServer 访问 ThinkAdmin

#### 安装

`composer require zoujingli/think-library-server dev-master`

#### 运行

`php think xadmin:server --host 127.0.0.1 --port 8080`

* 可选参数`port`可以指定监听端口，默认为`80`端口
* 可选参数`host`可以指定监听主机，默认为`0.0.0.0`

#### 鸣谢

* `topthink/framework`
* `topthink/think-view`
* `topthink/think-worker`
* `workerman/workerman`
