<?php
return array(
	
	'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    'DB_NAME'               =>  'weibo',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'root',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'hd_',    // 数据库表前缀

     //URL静态路由配置
    'URL_ROUTER_ON'         => true,              //开启路由功能
    'URL_ROUTE_RULES'       =>array(//定义路由规则
        'User/:id\d'        =>'User/index',
        'UserAtme/:username'=>'User/index',
        'follow/:uid\d'     =>array('User/followList','type=1'),
        'fans/:uid\d'       =>array('User/followList','type=0'),
    )
);