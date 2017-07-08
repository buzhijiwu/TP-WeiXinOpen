<?php
return array(
    //数据库配置
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '10.0.0.201', // 服务器地址
    'DB_NAME'               =>  'weixin_open',  // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'root',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'wx_',    // 数据库表前缀

    //系统配置
    'APP_GROUP_LIST' => 'Home,Admin', // 分组
    'DEFAULT_GROUP' => 'Home', // 默认分组
    'URL_MODEL' => 3, // URL兼容模式
    'URL_CASE_INSENSITIVE' => true, // URL是否不区分大小写 默认区分大小写
    'URL_HTML_SUFFIX'       =>  '',  // URL伪静态后缀设置,例如：".html"
//    'CONTROLLER_LEVEL'      =>  2, //设置2级目录的控制器层

    //Redis缓存配置
    'TP_REDIS_HOST'   =>  '127.0.0.1', //服务器IP
    'TP_REDIS_PORT'   =>  '6379',     //端口
    'TP_REDIS_AUTH'   =>  'redisAuthRoot',    //Redis auth认证(密钥)

    //微往联盟支付配置信息
    'appid_weiwanglianmeng'    =>  'wx59437eafc22a2410',
    'appsecret_weiwanglianmeng' =>  'b7dc111b8dc48b50913c325de0f6fbb4',
    'mchid_weiwanglianmeng' =>  '1311059201',    //商户号
    'partnerkey_weiwanglianmeng' =>  'U8rnd763laWe2Mnv64c064sdbrx3k1mf',    //支付的key

    //微往联盟开放平台配置信息
    'appid_weiwanglianmeng_open'    =>  'wx0fd54b44bfecb374',
    'appsecret_weiwanglianmeng_open' =>  'afb164d5139b70f14677047a47d5bb63',
);