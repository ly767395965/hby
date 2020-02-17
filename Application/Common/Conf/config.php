<?php
return array(
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '192.168.31.244', // 服务器地址
    'DB_NAME'               =>  'netcardb',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  '',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    //'DB_PREFIX'             =>  'hby_',    // 数据库表前缀
    'DB_CHARSET'            =>  'utf8',      // 数据库编码
    'DB_DEBUG'  =>  false, // 数据库调试模式 开启后可以记录SQL日志
    'DB_SQLSERVER' => array(
        'db_type'  => 'sqlsrv',
        'db_user'  => 'sa',
        'db_pwd'   => 'root',
        'db_host'  => 'localhost',
        //'db_port'  => '1433',
        'db_name'  => 'test',
        'db_charset' => 'GB2312',
    ),

    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误


//	'SHOW_PAGE_TRACE' =>true,
    /* 数据缓存设置 */
    'DATA_CACHE_COMPRESS'   => false,   // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK' => false,   // 数据缓存是否校验缓存
    'DEFAULT_FILTER'        =>  'strip_tags,stripslashes',//全局的过滤方法
    'TMPL_CACHE_ON'=>false,      // 默认开启模板缓存
    'DB_FIELD_CACHE'=>false,
    'HTML_CACHE_ON'=>false,
    /* 错误设置 */
    'ERROR_MESSAGE' => '您浏览的页面暂时发生了错误！请稍后再试～',//错误显示信息,非调试模式有效
    'ERROR_PAGE' =>'/Themes/Home/404.html',// 错误定向页面
    /* 静态缓存设置 */
    'HTML_CACHE_ON'   => false,   // 默认关闭静态缓存
    'HTML_CACHE_TIME' => 60,      // 静态缓存有效期


    /* 语言设置 */
    'LANG_SWITCH_ON'        => false,   // 默认关闭多语言包功能
    'LANG_AUTO_DETECT'      => false,   // 自动侦测语言 开启多语言功能后有效

    /* SESSION设置 */
    'SESSION_AUTO_START'    => true,    // 是否自动开启Session
    'DEFAULT_JSONP_HANDLER' =>  'myJsonpReturn', // 默认JSONP格式返回的处理方法

    'AUTH_CONFIG' => array(
        'AUTH_ON' => true, //认证开关
        'AUTH_TYPE' => 1, // 认证方式，1为时时认证；2为登录认证。
        'AUTH_GROUP' => 'auth_group', //用户组数据表名
        'AUTH_GROUP_ACCESS' => 'auth_group_access', //用户组明细表
        'AUTH_RULE' => 'auth_rule', //权限规则表
        'AUTH_USER' => 'admin_user' //用户信息表
    ),

    /*COOKIE信息安全设置*/
    'COOKIE_HTTPONLY' => true,  //开启cookie的httponly的设置

//    'DB_PARAMS' => array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),

    /*网约车公共配置信息(自定义常量)*/
    'NETCAR_SUBMIT_URL' => '127.0.0.1:9191',//报送地址
    'NETCAR_SUBMIT_SOURCE' => 0,//消息来源标识

    'NETCAR_LAN_URL' => 'http://169.254.181.250:80/Index.php',//局域网服务器php入口文件
    'NETCAR_FTP_ON' => true,//网约车上传文件至部级平台ftp开关


);