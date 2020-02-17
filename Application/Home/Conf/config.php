<?php
return array(
    'VIEW_PATH'=>'./Themes/Home/',


    'URL_ROUTER_ON'   => true, //开启路由
    'URL_ROUTE_RULES' => array( //定义路由规则

    ),

    //静态页面的缓存 （默认保存早在html/文件夹中）
    'HTML_CACHE_ON'=>true,

    'HTML_CACHE_RULES'=> array(
        /**
        控制器名/方法名=array('缓存文件的名称'，'静态缓存有效期'，'附加规则')
         */
        'Show:Index'=>array('{:module}_{:action}_{id}',1000),
    ),
    'SHOW_PAGE_TRACE' =>false, //调试模式
    /**
     * 路由配置
     */

    // 开启路由
    'URL_ROUTER_ON'   => true,
    //配置路由规则
    'URL_ROUTE_RULES'=>array(
        'Index' => 'Index.php/Home/Index/Index', // 静态地址路由

    ),
    //静态路由
    'URL_MAP_RULES'=>array(
        'U' => 'Index.php/Home/Index/Index',//控制器+方法
    ),

    //url可以不区分大小写
    'URL_CASE_INSENSITIVE' =>true,
    //设置伪静态后缀
    'URL_HTML_SUFFIX'=>'htm',
    'URL_MODEL' => 2,

    //支付宝配置参数（订单）
    'alipay_config'=>array(
        //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'partner' =>'2088721439066714',//合作伙伴身份（PID）：2088421352218578
        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
        'seller_id' => '2088721439066714',
        // MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'key'=>'4y1juekh0g2v50rivplylvhgkonvcfjf',//新华邦MD5秘钥：rs5ohe566j8mitfw0ap5665u0wp7p2es

        //签名方式
        'sign_type'=>strtoupper('MD5'),
        //字符编码格式 目前支持 gbk 或 utf-8
        'input_charset'=> strtolower('utf-8'),
        //请保证cacert.pem文件在当前文件夹目录中
        'cacert'=> getcwd().'\\cacert.pem',
        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        'transport'=> 'http',
        // 产品类型，无需修改
        'service' => 'create_direct_pay_by_user',

        //这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
        'seller_email'=>'837927777@qq.com',
        //这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
        'notify_url'=>'http://www.hbzc777.com/Home/Pay/notifyUrl',
        //这里是同步通知页面url，提交到项目的Pay控制器的returnurl方法；
        'return_url'=>'http://www.hbzc777.com/Home/Pay/returnUrl',

        //支付成功跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参payed（已支付列表）
        'successPage'=>'http://www.hbzc777.com/Home/UserManage/Index',
        //支付失败跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参unpay（未支付列表）
        'errorPage'=>'http://www.hbzc777.com/UserManage/myOrder?orderType=payFail',
//		'errorPage'=>'http://100.64.129.69/UserManage/myOrder?orderType=payFail',
    ),
    //支付宝配置参数（充值）
    'recharge_alipay_config'=>array(
        //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'partner' =>'2088421352218578',
        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
        'seller_id' => '2088421352218578',
        // MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
        'key'=>'rs5ohe566j8mitfw0ap5665u0wp7p2es',
        //签名方式
        'sign_type'=>strtoupper('MD5'),
        //字符编码格式 目前支持 gbk 或 utf-8
        'input_charset'=> strtolower('utf-8'),
        //请保证cacert.pem文件在当前文件夹目录中
        'cacert'=> getcwd().'\\cacert.pem',
        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        'transport'=> 'http',
        // 产品类型，无需修改
        'service' => 'create_direct_pay_by_user',

        //这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
        'seller_email'=>'837927777@qq.com',
        //这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
        'notify_url'=>'http://www.hbzc777.com/Home/UserManage/notifyUrl',
        //这里是同步通知页面url，提交到项目的Pay控制器的returnurl方法；
        'return_url'=>'http://www.hbzc777.com/Home/UserManage/returnUrl',

        //支付成功跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参payed（已支付列表）
        'successPage'=>'http://www.hbzc777.com/Home/UserManage/recharge',
        //支付失败跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参unpay（未支付列表）
        'errorPage'=>'http://www.hbzc777.com/UserManage/myOrder?orderType=payFail',
//		'errorPage'=>'http://100.64.129.69/UserManage/myOrder?orderType=payFail',
    ),

    'alipay_mobile' => array(
        //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
        'partner' => '2088421352218578',

//商户的私钥,此处填写原始私钥去头去尾，RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
        'private_key'	=> 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALrpaftDFYhWceb1
MLx5YiUEjy9HhmgkG9nfgh2ysEfJqiSjhspuod9fHG4W1N0UVUr368pKgbEKteNJ
ifwfyOhV1X37bzgDA5slcL2muIVDaUIodF2QL0JPkbLpB0xYC4Rtt1vPbTnFzGs6
WDMojxUr5advRYNJdMHZeUpXC1E9AgMBAAECgYAKHnDaZXtY8jUgZ83Hplql3mVS
DfE82heX5/3HVdEtUcGgUioN84dX7HJBk4LapSso79sYDIiQ6R+Huod52s6pjY9L
4mqu3lWeVjthTko9juLUYUkSmVWMAbV5+QDe0I5iDaXBGUGYAPWVF/Fjjy/w08WJ
5D55U+2SfhaRLOHGkQJBAPFhEzBGUd6vUtkn4oNpcdj5ph8XkWWZdxHtavXAk8MO
ySgY1LbTC04PFOLbJXud2Ja1dZPSlkOa3bf2SvEly0MCQQDGO76oDWNToG2KNmsJ
E+vLYc8c4iUsmEtwVKWSGNg5/NT00kesLZJurZqiD2qRRYK8jr1q3QWrmDOGKUrE
D2l/AkB6yYDzW7Il71XbtZhadPc/Aq/ovRpvboPNkNKKNO51mT3mscrzPaRQjwd2
5zfIDGGzOJeZHNTniw4imJ1C0pD1AkBX30X9gqnD+Tp1aPf7dywv5LSFji2CXiQa
sDsQzxiSY+QWflwtE6p0i1ofeov3x4NTiEao5z7LONWmJAmzbU2LAkEAxFVvCVpH
BLMOnbSEuvd0IxRZ+Wi2gtIt5bXxrLnr9fEsQ3ijvtr+HpnaVpUluNJZnJ6htSDQ
1niUJey+nkxgRQ==',

//支付宝开发平台合作伙伴秘钥 =》 支付宝公钥，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
        'alipay_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB',

//异步通知接口
        'service' => 'mobile.securitypay.pay',
//        'service' => 'http://1o5968g559.51mypc.cn/Pay/notifyMobileUrl',
//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//签名方式 不需修改
        'sign_type' => strtoupper('RSA'),

//字符编码格式 目前支持 gbk 或 utf-8
        'input_charset' => strtolower('utf-8'),

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
        'cacert' => getcwd().'/cacert.pem',

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        'transport' => 'http',
    ),

);
