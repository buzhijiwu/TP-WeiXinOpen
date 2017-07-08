<?php
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式
define('APP_DEBUG',True);

// 定义应用目录
define('APP_PATH','./Application/');

// 定义公共模块目录
define('COMMON_PATH','./Common/');

// 定义运行时目录
define('RUNTIME_PATH', './Runtime/');

// 引入ThinkPHP入口文件
require "./System/ThinkPHP/ThinkPHP.php";