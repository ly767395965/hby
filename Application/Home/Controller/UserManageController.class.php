<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;
use Common\Common\sendAPI;
use Org\Util\Date;
use Vendor\Alipay\AlipaySubmit;
use Vendor\Alipay\AlipayNotify;
//date_default_timezone_set('PRC');//设置时区
/**
 * Class NewsController
 * @package Home\Controller
 * 用户中心控制器
 */

class UserManageController extends BaseHomeController{

    //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');
    }

    public function index(){
        //实例化操作的表
        $usertable = M('work_member');
        //读取cookie接受当前用户
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone,'DECODE','123456');
        if ($tel == null || $tel ==""){
            echo '<script>alert("非法访问!");window.location.href="/Home/Login";</script>';
        } else {
            if (empty($_POST)){
                $sql = "SELECT username,phone,sex,usertype,identitys,addtime,remarks FROM work_member WHERE (state=0) AND (phone='%s')";
                $arr = $tel;
                $data = $usertable->query($sql,$arr);
                $this->assign('userinfo',$data);
                cookie('usertype',$data[0]['usertype']);
                //站点信息
                $sitetitle = new IndexController();
                $sitetitle = $sitetitle->webInfo();
                $this->assign('sys_title',$sitetitle[0]['title']);
                $this->display();
            }
        }

    }

    //车辆监控数据
    /**
     * @return config
     */
    public function cartMonitor()
    {
        /**
         * 获取当前车辆轨迹
         * SELECT c.carno,b.begintime,b.endtime,a.id FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid = c.id WHERE a.uid=2 AND a.order_state < 4
         */
        //读取cookie接受当前用户
        $uid = cookie('uid');
        //账户解密
        $userid = authcode($uid,'DECODE','123456');

        $sql = "SELECT a.id,a.id as relationid,a.order_date,a.order_state,a.order_code,a.pk_date,a.re_date,c.imei,c.id as cartid,c.carno,(SELECT COUNT(id) FROM order_car WHERE orderid = a.id) as cartnumber,(SELECT COUNT(c.imei) FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid = c.id WHERE a.uid= '%d' AND a.id = relationid  AND a.order_state < '%d' AND c.imei = '') as ieminumber FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid = c.id WHERE a.uid = '%d' AND a.order_state < '%d' GROUP BY a.id ";
        $countSql = "SELECT COUNT(a.id) FROM `order` a  WHERE a.uid = '%d' AND a.order_state < '%d' ";
        $ary = [$userid,4,$userid,4];
        $this->pageDisplay($sql, $countSql, 1, $ary, 'count(a.id)', 'list', 'page','UserManage/ajaxcartMonitor',true);

        $this->display();
    }


    //获取历史订单车辆列表
    public function getOrderCarall(){
        $m = M();
        $id = I('post.id');
        $sql ="SELECT a.id,a.pk_date,a.re_date,c.id as carid,c.carno,c.imei,b.driveid FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid=c.id WHERE a.id='%d'";
        $list = $m->query($sql,[$id]);
        for ($i = 0;$i < count($list);$i++){
            if ($list[$i]['driveid'] == 0){
                $list[$i]['driveid'] = '自驾';
            } else {
                $list[$i]['driveid'] = '代驾';
            }
            if (empty($list[$i]['imei'])){
                $list[$i]['imei'] = 0;
                $list[$i]['prompt'] = '无定位设备(无法定位)';
            } else {
                $list[$i]['prompt'] = '轨迹回放';
            }
        }
        $this->ajaxReturn($list);
    }

    public function historyData(){
        $uid = cookie('uid');
        //账户解密
        $userid = authcode($uid,'DECODE','123456');

        $sql = "SELECT a.id,a.id as relationid,a.order_date,a.order_state,a.order_code,a.pk_date,a.re_date,c.imei,c.id as cartid,c.carno,(SELECT COUNT(id) FROM order_car WHERE orderid = a.id) as cartnumber,(SELECT COUNT(c.imei) FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid = c.id WHERE a.uid= '%d' AND a.id = relationid  AND a.order_state >= '%d' AND c.imei = '') as ieminumber FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid = c.id WHERE a.uid = '%d' AND a.order_state >= '%d' GROUP BY a.id ";

        $countSql = "SELECT COUNT(a.id) FROM `order` a  WHERE a.uid = '%d' AND a.order_state >= '%d' ";
        $ary = [$userid,4,$userid,4];
        $this->pageDisplay($sql, $countSql, 1, $ary, 'count(a.id)', 'list', 'page','ajaxHistoryData',true);
        $this->display();

    }


    //车辆位置
    public function getCartLocation(){
        if ($_GET){
            $this->display();
        }else{
            //读取cookie接受当前用户
            $uid = cookie('uid');
            //账户解密
            $userid = authcode($uid,'DECODE','123456');
            $orderid = I('post.id');
            $m = M();
            $sql = "SELECT a.id as orderid,c.id as cartid,c.carno,c.imei FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid = c.id WHERE a.uid= '%d' AND a.id = '%d'  AND a.order_state < '%d' ";
            $carimei = $m->query($sql,[$userid,$orderid,4]);
            $cartStsteData = $this->getCartState($carimei);
            for ($i = 0;$i < count($carimei);$i++){
                if ($carimei[$i]['imei'] == '' || $carimei[$i]['imei'] == null){
                    $carimei[$i]['imei'] = 0;
                    $carimei[$i]['prompt'] = '无定位设备(无法定位)';
                } else{
                    $carimei[$i]['prompt'] = '轨迹回放';
                }
            }
            $this->ajaxReturn(array('cartStsteData'=>$cartStsteData,'carinfo'=>$carimei));

        }
    }

    //回访轨迹
    public function markPlayback(){
        $m = M();
        if ($_GET){
            $carno = I('get.carno');
            $orderid = I('get.orderid');
            $cartid = I('get.cartid');

            $list = $this->getRoute($m,$orderid,$cartid);
            $this->assign('carno',$carno);
            $this->assign('list',$list);
            $this->display();
        }else{
            $id = I('post.id');
            $carid = I('post.carid');
            $startdate = I('post.startdate');
            $stopdate = I('post.stopdate');
            $result = $this->getRoute($m,$id,$carid);
            $time = date('Y-m-d H:i:s',time());
            if ($startdate > $time){
                $res = 1;
            } else {
                if ($result){
                    if ($startdate >= $result[0]['pk_date'] && $stopdate <= $result[0]['re_date']){
                        $startdate = strtotime($startdate);
                        $stopdate = strtotime($stopdate);

                        $url = "http://in.gpsoo.net/1/devices/history?begin_time={$startdate}&end_time={$stopdate}&access_token=20007335774210152116974132b75b44b4221a579da0d6a62fce2385f60000010016010&limit=500&imei={$result[0]['imei']}&time=1521171319&map_type=BAIDU&sign=a1b244505149d48f1f0952ae3d2bbd12&n=B779F255CA011605F3026797EB6580A8&appver=3.0&appid=1001&os=android&access_type=inner&lang=zh-CN&source=app1.2&http_seq=128&apptype=goocar&lat=26.550564&lng=106.709525&vercode=227";
                        $handle = fopen($url,"rb");
                        $content = "";
                        while (!feof($handle)) {
                            $content .= fread($handle, 10000);
                        }
                        fclose($handle);
                        $list = json_decode($content,true);
                        $res = $this->bd_decrypt($list);
                    } else{
                        $startdate = strtotime($result[0]['pk_date']);
                        $stopdate = strtotime($result[0]['re_date']);

                        $url = "http://in.gpsoo.net/1/devices/history?begin_time={$startdate}&end_time={$stopdate}&access_token=20007335774210152116974132b75b44b4221a579da0d6a62fce2385f60000010016010&limit=500&imei={$result[0]['imei']}&time=1521171319&map_type=BAIDU&sign=a1b244505149d48f1f0952ae3d2bbd12&n=B779F255CA011605F3026797EB6580A8&appver=3.0&appid=1001&os=android&access_type=inner&lang=zh-CN&source=app1.2&http_seq=128&apptype=goocar&lat=26.550564&lng=106.709525&vercode=227";
                        $handle = fopen($url,"rb");
                        $content = "";
                        while (!feof($handle)) {
                            $content .= fread($handle, 10000);
                        }
                        fclose($handle);
                        $list = json_decode($content,true);
                        $res = $this->bd_decrypt($list);
                    }
                }

            }
            $this->ajaxReturn($res);


        }
    }


    //百度经纬度转换高德
    public function bd_decrypt($data){
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;

       for ($i = 0;$i < count($data['data']['pos']);$i++){
           $x = $data['data']['pos'][$i]['lng'] - 0.0065;

           $y = $data['data']['pos'][$i]['lat'] - 0.006;

           $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);

           $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);

           $data['data']['pos'][$i]['lng'] = $z * cos($theta);

           $data['data']['pos'][$i]['lat'] = $z * sin($theta);
       }

        return $data;
    }

    public function getRoute($m,$orderid,$cartid){
        $sql = "SELECT a.pk_date,a.re_date,c.imei,b.carid,a.id FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN car_carinfo c ON b.carid = c.id WHERE a.id = '%d' AND b.carid='%d'";
        $list = $m->query($sql,[$orderid,$cartid]);
        return $list;
    }

    /**
     * 获取车辆状态数据
     */
    public function getCartState($carimei){
        $time = time();
        $account = '贵阳华邦';
        $signature = md5(md5('??123456').$time);
        $token = $this->getToken($time,$account,$signature);

        $url = "http://in.gpsoo.net/1/account/monitor?access_type=inner&account={$account}&map_type=BAIDU&target={$account}&access_token={$token}&time={$time}&sign=47fe1b4688f46bdb117016e837c2bce1&n=D714B6E9C26538488A1AEBBECBCBA602&appver=1.9.8&os=android&access_type=inner&lang=zh-CN&source=app1.2&http_seq=35&apptype=goocar&lat=26.556673&lng=106.716043&vercode=99&appid=1001";
        $handle = fopen($url,"rb");
        $content = "";
        while (!feof($handle)) {
            $content .= fread($handle, 10000);
        }
        fclose($handle);
        $res = json_decode($content,true);

        $arr = [];
        for ($j = 0;$j < count($carimei);$j++){
            for($i = 0;$i < count($res['data']);$i++){
                if ($res['data'][$i]['imei'] == $carimei[$j]['imei']){
                    $res['data'][$i]['carno'] = $carimei[$j]['carno'];
                    $res['data'][$i]['gps_time'] = date("Y-m-d H:i:s",$res['data'][$i]['gps_time']);
                    $res['data'][$i]['sys_time'] = date("Y-m-d H:i:s",$res['data'][$i]['sys_time']);
                    $res['data'][$i]['server_time'] = date("Y-m-d H:i:s",$res['data'][$i]['server_time']);
                    $res['data'][$i]['orderid'] = $carimei[$j]['orderid'];
                    $res['data'][$i]['cartid'] = $carimei[$j]['cartid'];
                    $arr[] = $res['data'][$i];
                }
            }
        }
        return $arr;

    }


    //获取token
    public function getToken($time,$account,$signature){
        $url = "http://in.gpsoo.net/1/auth/access_token?access_type=inner&account={$account}&signature={$signature}&platform=android&alias=&push=gpns&timezone=28800&channelid=12518974&time={$time}&sign=2c9c1f71510276f8ef034ba170b84e61&n=B779F255CA011605F3026797EB6580A8&appver=3.0&appid=1001&os=android&access_type=inner&lang=zh-CN&source=app1.2&http_seq=590&apptype=goocar&lat=26.550567&lng=106.709624&vercode=227";
        $handle = fopen($url,"rb");
        $content = "";
        while (!feof($handle)) {
            $content .= fread($handle, 10000);
        }
        fclose($handle);
        $res = json_decode($content,true);
        return $res['data']['access_token'];

    }




    //头部信息
    public function  header(){
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone,'DECODE','123456');
        $usertable = M();
        $sql = "SELECT score,balance,sex,username,phone FROM work_member WHERE (phone = '%s')";
        $arr = [$tel];
        $list = $usertable->query($sql,$arr);
        $time = strSub(Date('H:i:s A'),2);
        if ($time > 06 && $time < 13){
            $greetings = '上午好!';
        } elseif ($time > 12 && $time < 15){
            $greetings = '中午好!';
        } elseif ($time > 14 && $time < 18){
            $greetings = '下午好!';
        } elseif ($time > 17 && $time < 24){
            $greetings = '晚上好!';
        } else{
            $greetings = '夜间好!';
        }
        $name = $list[0]['username'];
        if ($list[0]['sex'] == 1){
            $user = $name;
        } else {
            $user = $name;
        }
        $this->ajaxReturn(array('score'=>$list[0]['score'],'balance'=>$list[0]['balance'],'user'=>$user,'greetings'=>$greetings,'phone'=>$list[0]['phone']));
    }


    //支付成功跳转的租车记录页
    public function carOrder() {
        //读取cookie接受当前用户
        $phone = cookie('tel');
        $tel = authcode($phone,'DECODE','123456');
        if ($tel == null || $tel ==""){
            echo '<script>alert("非法访问!");window.location.href="/Home/Login";</script>';
        } else {
            $userid = cookie('uid');
            $uid = authcode($userid,'DECODE','123456');
            $sql = "SELECT a.id,a.order_code,a.check_out,a.order_date,a.pk_date,a.re_date,a.price_rec,a.collections_rec,a.order_state,b.usertype FROM `order` a LEFT JOIN work_member b ON a.uid = b.id WHERE uid = %d ORDER BY id DESC";
            $countSql = "SELECT COUNT(a.id) FROM `order` a LEFT JOIN work_member b ON a.uid = b.id WHERE a.uid = %d";
            $ary = [$uid];
            $this->pageDisplay($sql, $countSql, 5, $ary, 'count(a.id)', 'list', 'page','UserManage/ajax',true);
            $this->display();
        }

    }

    //优惠券列表
    public function coupon(){
        //读取cookie接受当前用户
        $phone = cookie('tel');
        $tel = authcode($phone,'DECODE','123456');
        if ($tel == null || $tel ==""){
            echo '<script>alert("非法访问!");window.location.href="/Home/Login";</script>';
        } else {
            $sql = "SELECT b.id,a.coupon_name,a.info,b.money,b.discount,b.use_limit,b.use_condition,b.termofvalidity,b.addtime,b.use_type,b.coupon_type,current_timestamp() as nowtime 
FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid LEFT JOIN work_member c ON b.uid=c.id WHERE c.phone='%s' AND b.coupon_type!=2 AND b.use_type=0 AND b.is_del=0 ORDER BY b.id DESC ";
            $countSql = "SELECT COUNT(b.id) FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid LEFT JOIN work_member c ON b.uid=c.id WHERE c.phone='%s' AND b.coupon_type!=2 AND b.use_type=0 AND b.is_del=0";
            $arr = [$tel];
            $this->pageDisplay($sql, $countSql, 10, $arr, 'count(b.id)', 'list', 'page','UserManage/couponAjax',true);
            $this->display();
        }
    }

    //查询优惠券介绍信息
    public function couponInfo(){
        $tab = M();
        $id = I('get.id');
        $sql = "SELECT a.info,a.coupon_name,b.addtime,b.termofvalidity FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid WHERE b.id=%d";
        $arr = [$id];
        $list = $tab->query($sql,$arr);
        $this->ajaxReturn(array('data'=>$list[0]['info'],'name'=>$list[0]['coupon_name'],'bgin'=>$list[0]['addtime'],'end'=>$list[0]['termofvalidity']));

    }

    public function link() {
        $this->display();
    }

    //付款方法pay()
    public function pay() {
        $id = '';
        $id = I('get.id');
        $m = M();
        $sql = "SELECT id,price_rec FROM `order` WHERE (id = %d)";
        $arr = [$id];
        $data = $m->query($sql,$arr);
        $this->ajaxReturn(array('total' => $data[0]['price_rec'],'id' => $data[0]['id']));
    }

    //取消订单 offOrder
    public function offOrder() {
        $id = I('get.id');
        //更改订单状态
        $order = M('order');
        $sql = "SELECT car_id FROM `order` WHERE (id = %d)";
        $arr = [$id];
        $carid = $order->query($sql,$arr);

        //判断订单是否是优惠车辆类型
        if ($carid[0]['car_id'] == 0){
            $data = [];
            $data['order_state'] = 10;
            if ($order->where(array('id=' . $id))->save($data)) {
                echo '<script>window.location.href = document.referrer;</script>';
            } else {
                echo '<script>window.location.href = document.referrer;</script>';
            }
        } else {
            $data = [];
            $data['order_state'] = 10;
            if ($order->where(array('id=' . $id))->save($data)) {
                $carinfo = M('car_carinfo');
                //查询订单对应的车辆信息
                $sql = "SELECT car_id FROM `order` WHERE (id = %d)";
                $arr = [$id];
                $carid = $order->query($sql,$arr);
                //更改优惠车辆状态
                $car = array();
                $car['usestatus'] = 0;
                if ($carinfo->where(array('id='.$carid[0]['car_id']))->save($car)){
                    echo '<script>window.location.href = document.referrer;</script>';
                }

            } else {
                echo '<script>window.location.href = document.referrer;</script>';
            }
        }
    }

    //退款申请方法 refundApply
    public function refundApply() {
        $id = I('get.id');
        $order = M('order');
        $data = [];
        $data['order_state'] = 11;
        if ($order->where(array('id=' . $id))->save($data)) {
            echo '<script>alert("退款已受理!");window.location.href = document.referrer;</script>';
        } else {
            echo '<script>alert("退款申请失败!");window.location.href = document.referrer;</script>';
        }
    }

    //订单详情查询 orderPage
    public function orderPage() {
        $orderid = I('get.id');
        $order = M();
        $sql = "SELECT a.car_id,a.dp_price,a.in_cost,a.oil_price,a.in_price,a.in_dep,a.tolls,a.in_code,a.wash_price,a.re_price,a.collections_rec,a.u_price,
a.order_code,a.price_rec,a.pk_date,a.re_date,a.check_out,a.driver_price,a.order_state,a.drive_state,a.pk_way,a.send_location,b.username,c.carmodeltype,c.frontimg,c.carmodelname,c.agestyle,c.sitecount,c.bearboxtype,d.carno,e.brand 
FROM `order` a  LEFT JOIN work_member b ON a.uid = b.id 
LEFT JOIN car_carmodel c ON a.carmodelid = c.id 
LEFT JOIN car_carinfo d ON a.car_id = d.id LEFT JOIN car_barand e ON c.barandid = e.id 
WHERE (a.id = %d)";
        $arr = [$orderid];
        $list = $order->query($sql,$arr);
        //查询订单交易信息
        $orderInfo = "SELECT a.trade_code,a.charge_time,a.charge_sum,a.charge_road,a.pay_way FROM order_cost a LEFT JOIN `order` b ON a.order_id = b.id WHERE (b.id=%d)";
        $ary = [$orderid];
        $tradeInfo = $order->query($orderInfo,$ary);
        //计算租车租时长
        $num = strtotime($list[0]['re_date']) - strtotime($list[0]['pk_date']);
        $d = floor($num / 3600 / 24);
        $h = floatval($num / 3600);  //%取余
        $h = ceil($h);
        $h = $h % 24;
        $data = $d . "天" . $h . "小时<br>";
//        驾驶方式
        if ($list[0]['drive_state'] == 1){
            $drive_state = '是';
        }else{
            $drive_state = '否';
        }

        //取车方式
        if ($list[0]['pk_way'] == 2){
            $pk_way = '是';
        }else{
            $pk_way = '否';
        }

        //车辆类型
        if ($list[0]['carmodeltype'] == 1){
            $carmodeltype = '商务车';
        } elseif ($list[0]['carmodeltype'] == 2) {
            $carmodeltype = '越野车';
        } elseif ($list[0]['carmodeltype'] == 3) {
            $carmodeltype = '面包车';
        } elseif ($list[0]['carmodeltype'] == 4) {
            $carmodeltype = '轿车';
        } elseif ($list[0]['carmodeltype'] == 5) {
            $carmodeltype = '客车';
        }

        //计算订单总金额
        $sum = M();
        $order_sum = "SELECT SUM(a.charge_sum) FROM order_cost a LEFT JOIN `order` b ON a.order_id = b.id WHERE b.id=%d";
        $ary = [$orderid];
        $ordersum = $sum->query($order_sum,$ary);
//        echo "<pre>";
//        print_r($ordersum);
        $strAry = $this->carADHandle($list[0]['send_location']);
        $arr = array(
            'name' => $list[0]['username'],//租车人
            'times' => $data,//租期
            'sumnum' => $list[0]['collections_rec'],//实收金额
            'price_rec' => $list[0]['price_rec'],//应收金额
            'order_state' => $list[0]['order_state'],//订单状态
            'u_price' => $list[0]['u_price'],//日租价
            'order_code' => $list[0]['order_code'],//订单号
            'img' => $list[0]['frontimg'],//车辆图片
            'pk_date' => $list[0]['pk_date'],//取车时间
            're_date' => $list[0]['re_date'],//还车时间
            'drive_state' => $drive_state,//驾驶方式
            'pk_way' => $pk_way,//取车方式
            'brand' => $list[0]['brand'],//品牌
            'carmodeltype' => $carmodeltype,//车辆类型
            'agestyle' => $list[0]['agestyle'],//年代款
            'carmodelname' => $list[0]['carmodelname'],//车型
            'bearboxtype' => $list[0]['bearboxtype'],//变速箱
            'sitecount' => $list[0]['sitecount'],//座位数
            'carno' => $list[0]['carno'],//车牌号
            'send_location' => $strAry[0][0],//送车地址
            'dp_price' => $list[0]['dp_price'],//违章金额
            'in_price' => $list[0]['in_price'],//开票金额
            'in_code' => $list[0]['in_code'],//发票号
            'in_cost' => $list[0]['in_cost'],//开票成本
            'in_dep' => $list[0]['in_dep'],//开票单位
            'wash_price' => $list[0]['wash_price'],//洗车费
            'oil_price' => $list[0]['oil_price'],//油费
            'tolls' => $list[0]['tolls'],//过路费
            're_price' => $list[0]['re_price'],//维修费
            'car_id' => $list[0]['car_id'],//车辆id
            'driver_price' => $list[0]['driver_price'],//代驾费
            'check_out' => $list[0]['check_out'],//结账状态
            // 交易明细
//            'trade' => array(),
            'ordersum'=> $ordersum[0]['sum(a.charge_sum)'],//订单总金额
        );
        foreach ($tradeInfo as $key => $value){

            if ($value['charge_road'] == 1 )
            {
                $charge_road = '预付';
            } elseif ($value['charge_road'] == 2){
                $charge_road = '结账';
            } elseif ($value['charge_road'] == 3){
                $charge_road = '补交';
            } elseif ($value['charge_road'] == 4){
                $charge_road = '退款';
            } elseif ($value['charge_road'] == 5){
                $charge_road = '交违章押金';
            } elseif ($value['charge_road'] == 6){
                $charge_road = '退违章押金';
            } elseif ($value['charge_road'] == 7){
                $charge_road = '补交违章押金';
            }
            //判断支付状态
            if ($value['pay_way'] != 0 ){
                $pay_way = '已支付';
            } else {
                $pay_way = '未支付';
            }
            $value['charge_road'] = $charge_road;
            $value['pay_way'] = $pay_way;
            $arr['trade'][] = $value;
        }

        $this->ajaxReturn($arr);

    }


    //执行修改密码方法
    public function pass() {

        $usertable = M('work_member');
        if (!empty($_POST)) {
            $rules = array(
//                array('phone', 'require', '<script>alert("手机不能为空！");history.back(-1);</script>', 1),
//                array('newpass', 'require', '<script>alert("新密码不能为空！");history.back(-1);</script>', 1),
            );
            if (!$usertable->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($usertable->getError());
            } else {
                //接受验证码
                $code = cookie('code');
                //创建当前时间
                $nowTime = date('Y-m-d H:i:s ', $_SERVER['REQUEST_TIME']);
                //接受验证码生成时间
                $oldTime = cookie('codeTime');
                //计算验证码过期时间
                if (floor((strtotime($nowTime)-strtotime($oldTime))/86400/60)>5) {
                    cookie('code',null);
                    cookie('codeTime',null);
                    //验证码过期
                    $this->ajaxReturn(array('state'=>4));
                } else {
                    //判断页面输入的验证码是否正确
                    if ($code==I('post.code')) {
                        $tel = I('post.phone');
                        $sql = "SELECT COUNT(ID) FROM work_member WHERE (phone='%s')";
                        $arr = [$tel];
                        $list = $usertable->query($sql,$arr);
                        if ($list[0]['count(id)'] >0) {
                            $pass = md5(I('post.newpass'));
                            $editpass = "UPDATE work_member SET userpass='%s' WHERE (phone='%s')";
                            $arr1 = [$pass,$tel];
                            $insert = $usertable->execute($editpass,$arr1);
                            if ($insert !== false){
                                cookie('code',null);
                                cookie('codeTime',null);
                                //密码修改成功
                                $this->ajaxReturn(array('state'=>0));
                            }else{
                                cookie('code',null);
                                cookie('codeTime',null);
                                //密码修改失败
                                $this->ajaxReturn(array('state'=>1));
                            }
                        } else {
                            // 该手机用户不存在
                            $this->ajaxReturn(array('state'=>2));
                        }
                    } else {
                        // 输入的验证码有误
                        $this->ajaxReturn(array('state'=>3));

                    }
                }
            }
        }


    }

    //修改密码方法 editpass（）
    public function editpass()
    {
        //读取cookie接受当前用户
        $phone = cookie('tel');
        $tel = authcode($phone,'DECODE','123456');
        if ($tel == null || $tel ==""){
            echo '<script>alert("非法访问!");window.location.href="/Home/Login";</script>';
        } else {
            //站点信息
            $sitetitle = new IndexController();
            $sitetitle = $sitetitle->webInfo();
            $this->assign('sys_title',$sitetitle[0]['title']);
            $this->display();

        }
    }

    //找回密码
    public function BackPass () {
        $usertable = M('work_member');
        if (empty($_POST)) {
            //站点信息
            $sitetitle = new IndexController();
            $sitetitle = $sitetitle->webInfo();
            $this->assign('sys_title',$sitetitle[0]['title']);
            $this->display();
        }
        if (!empty($_POST)) {
            $rules = array(
                array('phone', 'require', '<script>alert("手机不能为空！");history.back(-1);</script>', 1),
                array('newpass', 'require', '<script>alert("新密码不能为空！");history.back(-1);</script>', 1),
            );
            if (!$usertable->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($usertable->getError());
            } else {
                //接受验证码
                $code = cookie('code');
                //创建当前时间
                $nowTime = date('Y-m-d H:i:s ', $_SERVER['REQUEST_TIME']);
                //接受验证码生成时间
                $oldTime = cookie('codeTime');
                //计算验证码过期时间
                if (floor((strtotime($nowTime)-strtotime($oldTime))/86400/60)>5) {
                    cookie('code',null);
                    cookie('codeTime',null);
                    echo '<script>alert("验证码过期");history.back(-1);</script>';
                } else {
                    //判断页面输入的验证码是否正确
                    if ($code==I('post.code')) {
                        $tel = I('post.phone');
                        $sql = "SELECT COUNT(ID) FROM work_member WHERE (phone='%s')";
                        $arr = [$tel];
                        $list = $usertable->query($sql,$arr);
                        if ($list[0]['count(id)'] >0) {
                            $pass = md5(I('post.newpass'));
                            $editpass = "UPDATE work_member SET userpass='%s' WHERE (phone='%s')";
                            $arr1 = [$pass,$tel];
                            $insert = $usertable->execute($editpass,$arr1);
                            if ($insert !== false){
                                cookie('code',null);
                                cookie('codeTime',null);
                                $this->success('密码修改成功!',U('/Home/Login'),1);
                            }else{
                                cookie('code',null);
                                cookie('codeTime',null);
                                $this->error('修改失败','',1);
                            }
                        } else {
                            echo '<script>alert("该手机用户不存在！");history.back(-1);</script>';
                        }
                    } else {
                        echo '<script>alert("输入的验证码有误!");history.back(-1);</script>';
                    }
                }


            }

        }
    }

    // 充值记录方法 record()
    public function record() {
        //读取cookie接受当前用户
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone,'DECODE','123456');
        $user = M();
        $sql = "SELECT id FROM work_member WHERE (phone = '%s')";
        $arr = [$tel];
        $uid = $user->query($sql,$arr);

        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        //查询充值记录
        $rec = "SELECT id,amount,credit_order,credit_time,state FROM recharge WHERE (uid = %d) ORDER by id DESC ";
        $art = [$uid[0]['id']];
        $countSql = "SELECT COUNT(ID) FROM `recharge` WHERE (uid = %d)";
        $this->pageDisplay($rec, $countSql, 10, $art, 'count(id)', 'list', 'page','UserManage/rechargeAjax',true);
        $this->display();
    }


    //在线充值方法 recharge（）
    public function recharge() {
        $table = M('recharge');
        //读取cookie接受当前用户
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone,'DECODE','123456');
        $user = M();
        $sql = "SELECT id FROM work_member WHERE (phone = '%s')";
        $arr = [$tel];
        $uid = $user->query($sql,$arr);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);


        if (!empty($_POST)){
        }
        $this->display('rechargeOrder');

    }

    //判断充值金额是否是正整数
    function check_zzs($varnum){
        $string_var = "0123456789";
        $len_string = strlen($varnum);
        if(substr($varnum,0,1)=="0"){
            return false;
            die();
        }else{
            for($i=0;$i<$len_string;$i++){
                $checkint = strpos($string_var,substr($varnum,$i,1));
                if($checkint===false){
                    return false;
                    die();
                }
            }
            return true;
        }
    }

    //充值 支付宝支付方法
    public function doAlipay(){
        $id = I('post.orderid');
        $table = M();
        $sql = "SELECT credit_order,amount FROM `recharge`  WHERE (id = %d)";
        $arr = [$id];
        $list = $table->query($sql,$arr);
        //调用支付宝配置
        $alipay_config=C('recharge_alipay_config');
        /**************************请求参数**************************/
        $payment_type = "1"; //支付类型 //必填，不能修改
        $notify_url = C('alipay.notify_url'); //服务器异步通知页面路径
        $return_url = C('alipay.return_url'); //页面跳转同步通知页面路径
        $seller_email = C('alipay.seller_email');//卖家支付宝帐户必填
//        $out_trade_no = $_POST['WIDout_trade_no']; //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $list[0]['credit_order']; //商户订单号，商户网站订单系统中唯一订单号，必填
//        $subject = $_POST['WIDsubject']; //订单名称，必填
        $subject = '华邦出行'; //订单名称，必填
        $total_fee = $list[0]['amount']; //付款金额，必填
        $body = $_POST['WIDbody'];//商品描述，可空
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
//        echo $html_text;
    }

