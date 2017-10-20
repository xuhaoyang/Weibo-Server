<?php
return array(

	 /* 数据库设置 */
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    'DB_NAME'               =>  'weibo',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  '19940531x',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'hd_',    // 数据库表前缀
    'DB_CHARSET'            =>  'utf8',      // 数据库编码默认采用utf8

    'URL_HTML_SUFFIX'=>'',                  //设置伪静态后缀为空

    //'SHOW_PAGE_TRACE' =>true,               //开启Trace
    'ENCTYPTION_KEY'        =>  'sic.szpt.edu.cn',  //用于异位或加密的KEY
    'AUTO_LOGIN_TIME'       =>  time() + 3600 * 24* 7 , //一个星期cookies

    //图片上传
    'UPLOAD_MAX_SIZE'       =>  2000000,        //最大上传大小
    'UPLOAD_PATH'           =>  './Uploads/',   //文件上传保存路径
    'UPLOAD_EXTS'           =>  array('jpg','jpeg','gif','png'),    //允许上传文件的后缀

    //缓存设置
    'DATA_CACHE_SUBDIR'     =>true,             //以哈希形式生成缓存目录
    'DATA_PATH_LEVEL'       =>2,                //目录层次

    //加载扩展配置
    'LOAD_EXT_CONFIG'       =>'system',

);
?>