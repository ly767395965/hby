<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;
use  Admin\Controller\SubmittedController;
use Vendor\Alipay\AlipayNotify;
use Vendor\Alipay\AlipaySubmit;
use Vendor\APPAlipay\APPAlipayNotify;


date_default_timezone_set("PRC");
/**
 * Class NewsController
 * @package Home\Controller
 * 支付控制器
 */

class PayController extends BaseHomeController{

    //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');

        vendor('APPAlipay.alipay_rsa.function');
        vendor('APPAlipay.alipay_core.function');


    }


    //doalipay 支付方法
    public function doAlipay(){
        $ordernum = I('post.ordernum');
        $order = M();
        $sql = "SELECT a.trade_code,a.charge_sum FROM `order_cost` a LEFT JOIN `order` b ON a.order_id = b.id WHERE (b.id = %d) AND (a.pay_way=0)";
        $arr = [$ordernum];
        $total = $order->query($sql,$arr);

//        header("Content-type:text/html;charset=utf-8");
        //调用支付宝配置
        $alipay_config=C('alipay_config');
        /**************************请求参数**************************/
        $payment_type = "1"; //支付类型 //必填，不能修改
        $notify_url = C('alipay.notify_url'); //服务器异步通知页面路径
        $return_url = C('alipay.return_url'); //页面跳转同步通知页面路径
        $seller_email = C('alipay.seller_email');//卖家支付宝帐户必填
//        $out_trade_no = $_POST['WIDout_trade_no']; //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $total[0]['trade_code']; //商户订单号，商户网站订单系统中唯一订单号，必填
//        $subject = $_POST['WIDsubject']; //订单名称，必填
        $subject = '华邦出行'; //订单名称，必填
        $total_fee = $total[0]['charge_sum']; //付款金额，必填
        $body = '商品描述';//商品描述，可空
        $show_url = $_POST['ordshow_url'];  //商品展示地址 通过支付页面的表单进行传递
        $anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
        $exter_invoke_ip = get_client_ip(); //客户端的IP地址

        /************************************************************/


//构造要请求的参数数组，无需改动
        $parameter = array(
            "service"       => $alipay_config['service'],
            "partner"       => $alipay_config['partner'],
            "seller_id"  => $alipay_config['seller_id'],
            "payment_type"	=> $alipay_config['payment_type'],
            "notify_url"	=> $alipay_config['notify_url'],
            "return_url"	=> $alipay_config['return_url'],

            "anti_phishing_key"=>$alipay_config['anti_phishing_key'],
            "exter_invoke_ip"=>$alipay_config['exter_invoke_ip'],
            "out_trade_no"	=> $out_trade_no,
            "subject"	=> $subject,
            "total_fee"	=> $total_fee,
            "body"	=> $body,
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
            //如"参数名"=>"参数值"

        );
//建立请求
//        $alipaySubmit = new AlipaySubmit($alipay_config);
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"post", "确认");
        echo $html_text;

    }


    //异步跳转
    public function notifyUrl()
    {
        //这里还是通过C函数来读取配置项，赋值给$alipay_config
        $alipay_config = C('alipay_config');
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if ($verify_result) {
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $out_trade_no   = $_POST['out_trade_no'];      //商户订单号
            $trade_status   = $_POST['trade_status'];      //交易状态
            $subject   = $_POST['subject'];      //商品名称
            $body   = $_POST['body'];      //商品描述
            $total_fee   = $_POST['total_fee'];//交易金额
            $parameter = array(
                "out_trade_no"     => $out_trade_no, //商户订单编号；
                "trade_status"     => $trade_status, //交易状态
                "subject"     => $subject, //商品名称
                "body"     => $body, //商品描述
                "total_fee"     => $total_fee, //商品描述
            );
            if ($_POST['trade_status'] == 'TRADE_FINISHED') {

            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                if(!$this->checkOrderStatus($out_trade_no)){
                    $Ord = M();
                    $sql = "SELECT a.id,a.uid,a.order_state FROM `order` a LEFT JOIN order_cost b ON a.id=b.order_id WHERE (b.trade_code = '%s')";
                    $arr = [$out_trade_no];
                    $list = $Ord->query($sql,$arr);

                    couponUserState($list[0]['id'],0);//修改该订单获取的优惠劵状态为未使用

                    $res = $this->orderHandle($parameter,$list[0]['id'],$list[0]['uid'],$list[0]['order_state']);
                    if ($res !== false) {
                        exit();
                    }
                    //进行订单处理，并传送从支付宝返回的参数；
                }
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";        //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
        }
    }

    // 同步跳转；
    public function returnUrl(){
        $alipay_config = C('alipay_config');
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            $out_trade_no = $_GET['out_trade_no'];      //商户订单号
            $trade_status = $_GET['trade_status'];      //交易状态
            $subject   = $_GET['subject'];      //商品名称
            $body   = $_GET['body'];      //商品描述
            $parameter = array(
                "out_trade_no"     => $out_trade_no,      //商户订单编号；
                "trade_status"     => $trade_status,      //交易状态
                "subject"     => $subject, //商品名称
                "body"     => $body, //商品描述

            );
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
//                if(!checkOrderStatus($out_trade_no)){
//                    orderHandle($parameter);  //进行订单处理，并传送从支付宝返回的参数；
//                }

                $this->success('支付成功，正在跳转到用户中心!',U('UserManage/carOrder'),1);

            } else {
                $this->redirect(C('alipay.errorPage'));//跳转到配置项中配置的支付失败页面；
            }
        } else {
            echo('验证失败:');
        }


    }

    // Wap站点支付宝同步跳转；
    public function returnWapUrl(){

        $alipay_config = C('alipay_config');
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            $out_trade_no = $_GET['out_trade_no'];      //商户订单号
            $trade_status = $_GET['trade_status'];      //交易状态
            $subject   = $_GET['subject'];      //商品名称
            $body   = $_GET['body'];      //商品描述
            $parameter = array(
                "out_trade_no"     => $out_trade_no,      //商户订单编号；
                "trade_status"     => $trade_status,      //交易状态
                "subject"     => $subject, //商品名称
                "body"     => $body, //商品描述

            );
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                if(!$this->checkOrderStatus($out_trade_no)){
                    $Ord = M();
                    $sql = "SELECT a.id,a.uid,a.order_state FROM `order` a LEFT JOIN order_cost b ON 
a.id=b.order_id WHERE (b.trade_code = '%s')";
                    $arr = [$out_trade_no];
                    $list = $Ord->query($sql,$arr);
                    $res = $this->orderHandle($parameter,$list[0]['id'],$list[0]['uid'],$list[0]['order_state']);
                    if ($res !== false) {
                        exit();
                    }
                    //进行订单处理，并传送从支付宝返回的参数；

                }
                $this->success('支付成功，正在跳转到我的订单!',U('UserManage/wapOrder'),1);

            } else {
                $this->redirect(C('alipay.errorPage'));//跳转到配置项中配置的支付失败页面；
            }
        } else {
            echo('验证失败:');
        }


    }


    //移动端异步
    public function notifyMobileUrl()
    {
        $alipay_mobile = C('alipay_mobile');

        $alipayNotify = new APPAlipayNotify($alipay_mobile);

        if($alipayNotify->getResponse($_POST['notify_id']))//判断成功之后使用getResponse方法判断是否是支付宝发来的异步通知。
        {
            if($alipayNotify->getSignVeryfy($_POST, $_POST['sign'])) {//使用支付宝公钥验签
                $out_trade_no   = $_POST['out_trade_no'];      //商户订单号
                $trade_status   = $_POST['trade_status'];      //交易状态
                $subject   = $_POST['subject'];      //商品名称
                $body   = $_POST['body'];      //商品描述
                $total_fee   = $_POST['total_fee'];//交易金额
                $parameter = array(
                    "out_trade_no"     => $out_trade_no, //商户订单编号；
                    "trade_status"     => $trade_status, //交易状态
                    "subject"     => $subject, //商品名称
                    "body"     => $body, //商品描述
                    "total_fee"     => $total_fee, //商品描述
                );

                if($_POST['trade_status'] == 'TRADE_FINISHED') {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序
                    //注意：
                    //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
                    //请务必判断请求时的out_trade_no、total_fee、seller_id与通知时获取的out_trade_no、total_fee、seller_id为一致的
                }
                else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {

                    $re = $this->isOrderCode($out_trade_no);
                    /**
                     * $re:carRental 租车；aboutCar 约车
                     */
					 $myfile = fopen("ok.txt", "w") or die("Unable to open file!");
							$txt = 'ok66-'.$re;
							fwrite($myfile, $txt);
							fclose($myfile);
                    if ($re == 'carRental'){
                        //租车业务
                        if(!$this->checkOrderStatus($out_trade_no)){
                            $Ord = M();
                            $sql = "SELECT a.id,a.uid,a.order_state FROM `order` a LEFT JOIN order_cost b ON a.id=b.order_id WHERE (b.trade_code = '%s')";
                            $arr = [$out_trade_no];
                            $list = $Ord->query($sql,$arr);

                            $res = $this->orderHandle($parameter,$list[0]['id'],$list[0]['uid'],$list[0]['order_state']);

                            if ($res == false) {
                                exit();
                            }
//                            进行订单处理，并传送从支付宝返回的参数；
//                        $myfile = fopen("ok.txt", "w") or die("Unable to open file!");
//                        $txt = 'ok-'.$out_trade_no;
//                        fwrite($myfile, $txt);
//                        fclose($myfile);
                        }
                    } else {
                        //约车业务
						$reply = $this->checkOrderStatus($out_trade_no);
						
                        if($reply){
							$tab = M();
							if ($reply == 4){
								 //订单支付
                                $order = "UPDATE order_netcar SET OrderState = %d WHERE OrderCode='%s'";
                                $orderArr = [5,$out_trade_no];
                                $res = $tab->execute($order,$orderArr);

                                $sql = "UPDATE order_settlement SET ActualPay='%s',State=%d,PayType=%d WHERE OrderCode='%s'";
                                $arr = [$_POST['total_fee'],1,1,$out_trade_no];
                                $re = $tab->execute($sql,$arr);

                                if ($res == false && $re == false) {
                                    exit();
                                }
                                $this->infoSub($out_trade_no);  //经营支付信息报送
							}else if ($reply == 11){
								//缴纳违约金
                                $sql = "UPDATE order_settlement SET Ispenalty = %d,PayType=%d WHERE OrderCode='%s'";
                                $arr = [2,1,$out_trade_no];
                                if (!$tab->execute($sql,$arr)){
                                    exit();
                                }
							}
                        }

                    }

                }
                //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
                echo "success";		//请不要修改或删除
            }
            else //验证签名失败
            {
                echo "sign fail";
            }
        }
        else //验证是否来自支付宝的通知失败
        {
            echo "response fail";
        }
    }

    /**
     * 在线交易订单支付处理函数
     * 函数功能：根据支付接口传回的数据判断该订单是否已经支付成功；
     * 返回值：如果订单已经成功支付，返回true，否则返回false；
     * @param $orderCode
     * @return bool
     */
    protected function checkOrderStatus($orderCode)
    {
        $Ord=M();
        $re = $this->isOrderCode($orderCode);
        $arr = [$orderCode];
        if ($re == 'carRental'){
            $sql = "SELECT a.order_state FROM `order` a LEFT JOIN order_cost b ON a.id=b.order_id WHERE (b.trade_code = '%s')";
            $order_code = $Ord->query($sql,$arr);
            if($order_code[0]['order_state']==1){
                return true;
            }else{
                return false;
            }
        } else {
            $sql = "SELECT OrderState  FROM order_netcar WHERE OrderCode = '%s' ";
            $order_code = $Ord->query($sql,$arr);
			return $order_code[0]['orderstate'];
        }

        

    }

    /**
     * @param $parameter 交易订单号 和交易金额
     * @param $id 订单id
     * @param $uid  当前交易的用户id
     * @param $order_state 订单状态
     * @return bool
     */
    protected function orderHandle($parameter,$id,$uid,$order_state)
    {
        $Ord=M();
        $ordid=$parameter['out_trade_no'];//支付宝返回的订单号
        $total=$parameter['total_fee'];//交易金额
        $res = 0;
        if ($order_state == 0){
            $sql = "UPDATE order_cost a,`order` b,`work_member` c SET a.pay_way = 1,b.collections_rec = b.collections_rec + '" . $total . "',b.order_state = 1,c.score = c.score + '" . $total . "' WHERE (a.trade_code = '" . $ordid . "') AND (b.id='" . $id . "') AND (c.id='" . $uid . "')";
            $res = $Ord->execute($sql);
        }

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    //app端同步回调 数据处理
    public function orderHandleApp()
    {
        $Ord=M();
        $ordid=I('get.out_trade_no');//支付宝返回的订单号
        $total=I('get.total_fee');//交易金额
        $table = M();
        $sql = "SELECT a.trade_code,a.charge_sum,pay_way,b.uid,b.id,b.order_state FROM order_cost a LEFT JOIN  `order` b ON a.order_id=b.id WHERE (a.trade_code='%s')";
        $arr = [$ordid];
        $orderifno = $table->query($sql,$arr);

        if ($orderifno[0]['order_state'] == 0){
            $sql = "UPDATE order_cost a,`order` b,`work_member` c SET a.pay_way = 1,b.collections_rec = b.collections_rec + '" . $total . "',b.order_state = 1,c.score = c.score + '" . $total . "' WHERE (a.trade_code = '" . $ordid . "') AND (b.id='" . $orderifno[0]['id'] . "') AND (c.id='" . $orderifno[0]['uid'] . "')";
            $res = $Ord->execute($sql);
            if ($res>0){
                $list = array('error'=>0);//表示数据修改成功
            }else{
                $list = array('error'=>1);//表示数据修改失败
            }
        }
        $json = json_encode($list);
        echo $json;

    }

    //网约车经营支付信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('operatePay',$id);
    }



}