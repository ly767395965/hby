<?php
return array(

    'VIEW_PATH'=>'./Themes/App/',
    'WapPayConfig' => array (
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'partner'		=> '2088421352218578',

//收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
        'seller_id'	=> '2088421352218578',

// MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'key'			=> 'rs5ohe566j8mitfw0ap5665u0wp7p2es',
// 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        'notify_url' => "http://www.hbzc777.com/Home/Pay/notifyUrl",

// 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
        'return_url' => "http://www.hbzc777.com/Home/Pay/returnWapUrl",
//        'return_url' => "http://m.baidu.com",

        //这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
        'seller_email'=>'837927777@qq.com',


//签名方式
        'sign_type'    => strtoupper('MD5'),

//字符编码格式 目前支持utf-8
        'input_charset'=> strtolower('utf-8'),

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
        'cacert'   => getcwd().'\\cacert.pem',

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        'transport'    => 'http',

// 支付类型 ，无需修改
        'payment_type' => "1",

// 产品类型，无需修改
       'service' => "alipay.wap.create.direct.pay.by.user",

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
    ),



);
