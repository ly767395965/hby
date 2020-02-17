<?php
return array(
	'VIEW_PATH'=>'./Themes/Admin/',



//
	//静态页面的缓存 （默认保存早在html/文件夹中）
	'HTML_CACHE_ON'=>true,

	'HTML_CACHE_RULES'=> array(
		/**
		控制器名/方法名=array('缓存文件的名称'，'静态缓存有效期'，'附加规则')
		 */
		'Show:Index'=>array('{:module}_{:action}_{id}',1000),
	),

	/**
	 * 路由配置
	 */
	//设置可访问模块
//	'MODULE_ALLOW_LIST'    =>    array('Admin'),
//	'DEFAULT_MODULE'       =>    'Admin',  // 默认模块
//	'DEFAULT_CONTROLLER'    =>  'Login', // 默认控制器名称
//	'DEFAULT_ACTION'        =>  'Index', // 默认操作名称
//	// 开启路由
//	'URL_ROUTER_ON'   => true,
//	//配置路由规则
//	'URL_ROUTE_RULES'=>array(
//		'Admin/Index/Index' => 'Index/Index', // 静态地址路由
//
//	),
//	//静态路由
//	'URL_MAP_RULES'=>array(
//		'Index/Index' => 'Admin/Index/Index',//控制器+方法
//	),

	//url可以不区分大小写
	'URL_CASE_INSENSITIVE' =>true,
	//设置伪静态后缀
	'URL_HTML_SUFFIX'=>'htm',
);
