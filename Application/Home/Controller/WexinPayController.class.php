<?php

namespace Home\Controller;

use Common\Common\BaseHomeController;
use Vendor\WxPayPubHelper\OrderQuery_pub;
use Vendor\WxPayPubHelper\UnifiedOrder_pub;

/**
 * Class NewsController
 * @package Home\Controller
 * 微信支付控制器
 */

class WexinPayController extends BaseHomeController
{
//初始化
    public function _initialize()
    {
        //引入WxPayPubHelper
        vendor('WxPayPubHelper.WxPayPubHelper');
    }

    //生成二维码
    public function index()
    {
        //接受当前用户
        $name = cookie('username');
        $user = authcode($name, 'DECODE', '123456');
        $order = I('post.ordernum');//订单id
        $total = I('post.WIDtotal_fee');
        //查询订单信息
        $m = M();
        $sql = "SELECT a.id,a.pk_date,a.car_id,a.re_date,a.order_code,d.charge_sum,a.pk_way,a.drive_state,a.carmodelid,d.trade_code,b.carmodeltype,b.agestyle,b.carmodelname,b.bearboxtype,b.sitecount,c.brand FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid = b.id LEFT JOIN  car_barand c ON b.barandid=c.id LEFT JOIN order_cost d ON a.id=d.order_id WHERE (a.id = %d)";
        $arr = [$order];
        $orderInfo = $m->query($sql, $arr);
        $url = $_SERVER['HTTP_REFERER'];

//        var_dump($orderInfo);

        //使用统一支付接口
//        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder = new UnifiedOrder_pub();
        //设置统一支付接口参数
        //设置必填参数
        $unifiedOrder->setParameter("body", "华邦出行");//商品描述
        //订单总金额
        $orderSum = $orderInfo[0]['charge_sum'] * 100;
        //自定义订单号，此处仅作举例
//        $timeStamp = time();
        $timeStamp = $orderInfo[0]['trade_code'];
//        $out_trade_no = C('WxPay.pub.config.APPID')."$timeStamp";
        $out_trade_no = $timeStamp;
        $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
        $unifiedOrder->setParameter("total_fee", $orderSum);//总金额
        $unifiedOrder->setParameter("notify_url", 'http://www.hbzc777.com/Home/WexinPay/notify');//通知地址
        $unifiedOrder->setParameter("trade_type", "NATIVE");//交易类型MWEB、NATIVE、WAP
        //非必填参数，商户可根据实际情况选填
        //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
        //$unifiedOrder->setParameter("device_info","XXXX");//设备号
        //$unifiedOrder->setParameter("attach","XXXX");//附加数据
        //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
        //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
//         $unifiedOrder->setParameter("goods_tag","");//商品标记
//         $unifiedOrder->setParameter("openid","19405");//用户标识
        //$unifiedOrder->setParameter("product_id","XXXX");//商品ID
        //获取统一支付接口结果
        $unifiedOrderResult = $unifiedOrder->getResult();
//         var_dump($unifiedOrder);
        //商户根据实际情况设置相应的处理流程
        if ($unifiedOrderResult["return_code"] == "FAIL") {
            //商户自行增加处理流程
            echo "通信出错：" . $unifiedOrderResult['return_msg'] . "<br>";
        } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
            //商户自行增加处理流程
            echo "错误代码：" . $unifiedOrderResult['err_code'] . "<br>";
            echo "错误代码描述：" . $unifiedOrderResult['err_code_des'] . "<br>";
        } elseif ($unifiedOrderResult["code_url"] != NULL) {
            //从统一支付接口获取到code_url
            $code_url = $unifiedOrderResult["code_url"];
            //商户自行增加处理流程
            //......
        }
        //计算租车时长
        $num = strtotime($orderInfo[0]['re_date']) - strtotime($orderInfo[0]['pk_date']);
        $d = floor($num / 3600 / 24);
        $h = floatval($num / 3600);  //%取余
        $h = ceil($h);
        $h = $h % 24;
        $data = $d . "天" . $h . "小时<br>";
//        echo $data;
        //租车时长
        $this->assign('data', $data);
        //基本信息
        $this->assign('orderInfo', $orderInfo);
        $this->assign('out_trade_no', $out_trade_no);
        $this->assign('code_url', $code_url);
        $this->assign('unifiedOrderResult', $unifiedOrderResult);
        //订单总价
        $this->assign('total', $orderSum);//$orderInfo[0]['price_rec']
        //当前用户
        $this->assign('user', $user);
        //订单号
        $this->assign('order', $out_trade_no);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        //上级地址
        $this->assign('url',$url);
        $this->assign('sys_title', $sitetitle[0]['title']);
        $this->display();
    }


    //重定向redirection（）
    public function redirection()
    {
        $order = M('order');
        $orderid = I('get.orderid');
        $sql = "SELECT a.order_state FROM `order` a LEFT JOIN order_cost b ON a.id=b.order_id WHERE (b.trade_code = '%s')";
        $arr = [$orderid];
        $orderstate = $order->query($sql, $arr);
        if ($orderstate[0]['order_state'] == 1) {
            $this->ajaxReturn(array('jumpurl' => 1));
        } else {
            $this->ajaxReturn(array('jumpurl' => 0));
        }


    }

    //微信支付回调接口
    public function notify()
    {
        $xmldata = file_get_contents('php://input');   //都要解下码
        $file = 'log.txt';//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
        $content = urldecode($xmldata);
        $jsonData = json_decode(json_encode(simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $orderId = $jsonData['out_trade_no'];
        $result_code = $jsonData['result_code'];
        $return_code = $jsonData['return_code'];
        $cash_fee = $jsonData['cash_fee'];

        if ($result_code == 'SUCCESS' and $return_code == 'SUCCESS') {
            $myfile = fopen("weixinlog.txt", "w+") or die("Unable to open file!");
            $txt = $orderId.'微信'.$result_code.'-------'.$return_code;
            fwrite($myfile, $txt);
            fclose($myfile);
            $re = $this->isOrderCode($orderId);
            /**
             * $re:carRental 租车；aboutCar 约车
             */
            if ($re == 'carRental'){
                $order = M();
                $sql = "SELECT a.price_rec,a.id,a.uid,a.order_state,b.pay_way as bpay_way FROM `order` a LEFT JOIN order_cost b ON a.id=b.order_id WHERE (b.trade_code = '%s')";
                $arr = [$orderId];
                $list = $order->query($sql, $arr);

                couponUserState($list[0]['id'],0);//修改该订单获取的优惠劵状态为未使用

                //修改订单，传参订单号和订单金额
                $res = $this->upOrder($orderId, $cash_fee, $list[0]['id'], $list[0]['uid'], $list[0]['pay_way']);
                if ($res) {
                    header("Content-Type: text/xml; charset=utf-8");
                    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml> ';
                    exit();
                }
            } else {
                //约车业务
                $tab = M();
                if ($this->isPenalty($orderId) == true){
                    //缴纳违约金
                    $sql = "UPDATE order_settlement SET Ispenalty = %d,PayType=%d WHERE OrderCode='%s'";
                    $arr = [2,2,$orderId];
                    $res = $tab->execute($sql,$arr);
                    if ($res){
                        header("Content-Type: text/xml; charset=utf-8");
                        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml> ';
                        exit();
                    }
                }else{
                    $order = "UPDATE order_netcar SET OrderState = %d WHERE OrderCode='%s'";
                    $orderArr = [5,$orderId];
                    $res = $tab->execute($order,$orderArr);

                    $cash_fee = $cash_fee / 100;
                    $sql = "UPDATE order_settlement SET ActualPay='%s',State=%d,PayType=%d WHERE OrderCode='%s'";
                    $arr = [$cash_fee,1,2,$orderId];
                    $re = $tab->execute($sql,$arr);

                    if ($res == true && $re == true) {
                        header("Content-Type: text/xml; charset=utf-8");
                        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml> ';
                        exit();
                    }
                }

            }

        }




    }


    //查询是否是违约订单
    public function isPenalty($orderId){
        $tab = M();
        $sql = "SELECT OrderState  FROM order_netcar WHERE OrderCode = '%s' ";
        $arr = [$orderId];
        $order_code = $tab->query($sql,$arr);
        if ($order_code[0]['orderstate'] == 11){
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改订单实收金额，交易表的交易状态，增加积分
     * @param $data 交易订单号
     * @param $cash 交易金额
     * @param $id 订单id
     * @param $uid 当前交易的用户id
     * @param $pay_way 支付状态
     * @return bool 返回布尔值类型 true/false
     */
    public function upOrder($data, $cash, $id, $uid, $pay_way)
    {
        $res = 0;
        $money = M('order');
        $cash = $cash / 100;
        if ($pay_way == 0) {
            $sql = "UPDATE order_cost a,`order` b,`work_member` c SET a.pay_way = 2,b.collections_rec = b.collections_rec + '" . $cash . "',b.order_state = 1,c.score = c.score + '" . $cash . "' WHERE (a.trade_code = '" . $data . "') AND (b.id='" . $id . "') AND (c.id='" . $uid . "')";
            $res = $money->execute($sql);

        }

        if ($res > 0 ) {
            return true;
        } else {
            return false;
        }

    }

    //APP端微信支付回调数据处理
    public function upOrderApp()
    {
        $ordid=I('get.out_trade_no');//支付宝返回的订单号
        $total=I('get.total_fee');//交易金额
        $table = M();
        $sql = "SELECT a.trade_code,a.charge_sum,pay_way,b.uid,b.id,b.order_state FROM order_cost a LEFT JOIN  `order` b ON a.order_id=b.id WHERE (a.trade_code='%s')";
        $arr = [$ordid];
        $orderifno = $table->query($sql,$arr);
        $money = M();
        $cash = $total / 100;
        if ($orderifno[0]['order_state'] == 0) {
            $sql = "UPDATE order_cost a,`order` b,`work_member` c SET a.pay_way = 2,b.collections_rec = b.collections_rec + '" . $cash . "',b.order_state = 1,c.score = c.score + '" . $cash . "' WHERE (a.trade_code = '" . $ordid . "') AND (b.id='" . $orderifno[0]['id'] . "') AND (c.id='" . $orderifno[0]['uid'] . "')";
            $res = $money->execute($sql);
            if ($res>0){
                $list = array('error'=>0);//表示数据修改成功
            }else{
                $list = array('error'=>1);//表示数据修改失败
            }
        }
        $json = json_encode($list);
        echo $json;
    }



}
