<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;
use Org\Util\Date;

/**
 * Class NewsController
 * @package Home\Controller
 * 成功订单控制器
 */

class GenerateController extends BaseHomeController
{
    protected static $table = 'car_carmodel';

    public function index()
    {
        //接受当前用户
        $name = cookie('username');
        $user = authcode($name, 'DECODE', '123456');
        $user_id = authcode(cookie('uid'), 'DECODE', '123456');
        $member = M('work_member');
        //读取cookie接受当前用户
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone, 'DECODE', '123456');
        $uid = "SELECT id,usertype,usertype FROM work_member WHERE (phone = '" . $tel . "')";
        $userid = $member->query($uid);
        //总价
        $total = "";
        //获取车型id
        $id = I('get.id');
        $model = M();
        //查询价格
        $Price = "SELECT a.id,a.frontimg,a.carmodelname,a.bearboxtype,a.shortdayprice,a.weekdayprice,a.monthdayPrice,a.carmodeltype,a.agestyle,a.sitecount,b.brand,c.id as id FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id LEFT JOIN `order` c ON a.id= c.carmodelid WHERE (a.isdel=0) AND (a.id=%d) ORDER BY c.id DESC";
        $arr = [$id];
        $rental = $model->query($Price, $arr);
        if (!empty($_POST)) {
            //取车日期
            $start = I('post.start');
            //取车时间
            $stime = I('post.stime');
            //还车日期
            $end = I('post.end');
            //还车时间
            $etime = I('post.etime');

            //优惠券id
            $couponInfoId = I('post.couponInfoId');
            //代驾
            $drivers = I('post.drivers');
            //送车
            $givecar = I('post.givecar');
            $address = I('post.address');
            $Send = array(
                'drivers' => $drivers,
                'givecar' => $givecar,
            );
            //组装日期时间
            $time = array(
                'start' => $start . ' ' . $stime,
                'end' => $end . ' ' . $etime,
            );
            cookie('time', $time);
            //计算租车租时长
            $num = strtotime($time['end']) - strtotime($time['start']);
            $d = floor($num / 3600 / 24);
            $h = floatval($num / 3600);  //%取余
            $h = ceil($h);
            $h = $h % 24;
            $data = $d . "天" . $h . "小时<br>";

            $d += $this->sumData($h);

            //计算租价
            if ($d <= 4) {
                $total = round($rental[0]['shortdayprice'] * $d + $Send['drivers'] * $d + $Send['givecar']);//四舍五入取整 round（）
//                $total = number_format($total,2);//金额格式化保留两位小数； number_format（），第一个参数金额 第二个参数 保留小数位数
            }
            if ($d > 4 && $d <= 20) {
                $total = round($rental[0]['weekdayprice'] * $d + $Send['drivers'] * $d + $Send['givecar']);//四舍五入取整 round（）
//                $total = number_format($total,2);//金额格式化保留两位小数； number_format（），第一个参数金额 第二个参数 保留小数位数
            }
            if ($d > 20) {
                $total = round($rental[0]['monthdayprice'] * $d + $Send['drivers'] * $d + $Send['givecar']);//四舍五入取整 round（）
//                $total = number_format($total,2);//金额格式化保留两位小数； number_format（），第一个参数金额 第二个参数 保留小数位数
            }
            //判断车辆单价
            if ($d <= 4) {
                $univalent = $rental[0]['shortdayprice'];
            }
            if ($d > 4 && $d <= 20) {
                $univalent = $rental[0]['weekdayprice'];
            }
            if ($d > 20) {
                $univalent = $rental[0]['monthdayprice'];
            }
            //代驾方式
            if ($drivers) {
                $drive_state = 1;
            } else {
                $drive_state = 0;
            }

            //取车方式
            if ($givecar != '') {
                $pk_way = 2;
            } else {
                $pk_way = 1;
            }
            //判断用户类型
            if ($userid[0]['usertype'] == 0){
                $pre_price = $total;
                $ordertype = 0;
            } else {
                $pre_price = 0;
                $ordertype = 2;
            }

            //计算代驾费
            $daijia = round($drivers * $d);

            //查询优惠券信息
            if ($couponInfoId !=0){
                $tab = M();
                $sql = "SELECT use_limit,use_condition,discount,coupon_type,money FROM coupon_bollaruser WHERE id=%d";
                $aty = [$couponInfoId];
                $info = $tab->query($sql,$aty);
                if ($info[0]['use_limit'] !=2){             //判断优惠劵是否有使用限制
                    switch ($info[0]['use_limit']){
                        case 0:
                            if ($total >= $info[0]['use_condition']){
                                $reg = 1;                   //$reg=1表示该订单符合使用优惠劵的条件
                            }
                            break;
                        case 1:
                            if ($d >= $info[0]['use_condition']){
                                $reg = 1;
                            }
                            break;
                    }

                }

                if ($reg){
                    switch ($info[0]['coupon_type']){               //判断优惠劵的类型,并计算出优惠价格
                        case 0:
                            $total = $total - $info[0]['money'];
                            break;
                        case 1:
                            $total = $total * $info[0]['discount'];
                            break;
                    }
                }
            }else{
                $active = coupon_active(2,$user_id);        //查询可参与的活动
                if ($active['activity_num'] != 0){          //如果有活动可参与则传入下单时间,用车时间进行判定
                    $active_re = couponClass($active['activity'],Date('Y-m-d H:i:s'),$total,$time['start'],$time['end']);
                    if ($active_re[0]['coupon_pass']){
                        if ($active_re[0]['coupon_pass'][2]){   //如果该活动有直接减免的优惠,并且该订单符合该优惠条件,则改变订单总价
                            $total -= $active_re[0]['coupon_pass'][2]['specific'];
                            $grant_coupon = reliefCoupon($user_id,$active_re[0]['coupon'.$active_re[0]['coupon_pass'][2]['coupon_num']]);
                            $activity_id = $active_re[0]['id'];
                        }

                        foreach ($active_re[0]['coupon_pass'] as $key => $val){ //对符合条件的优惠方式进行循环
                            $grant[$key] = $active_re[0]['coupon'.$val['coupon_num']];
                        }

                    }

                }
            }

            //判断订单号并提交
            if (cookie('order') != null) {
                //生成订单号
                $orderdata = M('order');
                $ordernum = cookie('order');
                $order = [];
                $order['uid'] = $userid[0]['id'];//用户id
                $order['carmodelid'] = $id;//车型id
                $order['order_code'] = $ordernum;//订单号
                $order['order_date'] = Date('Y-m-d H:i:s');//下单时间
                $order['pk_date'] = $time['start'];//取车时间
                $order['re_date'] = $time['end'];//还车时间
                $order['u_price'] = $univalent;//单价
                $order['price_rec'] = $total;//应收金额
                $order['pre_price'] = $total;//预付金额
                $order['driver_price'] = $daijia;//代驾费
                $order['drive_state'] = $drive_state;//代驾方式
                $order['send_price'] = $Send['givecar'];//送车费
                $order['pk_way'] = $pk_way;//取车方式
                $order['order_state'] = 0;//订单状态：未支付
                $order['send_location'] = $address;//送车地址
                $order['coupon_id'] = $couponInfoId;//使用的优惠券id，



                $num = $this->order($ordernum);
                if (!$num) {
                    if ($couponInfoId !=0 && $reg == 1){
                        //修改已被使用的优惠券
                        $table = M();
                        $bollar = "UPDATE coupon_bollaruser SET use_type = 1 WHERE id = %d";
                        $bollarId = [$couponInfoId];
                        $table->execute($bollar,$bollarId);
                    }else if ($active_re[0]['coupon_pass']){
                        //发放优惠劵
                        $coupun_gent = grantCoupon($user_id,$grant);
                        if ($coupun_gent['add']){
                            if ($grant_coupon){
                                $grant_coupon .= ','.$coupun_gent['add'];
                            }else{
                                $grant_coupon = $coupun_gent['add'];
                            }

                            $activity_id = $active_re[0]['id'];
                        }
                    }

                    if ($activity_id){      //如果该订单参与了活动则记录参与的活动id
                        $order['activity'] = $activity_id;
                    }

                    if ($grant_coupon){     //如果该订单获得了优惠,则记录获得的id
                        $order['grant_coupon'] = $grant_coupon;
                    }


                    //判断 如果选择送车 送车地址不能为空
                    if ($givecar !=""){
                        if ($address == ""){
//                            echo "<script>alert('送车地址不能为空');history.back(-1);</script>'";
                            return false;
                        } else{
                            //生成订单
                            $orderdata->add($order);
                            //获取当前订单id
                            $orderid = $orderdata->order('id desc')->find();
                            //写入明细表
                            $cost = M('order_cost');
                            //生成明细订单
                            $costid = orderCostOrderCode();
                            $sql = "SELECT COUNT(id) FROM order_cost WHERE (trade_code='%s')";
                            $arr = [$costid];
                            $trade_code = $cost->query($sql,$arr);
                            if ($trade_code[0]['count(id)'] == 0){
                                $ordercost = array();
                                $ordercost['order_id'] = $orderid['id'];//订单id
                                $ordercost['trade_code'] = $costid;//明细订单号
                                $ordercost['charge_sum'] = $total;//收费金额
                                $ordercost['charge_road'] = 1;//收费途径；预付
                                $ordercost['charge_time'] = Date('Y-m-d H:i:s');//收费时间
                                $ordercost['order_class'] = $ordertype;//订单类型
                                $cost->add($ordercost);//生成交易订单

                            } else {
                                return false;
                            }

                        }
                    } else{
                        //生成订单
                        $orderdata->add($order);
                        //获取当前订单id
                        $orderid = $orderdata->order('id desc')->find();
                        //写入明细表
                        $cost = M('order_cost');
                        //生成明细订单
                        $costid = orderCostOrderCode();
                        $sql = "SELECT COUNT(id) FROM order_cost WHERE (trade_code='%s')";
                        $arr = [$costid];
                        $trade_code = $cost->query($sql,$arr);
                        if ($trade_code[0]['count(id)'] == 0){
                            $ordercost = array();
                            $ordercost['order_id'] = $orderid['id'];//订单id
                            $ordercost['trade_code'] = $costid;//明细订单号
                            $ordercost['charge_sum'] = $total;//收费金额
                            $ordercost['charge_road'] = 1;//收费途径；预付
                            $ordercost['charge_time'] = Date('Y-m-d H:i:s');//收费时间
                            $ordercost['order_class'] = $ordertype;//订单类型
                            $cost->add($ordercost);//生成交易订单

                        } else {
                            return false;
                        }
                    }
                }
                //获取当前订单id
                $orderid = $orderdata->order('id desc')->find();

                //获取当前订单号
                $order_code = "SELECT id,order_code,carmodelid FROM `order`  WHERE (id = %d)";
                $arr = [$orderid['id']];
                $ordercode = $orderdata->query($order_code, $arr);
                //订单号
                $this->assign('ordercode', $ordercode);
            }
            //当前用户
            $this->assign('user', $user);
            //租期
            $this->assign('data', $data);
            //订单总价
            $this->assign('total', $total);
            //传取还车时间
            $this->assign('time', $time);
            //代驾
            $this->assign('drive_state', $drive_state);
            //送车
            $this->assign('pk_way', $pk_way);
            //基本信息
            //判断图片文件是否存在
            if (file_exists("./Public".$rental[0]['frontimg'])){
//            检测图片文件是否可读
                if (is_readable("./Public".$rental[0]['frontimg'])){
                    $frontimg = $rental[0]['frontimg'];
                } else {
                    $frontimg = '';
                }

            } else {
                $frontimg = '';
            }
            $list[0]['frontimg'] = $frontimg;
            $this->assign('rental', $rental);
            $this->display();
        } else {
            $this->error('非法访问!', U('/'), 1);
        }
    }

    //阻止重复提交订单
    public function order($ordernum)
    {
        $orderdata = M();
        $sql = "SELECT COUNT(id) FROM `order`  WHERE order_code = '%s'";
        $ary = [$ordernum];
        $list = $orderdata->query($sql, $ary);
        if ($list[0]['count(id)'] > 0) {
            return true;
        } else {
            return false;
        }


    }

    //判断时间周期
    protected function sumData($h)
    {
        $d = 0;
        if ($h < 2) {
            $d = $d;
        } else {
            if ($h > 2 && $h <= 6) {
                $d += 0.5;
            } else {
                if ($h > 6) {
                    $d += 1;
                }
            }
        }
        return $d;
    }

    //取消订单
    public function cancelOrder()
    {
        $id = I('get.id');
        $order = M('order');
        $data = [];
        $data['order_state'] = 10;
        if ($order->where(array('id=' . $id))->save($data)) {
            cancelCoupon($id);  //修改订单获取的优惠信息
            $this->success('订单取消成功', U('UserManage/carOrder'), 1);
        } else {
            echo '<script>alert("订单取消失败");history.back(-1);</script>';
        }


    }

    //特惠车辆信息写订单方法 discount
    public function discount()
    {
        //接受当前用户
        $name = cookie('username');
        $user = authcode($name, 'DECODE', '123456');
        $member = M('work_member');
        //读取cookie接受当前用户
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone, 'DECODE', '123456');
        $uid = "SELECT id,usertype FROM work_member WHERE (phone = '" . $tel . "')";
        $userid = $member->query($uid);
        //车辆id
        $id = I('get.id');
        if (!empty($_POST)) {
            $info = M();
            //获取车辆id

            //获取订单计算信息
            $sql = "SELECT a.id,a.goodprice,a.agent_id,b.frontimg,b.carmodeltype,b.agestyle,a.carmodel,b.carmodelname,b.bearboxtype,b.sitecount,ROUND(((b.shortdayprice+b.weekdayprice+b.monthdayPrice)/3),0) as avgsum,c.brand FROM car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel = b.id LEFT JOIN car_barand c ON a.brand = c.id WHERE (a.id = %d)";
            $arr = [$id];
            $carinfo = $info->query($sql, $arr);
//            var_dump($carinfo);
            //取车日期
            $start = I('post.start');
            //取车时间
            $stime = I('post.stime');
            //还车日期
            $end = I('post.end');
            //还车时间
            $etime = I('post.etime');

            //代驾
            $drivers = I('post.drivers');
            //送车
            $givecar = I('post.givecar');
            $address = I('post.address');
            $Send = array(
                'drivers' => $drivers,
                'givecar' => $givecar,
            );
            //组装日期时间
            $time = array(
                'start' => $start . ' ' . $stime,
                'end' => $end . ' ' . $etime,
            );
            cookie('time', $time);
            //计算租车租时长
            $num = strtotime($time['end']) - strtotime($time['start']);
            $d = floor($num / 3600 / 24);
            $h = floatval($num / 3600);  //%取余
            $h = ceil($h);
            $h = $h % 24;
            $data = $d . "天" . $h . "小时<br>";
//            echo $data;
            $d += $this->sumData($h);
//            echo $d . "天";
            //计算租价
            $total = round($carinfo[0]['goodprice'] * $d + $drivers * $d);//四舍五入取整 round（）
//            $total = number_format($total,2);//金额格式化保留两位小数； number_format（），第一个参数金额 第二个参数 保留小数位数
            //判断车辆单价

            //代驾方式
            if ($drivers) {
                $drive_state = 1;
            } else {
                $drive_state = 0;
            }

            //取车方式
            if ($givecar != '') {
                $pk_way = 2;
            } else {
                $pk_way = 1;
            }
            //计算代驾费
            $daijia = round($drivers * $d);

            //判断用户类型
            if ($userid[0]['usertype'] == 0){
                $pre_price = $total;
                $ordertype = 0;
            } else {
                $pre_price = 0;
                $ordertype = 2;
            }


            //判断订单号并提交
            if (cookie('order') != null) {
                //生成订单号
                $orderdata = M('order');
                $ordernum = cookie('order');
                $order = [];
                $order['uid'] = $userid[0]['id'];//用户id
                $order['car_id'] = $id;//车辆id
                $order['carmodelid'] = $carinfo[0]['carmodel'];//车型id
                $order['order_code'] = $ordernum;//订单号
                $order['order_date'] = Date('Y-m-d H:i:s');//下单时间
                $order['pk_date'] = $time['start'];//取车时间
                $order['re_date'] = $time['end'];//还车时间
                $order['u_price'] = $carinfo[0]['goodprice'];//单价
                $order['price_rec'] = $total;//应收金额
                $order['pre_price'] = $pre_price;//预付金额
                $order['driver_price'] = $daijia;//代驾费
                $order['drive_state'] = $drive_state;//代驾方式
                $order['send_price'] = $Send['givecar'];//送车费
                $order['pk_way'] = $pk_way;//取车方式
                $order['order_state'] = 0;//订单状态：未支付
                $order['send_location'] = $address;//送车地址
                $order['agent_id'] = $carinfo[0]['agent_id'];//代理商id
//
                $num = $this->order($ordernum);
                if (!$num) {
                    //判断 如果选择送车 送车地址不能为空
                    if ($givecar !=""){
                        if ($address == ""){
//                            echo "<script>alert('送车地址不能为空');window.location.href = document.referrer;</script>'";
                            return false;
                        } else{
                            //生成订单
                            $orderdata->add($order);
                            //修改车辆状态
                            $car = M('car_carinfo');
                            $info = [];
                            $info['usestatus'] = 2;
                            $car->where(array('id'=>$id))->save($info);

                            //获取当前订单id
                            $orderid = $orderdata->order('id desc')->find();
                            //写入明细表
                            $cost = M('order_cost');
                            //生成明细订单
                            $costid = orderCostOrderCode();
                            $sql = "SELECT COUNT(id) FROM order_cost WHERE (trade_code='%s')";
                            $arr = [$costid];
                            $trade_code = $cost->query($sql,$arr);
                            if ($trade_code[0]['count(id)'] == 0){
                                $ordercost = array();
                                $ordercost['order_id'] = $orderid['id'];//订单id
                                $ordercost['trade_code'] = $costid;//明细订单号
                                $ordercost['charge_sum'] = $total;//收费金额
                                $ordercost['charge_road'] = 1;//收费途径；预付
                                $ordercost['charge_time'] = Date('Y-m-d H:i:s');//收费时间
                                $cost->add($ordercost);//生成交易订单
                            } else {
                                return false;
                            }
                        }
                    } else{
                        //生成订单
                        $orderdata->add($order);
                        $car = M('car_carinfo');
                        $info = [];
                        $info['usestatus'] = 2;
                        $car->where(array('id'=>$id))->save($info);

                        //获取当前订单id
                        $orderid = $orderdata->order('id desc')->find();
                        //写入明细表
                        $cost = M('order_cost');
                        //生成明细订单
                        $costid = orderCostOrderCode();
                        $sql = "SELECT COUNT(id) FROM order_cost WHERE (trade_code='%s')";
                        $arr = [$costid];
                        $trade_code = $cost->query($sql,$arr);
                        if ($trade_code[0]['count(id)'] == 0){
                            $ordercost = array();
                            $ordercost['order_id'] = $orderid['id'];//订单id
                            $ordercost['trade_code'] = $costid;//明细订单号
                            $ordercost['charge_sum'] = $total;//收费金额
                            $ordercost['charge_road'] = 1;//收费途径；预付
                            $ordercost['charge_time'] = Date('Y-m-d H:i:s');//收费时间
                            $ordercost['order_class'] = $ordertype;//订单类型
                            $ordercost['charge_time'] = Date('Y-m-d H:i:s');//收费时间
                            $cost->add($ordercost);//生成交易订单
                        } else {
                            return false;
                        }
                    }



                }
                //获取当前订单id
                $orderid = $orderdata->order('id desc')->find();

                //获取当前订单号
                $order_code = "SELECT id,order_code,car_id FROM `order`  WHERE (id = %d)";
                $arr = [$orderid['id']];
                $ordercode = $orderdata->query($order_code, $arr);
                //订单号
                $this->assign('ordercode', $ordercode);
            }
            //当前用户
            $this->assign('user', $user);
            //租期
            $this->assign('data', $data);
            //订单总价
            $this->assign('total', $total);
            //传取还车时间
            $this->assign('time', $time);
            //代驾
            $this->assign('drive_state', $drive_state);
            //送车
            $this->assign('pk_way', $pk_way);
            //判断图片文件是否存在
            if (file_exists("./Public".$carinfo[0]['frontimg'])){
//            检测图片文件是否可读
                if (is_readable("./Public".$carinfo[0]['frontimg'])){
                    $frontimg = $carinfo[0]['frontimg'];
                } else {
                    $frontimg = '';
                }

            } else {
                $frontimg = '';
            }
            $carinfo[0]['frontimg'] = $frontimg;
            //基本信息
            $this->assign('rental', $carinfo);
            $this->display();
        } else {
            $this->error('非法访问!', U('/'), 1);
        }
    }

    //优惠车辆取消订单
    public function cancelCarOrder() {
        $id = I('get.id');
        //更改订单状态
        $order = M('order');
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
            if ( $carinfo->where(array('id='.$carid[0]['car_id']))->save($car)){
                $this->success('订单取消成功', U('UserManage/carOrder'), 1);
            }

        } else {
            echo '<script>alert("订单取消失败");history.back(-1);</script>';
        }
    }


}