//异步跳转
    public function notifyUrl()
    {
        //这里还是通过C函数来读取配置项，赋值给$alipay_config
        $alipay_config = C('recharge_alipay_config');
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if ($verify_result) {
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $out_trade_no   = $_POST['out_trade_no'];      //商户订单号
            $trade_status   = $_POST['trade_status'];      //交易状态
            $total_fee   = $_POST['total_fee'];      //交易状态
            $parameter = array(
                "out_trade_no"     => $out_trade_no, //商户订单编号；
                "trade_status"     => $trade_status, //交易状态
                "total_fee"     => $total_fee, //交易状态
            );
            if ($_POST['trade_status'] == 'TRADE_FINISHED') {

            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                if(!checkRechargeStatus($out_trade_no)){
                    rechargeHandle($parameter);
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
        $alipay_config = C('recharge_alipay_config');
//        $alipayNotify = new \Vendor\Alipay\AlipayNotify($alipay_config);//计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();

        if ($verify_result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            $out_trade_no = $_GET['out_trade_no'];      //商户订单号
            $trade_status = $_GET['trade_status'];      //交易状态
            $parameter = array(
                "out_trade_no"     => $out_trade_no,      //商户订单编号；
                "trade_status"     => $trade_status,      //交易状态

            );
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
//                if(!checkRechargeStatus($out_trade_no)){
//                    rechargeHandle($parameter);  //进行订单处理，并传送从支付宝返回的参数；
//                }
//
                $this->success('支付成功，正在跳转到用户中心!',U('UserManage/record'),3);

            } else {
                $this->redirect(C('alipay.errorPage'));//跳转到配置项中配置的支付失败页面；
            }
        } else {
            echo('验证失败:');
        }


    }



    public function setSms()
    {
        $usertel = I('post.usertel');//接受手机号
        $yzm = rand(10000,99999);//生成随机验证码
        $create = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
        $url 		= "http://www.yzmsms.cn/sendSmsYZM.do";//提/交地址
        $username 	= 'huabangyzm';//用户名 验证码平台账户
        $password 	= "p0GgyQ";//原密码
        $sendAPI = new sendAPI($url,$username,$password);
        $data = array(
            'content' 	=> '【华邦出行】验证码为：'. $yzm .',有效期为5分钟,请尽快使用!',//短信内容必须含有“码”字
            'mobile' 	=> $usertel,//手机号码
            'xh'		=> ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $resultStr = $sendAPI->sendSMS('POST');//GET or POST
//        $resultStr = '1,22222';
//        $this->ajaxReturn(array('state'=>$resultStr));
        $result = explode(',',$resultStr);
        if ($result[0]==1) {
            cookie('code',$yzm);
            cookie('codeTime',$create);
            $this->ajaxReturn(array('state'=>1));
        } else {
            $this->ajaxReturn(array('state'=>0));
        }
    }

    public function myOrder()
    {
        if ($_GET){
            $orderType = I('get.orderType');
            if ($orderType == 'paySuccess'){
                $this->success('支付成功',U('UserManage/Index'),1);
            } elseif ($orderType == 'payFail'){
                echo '支付失败！';
            }
        }
    }

    //重定向wap端订单记录
    public function wapOrder(){
        $this->display('wapOrder');
    }

}