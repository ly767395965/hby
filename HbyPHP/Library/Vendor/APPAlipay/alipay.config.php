<?php
/* *
 * 配置文件
 * 版本：1.0
 * 日期：2016-06-06
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
*/
 
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
$alipay_config['partner']		= '2088421352218578';

//商户的私钥,此处填写原始私钥去头去尾，RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
$alipay_config['private_key']	= 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALrpaftDFYhWceb1
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
1niUJey+nkxgRQ==';

//支付宝的公钥，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
$alipay_config['alipay_public_key']= 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';

//异步通知接口
$alipay_config['service']= 'mobile.securitypay.pay';
//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//签名方式 不需修改
$alipay_config['sign_type']    = strtoupper('RSA');

//字符编码格式 目前支持 gbk 或 utf-8
$alipay_config['input_charset']= strtolower('utf-8');

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
$alipay_config['cacert']    = getcwd().'/cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$alipay_config['transport']    = 'http';
?>