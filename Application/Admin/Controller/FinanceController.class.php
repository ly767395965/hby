<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
use Think\Page;

/**
 * Class CarController
 * @package Admin\Controller
 *网站后台财务管理操作控制器
 */
class FinanceController extends BaseController {

    //订单管理列表页面
//    public function Index(){
//        if(isset($_GET['select'])){
//            $select = I('get.select');
//            $select_time = I('get.select_time');
//            $startDate = I('get.start');
//            $stopDate = I('get.stop');
//            $key = I('get.key');
//            $msg['select'] = $select;
//            $msg['select_time'] = $select_time;
//            $msg['start'] = $startDate;
//            $msg['stop'] = $stopDate;
//            $msg['key'] = $key;
//            $this->assign('msg',$msg);
//            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'Index');
//        }else{
//            $this->q_query();
//        }
//    }

    //    订单管理方法
    public function financecost(){
        $m = M();
        if (isset($_GET['big'])){
            $this->assign('big',$_GET['big']);
        }
        if (isset($_GET['action'])){
            $id = I('get.id');
            $sql = "SELECT a.*,b.carmodelname,b.shortdayprice,b.weekdayprice,b.monthdayprice,b.barandid,c.username,c.phone,d.carno,e.id as driverid,e.drivername,e.phone as driverphone FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id LEFT JOIN car_driverinfo e ON a.be_driver=e.id WHERE (a.is_del=0)  AND (a.id=%d)";
            $ary = array($id);
            $orderinfo = $m->query($sql,$ary);
            $orderinfo = $orderinfo[0];
            $action = I('get.action');
            switch ($action){
                case 'send' :
                    $barand = $m->table('car_barand')->field('id,brand')->select();
                    $this->assign('barand',$barand);
                    break;
                case 'driver' :
                    $where2['state'] = 0;
                    $alldriver = $m->table('car_driverinfo')->field('id,drivername,phone,cost')->where($where2)->select();
                    $this->assign('alldriver',$alldriver);
                    break;
            }
            $this->assign('action',$action);
            $this->assign('list',$orderinfo);
            if ($action=='charge' || $action=='charge_two'){   //收取预付和补交预付
                $this->display('Order/edit');
            }else if ($action == 'max_return_car') {

                $this->display('Finance/max_endCost');
            } else{
                $this->display('Finance/endCost');
            }
        }else{
            $order_state = "AND ((a.order_state = 0) OR (a.order_state = 4) OR (a.order_state = 6) OR (a.order_state = 12) OR ((a.order_state < 4) AND (a.deposit != 0) AND (a.get_deposit = 0))) AND (c.check_cycle = 0)";
            $themes = 'financeCost';
            if (isset($_REQUEST['act'])){
                $act = I('request.act');
                switch ($act){
                    case 'charge':
                        //sql条件（（结账状态等于2）或（订单状态小于等于4 且 应付减实付大于0）或 （订单状态等于4）或 （订单状态小于4 且 押金不为0 且 押金未付））且（客户类型为普通）
                        $order_state = "AND ((a.check_out = 2) OR ((a.order_state <= 3) AND ((a.price_rec) - (a.collections_rec) > 0)) OR (a.order_state = 4) OR ((a.order_state < 4)  AND (a.get_deposit = 0) AND (drive_state = 0))) AND (c.usertype = 0)";
                        break;
                    case 'refund':
                        $order_state = "AND ((a.order_state = 6) OR (a.order_state = 12)) AND (c.check_cycle = 0)";
                        break;
                    case 'charge_q':
                        $order_state = "AND ((a.check_out >0) OR (a.order_state = 7))";
                        $themes = 'Finance/charge_q';
                        break;
                    default:
                }
            }
            $this->assign('act',$act);
            if(isset($_GET['select'])){
                $select = I('get.select');
                $select_time = I('get.select_time');
                $startDate = I('get.start');
                $stopDate = I('get.stop');
                $key = I('get.key');
                if ($select == 6){
                    $key = I('get.key_check');
                }elseif ($select == 3){
                    $key = I('get.key_state');
                }
                $msg['select'] = $select;
                $msg['select_time'] = $select_time;
                $msg['start'] = $startDate;
                $msg['stop'] = $stopDate;
                $msg['key'] = $key;
                $this->assign('msg',$msg);
                $this->q_query($select_time,$startDate,$stopDate,$select,$key,$themes,$order_state,0);
            }else{
                $this->q_query(3,0,0,100,0,$themes,$order_state,0);
            }
        }
    }

    //大客户收费模板数据信息
    public function max_charge(){
        $tab = M();
        $id = I('get.id');
        $sql = "SELECT a.*,b.username,b.phone,d.carno
FROM `order` a LEFT JOIN work_member b ON a.uid=b.id LEFT JOIN order_car c ON a.id=c.carid LEFT JOIN car_carinfo d ON c.carid=d.id 
WHERE (a.is_del=0) AND (a.id=%d)";
        $list = $tab->query($sql,[$id]);
        $this->assign('list',$list[0]);

        $this->display('charge');
    }

    //大客户收取预付
    public function order_charge(){
        $tab = M();
        $tab->startTrans();
        $cost = orderCostOrderCode();//交易订单号
        $id = I('post.id');
        $pre_price = I('post.pre_price');//预付金额
        $pay_way = I('post.pay_way');//支付方式
        $remarks = I('post.remarks');//备注

        //查询当前订单交易信息
        $ordercodesql = "SELECT id,charge_sum FROM order_cost WHERE order_id = %d AND pay_way=%d ";
        $codeinfo = $tab->query($ordercodesql,[$id,0]);

        $sql = "UPDATE `order` SET collections_rec=collections_rec+%d,pre_price=pre_price+%d,check_out=%d,remarks='%s' WHERE id=%d";
        $order = $tab->execute($sql,[$pre_price,$pre_price,2,$remarks,$id]);

        if ($codeinfo[0]['charge_sum'] == $pre_price){
            $ordercost1 = "UPDATE order_cost SET pay_way=%d,charge_time='%s',charge_road=%d WHERE id=%d";
            $order_cost = $tab->execute($ordercost1,[$pay_way,date('Y-m-d H:i:s',time()),1,$codeinfo[0]['id']]);
            $ordercost_re = 1;
        }else {
            //计算交易金额
            $charge_sum = round($codeinfo[0]['charge_sum'] - $pre_price);

            $ordercosts = "UPDATE order_cost SET pay_way=%d,charge_time='%s',charge_sum=%d,charge_road=%d WHERE id=%d";
            $order_cost = $tab->execute($ordercosts,[$pay_way,date('Y-m-d H:i:s',time()),$pre_price,1,$codeinfo[0]['id']]);
            //新增未结完账的数据
            $order_add = "INSERT INTO order_cost (order_id,order_class,trade_code,charge_sum,charge_road,charge_time,pay_way) VALUES (%d,%d,'%s',%d,%d,'%s',%d)";
            $ordercost_re = $tab->execute($order_add,[$id,2,$cost,$charge_sum,1,date('Y-m-d H:i:s',time()),0]);
        }
        if ($order > 0 && $order_cost > 0 && $ordercost_re >0){
            $tab->commit();
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $id, 'order_charge', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
                exit('<script type="text/javascript">alert("收取预付成功!");location.href="'.U('Finance/financeBig').'";</script>');
            }

        } else {
            $tab->rollback();
            exit('失败');
        }



    }



    //财务收费操作方法
    public function cost(){
        if (isset($_POST['action'])){
            switch ($_POST['action']) {
                case 'return_car':                      //结账操作
                    $this->return_car($_POST);
                    break;
                case 'charge':                          //收费操作
                    $this->charge($_POST);
                    break;
                case 'charge_two':                      //补收预付操作
                    $this->charge($_POST);
                    break;
                case 'fill_price':                      //收账操作
                    $this->fill_price($_POST);
                    break;
                case 'refund':                          //退款操作
                    $this->refund($_POST);
                    break;
                case 'get_deposit':                     //收取押金操作
                    $this->get_deposit($_POST);
                    break;
                case 'end':                             //退还押金操作
                    $this->end($_POST);
                    break;
            }
        }
    }
    //大客户账目管理方法
    public function financeBig(){
        $order_state = " AND (c.usertype = 1 AND (a.order_state < 8 AND (a.check_out = 2 OR a.check_out = 0))) ";
        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $msg['select_time'] = $select_time;
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'financeBig',$order_state,0);
        }else{
            $startDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']-2592000);
            $stopDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $this->q_query(0,$startDate,$stopDate,100,0,'financeBig',$order_state,0);
        }
    }

    //会议账目管理方法
    public function financeMeeting(){
        $m = M();
        $time = date("Y-m-d",time()); //当前时间
        $this->assign('time',$time);
        $order_state = " AND (order_state > 0) AND (order_state < 10)";
        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $msg['select_time'] = $select_time;
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->meet_query($select_time,$startDate,$stopDate,$select,$key,'financeMeeting',$order_state,0);
        }else{
            $startDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']-2592000);
            $stopDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $this->meet_query(0,$startDate,$stopDate,100,0,'financeMeeting',$order_state,0);
        }
    }

    //会议订单结账、收账
    public function meetingCost(){
        $m = M();
        if (empty($_POST)){
            $where['id'] = I('get.id');
            $orderinfo = $m->table('order_meeting')->where($where)->find();
            $orderinfo['action'] = I('get.action');
            $this->assign('list',$orderinfo);
            $this->display('meetingCost');
        }else{
            $this->checkout($_POST);
        }
    }
//    统计报表方法
    public function count(){

        if (isset($_POST['start'])){
            if ($_POST['start'] != null){
                $msg['start'] = $key['start'] = I('post.start');
            }else{
                $key['start'] = '2017-01-01';
            }
            if ($_POST['stop'] != null){
                $msg['stop'] = $key['stop'] = I('post.stop');
            }else{
                $key['stop'] = date("Y-m-d",time());
            }
            $this->assign('key',$msg);
            if ($_POST['stop'] != null){
                $key['stop'] = date("Y-m-d", strtotime("+1 day", strtotime($key['stop'])));
            }

        }
//        switch ($road){                                     //只有axjx传值时$road才会有值
//            case 'trade':
//                $tradeinfo = $this->trade_count($key);      //财务信息统计
////                $tradeinfo['time'] = $key;
//                echo json_encode($tradeinfo);
//                break;
//            case 'order':
//                $orderinfo = $this->order_cout($key);       //订单信息统计
////                $tradeinfo['time'] = $key;
//                echo json_encode($orderinfo);
//                break;
//            default:
        //财务信息统计
        $tradeall = $this->trade_count($key);
        //订单信息统计
        $orderinfo = $this->order_cout($key);
        //车辆信息统计
        $carinfo = $this->car_count();
        //会员信息统计
        $memberinfo = $this->member_count();
        $this->assign('tradeall',$tradeall);                  //交易记录
        $this->assign('orderinfo',$orderinfo);                  //订单记录
        $this->assign('carinfo',$carinfo);                      //车辆记录
        $this->assign('memberinfo',$memberinfo);                //车辆记录
        $this->display('finance/count');
//        }
    }
    //统计详情方法
    public function  count_info(){
        if ($_GET){
            $key = I('get.key');
            $cond['select'] = I('get.select');
            $cond['start'] = I('get.start');
            $cond['stop'] = I('get.stop');

            $this->assign('key',$key);
            $this->order_info($cond);
            $this->display();
        }
        if (isset($_POST['select'])){
            $key['select'] = I('post.select');
            $key['start'] = I('post.start');
            $key['stop'] = I('post.stop');
        }
    }
    //导出excel方法
    public function excel(){
        if (isset($_GET['act'])){
            $act = I('get.act');
            $order_all = $_SESSION['excel_all'];
            if ($act == 'look'){
                switch ($order_all['select']){
                    case 0:
                        $order_all['xlsName'] = '财务详情'.date("YmdHis",time());
                        break;
                    case 1:
                        $order_all['xlsName'] = '普通订单财务详情'.date("YmdHis",time());
                        break;
                    case 2:
                        $order_all['xlsName'] = '大客户订单财务详情'.date("YmdHis",time());
                        break;
                    case 3:
                        $order_all['xlsName'] = '正常订单财务详情'.date("YmdHis",time());
                        break;
                    case 4:
                        $order_all['xlsName'] = '会议订单财务详情'.date("YmdHis",time());
                        break;
                    default:
                        $order_all['xlsName'] = '财务详情'.date("YmdHis",time());
                }
                $this->assign('list',$_SESSION['excel']);
                $this->assign('order_all',$order_all);
                $this->display();
            }else{
                $xlsName = I('get.xlsName');
                $title['all'] = array(
                    array('type','订单类型'),
                    array('order_code','订单号'),
                    array('carno','车辆号牌'),
                    array('order_date','下单日期'),
                    array('pk_date','用车日期'),
                    array('re_date','还车日期'),
                    array('duration','使用天数'),
                    array('u_price','租车单价'),
                    array('out_cost','车辆成本'),
                    array('oil_price','油费'),
                    array('tolls','过路费'),
                    array('wash_price','洗车费'),
                    array('re_price','维修费'),
                    array('in_cost','开票成本'),
                    array('mixed_cost','会议杂项成本'),
                    array('cost_all','成本和'),
                    array('collections_rec','实收金额'),
                    array('check_cost','未结账目'),
                    array('profit','净收入'),
                );
                $title['plain'] = array(
                    array('type','订单类型'),
                    array('order_code','订单号'),
                    array('carno','车辆号牌'),
                    array('order_date','下单日期'),
                    array('pk_date','用车日期'),
                    array('re_date','还车日期'),
                    array('duration','使用天数'),
                    array('u_price','租车单价'),
                    array('out_cost','车辆成本'),
                    array('oil_price','油费'),
                    array('tolls','过路费'),
                    array('wash_price','洗车费'),
                    array('re_price','维修费'),
                    array('in_cost','开票成本'),
                    array('cost_all','成本和'),
                    array('collections_rec','实收金额'),
                    array('check_cost','未结账目'),
                    array('profit','净收入'),
                );
                $title['meet'] = array(
                    array('type','订单类型'),
                    array('order_code','订单号'),
                    array('carno','用车数量'),
                    array('order_date','下单日期'),
                    array('pk_date','用车日期'),
                    array('re_date','还车日期'),
                    array('duration','使用天数'),
                    array('out_cost','车辆成本'),
                    array('mixed_cost','会议杂项成本'),
                    array('cost_all','成本和'),
                    array('collections_rec','实收金额'),
                    array('check_cost','未结账目'),
                    array('profit','净收入'),
                );
                switch ($order_all['select']){
                    case '0':
                        $xlsCell = $title['all'];
                        break;
                    case '1':
                        $xlsCell = $title['plain'];
                        break;
                    case '2':
                        $xlsCell = $title['plain'];
                        break;
                    case '3':
                        $xlsCell = $title['plain'];
                        break;
                    case '4':
                        $xlsCell = $title['meet'];
                        break;
                    default:
                        $xlsCell = $title['all'];
                }
                $count_all = $_SESSION['excel_all'];
                $count_all['type'] = '总计';
                $count_all['order_code'] = '订单总数：'.$count_all['order_num'];
                $str = '';
                if (strlen($count_all['term']) < 39){
                    $str = $count_all['term'];
                }else{
                    for ($i=0;$i<=strlen($count_all['term']);$i++){
                        if ($i>1 && $i<10){
                            $str .= $count_all['term'][$i];
                        }elseif ($i == 11){
                            $str .= '号 至 ';
                        }elseif ($i>27 && $i<36){
                            $str .= $count_all['term'][$i];
                        }elseif ($i == 36){
                            $str .= '号';
                        }
                    }
                }
                $count_all['order_date'] = $str;
                $xlsData = $_SESSION['excel'];
                $xlsData[] = $count_all;
                foreach ($xlsData as $key => $value){                 //在订单号后添加一个空格，为了写入excel后数据已文本形式显示
                    $xlsData[$key]['order_code'] = $value['order_code'].' ';
//                    foreach ($arr as $key_one => $value){
//                        $xlsData[$key][$key_one] = (string)$value.' ';
//                    }
                }
                $this->exportExcel($xlsName,$xlsCell,$xlsData);
            }
        }
    }
//    收费方法
    public function charge($key){
        $m = M();
        $_POST = $key;
        $rules = array(
            array('pk_date','require','取车时间不能为空！',0), //默认情况下用正则进行验证
            array('re_date','require','还车时间不能为空！',0), //默认情况下用正则进行验证
            array('u_price','require','车辆单价不能为空！',0), //默认情况下用正则进行验证
            array('pre_price','require','预付金额不能为空！',0), //默认情况下用正则进行验证
        );
        if (!$m->table('order')->validate($rules)->create()){
            exit($m->table('order')->getError());
        }else{
            $order_cost['order_id'] = $where_cost['order_id'] = $where['id'] = I('post.id');               //将收费金额写入收费表中
            $where_cost['pay_way'] = array('eq','0');
            $where_cost['order_class'] = array('neq','1');
            $costinfo = $m->table('order_cost')->where($where_cost)->find();                               //查看该订单是否有未支付交易记录
            if ($_POST['big'] == 1){                                                                       //判断是否为大客户订单，并写入交易记录表的订单类型
                $order_cost['order_class'] = 2;
            }else{
                $order_cost['order_class'] = 0;
            }
            $order_cost['charge_sum'] = $data['pre_price'] = I('post.pre_price');//获取预付金额
            if ($_POST['action'] == 'charge_two'){
                $order_cost['charge_sum'] = I('post.pre_price_two');             //获取补交的预付金额
                $data['pre_price'] += $order_cost['charge_sum'];
            }
            $order_cost['charge_road'] = 1;                                      //收费记录表的收费途径，1为预付金额

            $order_cost['charge_time'] = $time = date("Y-m-d H:i:s",time());     //收费时间，当前时间
            $order_cost['pay_way'] = I('post.pay_way');;
            $data['collections_rec'] = $data['pre_price'];
            $data['remarks'] = I('post.remarks');                                //备注
            $order_state = I('post.order_state');
            if ($order_state < 1){
                $data['order_state'] = 1;
            }
            //将消费产生的积分写入用户表
            $score = $order_cost['charge_sum'];
            $order_id = I('post.id');
            $this->score($order_id,$score);

            $charge = $m->table('order')->where($where)->save($data);
            if ($charge){
                $sql = " SELECT grant_coupon FROM `order` WHERE id = %d";
                $order_coupon = M()->query($sql,[$where['id']]);
                if ($order_coupon){ //判定该订单获取的优惠劵是否还是不可使用状态
                    couponUserState($where['id'],0);//修改该订单获取的优惠劵状态为未使用
                }

                if ($order_cost['charge_sum'] != 0){
                    if ($costinfo){
                        $m->table('order_cost')->where($where_cost)->save($order_cost);
                    }else{
                        $order_cost['trade_code'] = orderCostOrderCode();            //交易订单号
                        $m->table('order_cost')->add($order_cost);
                    }
                }
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog('order', $where['id'], 'charge', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                    if ($_POST['big'] == 1){
                        exit('<script type="text/javascript">alert("收费成功");location.href="'.U('Finance/financebig').'";</script>');
                    }else{
                        exit('<script type="text/javascript">alert("收费成功");location.href="'.U('Finance/financecost?act=charge').'";</script>');
                    }

                }
            }else{
                exit('<script type="text/javascript">alert("收费失败");history.go(-1);</script>');
            }
        }
    }
//    结账操作方法
    public function return_car($key){
        $m = M();
        $_POST = $key;
        $order_cost['order_id'] = $where_cost['order_id'] = $where['id'] = I('post.id');
        $where_cost['pay_way'] = array('eq','0');
        $where_cost['order_class'] = array('neq','1');
        $costinfo = $m->table('order_cost')->where($where_cost)->find();
        $order_state = $m->table('order')->field('order_state')->where($where)->find(); //通过id查看订单状态
        if ($order_state['order_state'] == 4){                                          //如果订单为4（已还车）
            $data['order_state'] = 5;                                                   //则在结账后把状态改为5
        }
        if ($order_state['order_state'] == 6){                                          //如果订单为6（可退押金状态）
            $data['order_state'] = 7;                                                   //则在结账后把状态改为7
        }
        $data['price_rec'] = I('post.price_rec');
        $data['collections_rec'] = I('post.collections_rec');
        $data['price_paided'] = I('post.price_paided');
        $data['check_out'] = I('post.check_out');
        $data['is_invoice'] = I('post.is_invoice');           //是否开票
        if ($data['is_invoice'] == 1){
            $data['in_code'] = I('post.in_code');
            $data['in_price'] = I('post.in_price');
            $data['in_cost'] = I('post.in_cost');
            $data['in_dep'] = I('post.in_dep');
        }
        $endcost = $m->table('order')->where($where)->save($data);
        if ($endcost!==false){
            $sql = " SELECT b.use_type FROM `order` a LEFT JOIN coupon_bollaruser b ON a.grant_coupon = b.id WHERE a.id = %d";
            $order_coupon = M()->query($sql,[$where['id']]);
            if ($order_coupon[0]['use_type'] == 3){ //判定该订单获取的优惠劵是否还是不可使用状态
                couponUserState($where['id'],0);//修改该订单获取的优惠劵状态为未使用
            }

            $order_cost['charge_sum'] = $data['price_paided'];                          //将结账费用记录到收费表中
            if ($order_cost['charge_sum'] != 0){
                $order_cost['charge_road'] = 2;                                         //收费记录表的收费途径，2为结账金额
                $order_cost['pay_way'] = I('post.pay_way');
                $order_cost['trade_code'] = orderCostOrderCode();                       //交易订单号
                $order_cost['charge_time'] = $time = date("Y-m-d H:i:s",time());
                if ($_POST['big'] == 1){                                                //判断订单类型
                    $order_cost['order_class'] = 2;
                }else{
                    $order_cost['order_class'] = 0;
                }
                if ($costinfo){
                    $m->table('order_cost')->where($where_cost)->save($order_cost);
                }else{
                    $order_cost['trade_code'] = orderCostOrderCode();                   //交易订单号
                    $m->table('order_cost')->add($order_cost);
                }
                //将消费产生的积分写入用户表
                $score = $order_cost['charge_sum'];
                $order_id = I('post.id');
                $this->score($order_id,$score);
            }

            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'return_cost', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                if ($_POST['big']==1){
                    exit('<script type="text/javascript">alert("结帐完成，状态改变");location.href="'.U('Finance/financeBig').'";</script>');
                }
                exit('<script type="text/javascript">alert("结帐完成，状态改变");location.href="'.U('Finance/financecost?act=charge').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("结帐失败");history.go(-1);</script>');
        }
    }
//    收账操作方法
    public function fill_price($key){
        $m = M();
        $_POST = $key;
        $where['id'] = I('post.id');
        $order_state = $m->table('order')->field('order_state')->where($where)->find(); //通过id查看订单状态
        $data['collections_rec'] = I('post.collections_rec');
        $data['check_out'] = I('post.check_out');
        $fill_price = I('post.fill_price');
        $data['price_paided'] = I('post.price_paided');
        $data['price_paided'] = $data['price_paided'] + $fill_price;
        $data['remarks'] = I('post.remarks');           //备注
        /*if ($_POST['big'] == 1){
            if ($order_state['order_state'] == 6 ){                                          //如果订单为4（已还车）
                $data['order_state'] = 7;                                                   //则在结账后改为结单状态
            }
        }*/
        $endcost = $m->table('order')->where($where)->save($data);
        if ($endcost!==false){
            $order_cost['order_id'] = $where['id'] = I('post.id');
            $order_cost['charge_sum'] = $fill_price;
            $order_cost['trade_code'] = orderCostOrderCode();                    //交易订单号
            $order_cost['charge_road'] = 3;                                                 //收费记录表的收费途径，3为收账金额
            $order_cost['charge_time'] = date("Y-m-d H:i:s",time());
            $order_cost['pay_way'] = I('post.pay_way');                                     //收费记录表的收费途径，3为收账金额
            if ($_POST['big'] == 1){                                                //判断订单类型
                $order_cost['order_class'] = 2;
            }else{
                $order_cost['order_class'] = 0;
            }
            $m->table('order_cost')->add($order_cost);
            //将消费产生的积分写入用户表
            $score = $order_cost['charge_sum'];
            $order_id = I('post.id');
            $this->score($order_id,$score);
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'fill_price', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                if ($_POST['big']==1){
                    exit('<script type="text/javascript">alert("收帐完成，所收金额以加入补收金额中");location.href="'.U('Finance/financeBig').'";</script>');
                }
                exit('<script type="text/javascript">alert("收帐完成，所收金额以加入补收金额中");location.href="'.U('Finance/financecost?act=charge').'";</script>');
            }

        }else{
            exit('<script type="text/javascript">alert("收帐失败");history.go(-1);</script>');
        }
    }
//    退款操作方法
    public function refund($key){
        $m = M();
        $_POST = $key;
        $car_id['id'] =  I('post.car_id');                              //修改车辆状态，因为可退款的订单是所有为取车的订单（包括已派车的）
        $order_car['car_id'] = $car_id['id'];
        if ($car_id['id']){
            $order_car['isdel'] = 0;
            $order_car['order_state'] =array('lt','4');
            $car_sum = $m->table('order')->where($order_car)->select();
            if (count($car_sum) == 0){
                $car['usestatus'] = 0;
                $m->table('car_carinfo')->where($car_id)->save($car);
            }
        }
        $driver['id'] = I('post.be_driver');                            //同上改变代驾人状态
        if ($driver['id']){
            $driver['isdel'] = 0;
            $driver_state['state'] = 0;
            $m->table('car_driverinfo')->where($driver)->save($driver_state);
        }

        $where['id'] = I('post.id');
        $data['order_state'] = 13;
        $data['remarks'] = I('post.remarks');           //备注
        $endcost = $m->table('order')->where($where)->save($data);
        if ($endcost!==false){
            cancelCoupon($where['id']); //退款成功,修改该订单获得的优惠劵为不可用

            $cost['order_id'] = $where['id'];                               //将收费记录写入收费表中
            $cost['charge_sum'] = -I('post.refund_sum');
            $cost['charge_time'] = date("Y-m-d H:i:s",time());
            $cost['trade_code'] = orderCostOrderCode();                    //交易订单号
            $cost['charge_road'] = 4;                                       //收费途径，4为退款
            $cost['pay_way'] = I('post.refund_way');
            //将消费产生的积分写入用户表
            $score = $cost['charge_sum'];
            $order_id = I('post.id');
            $this->score($order_id,$score);
            $m->table('order_cost')->add($cost);
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'refund', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("退款完成，订单状态改变");location.href="'.U('Finance/financecost?act=refund').'";</script>');
            }

        }else{
            exit('<script type="text/javascript">alert("操作失败");history.go(-1);</script>');
        }
    }
//    收取押金操作方法
    public function get_deposit($key){
        $m = M();
        $_POST = $key;
        $where['id'] = I('post.id');
        $data['get_deposit'] = 1;
        $data['deposit'] = I('post.deposit');
        $data['remarks'] = I('post.remarks');           //备注
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){
            $order_cost['order_id'] = $where['id'];                               //将收费记录写入收费表中
            $order_cost['charge_sum'] = I('post.deposit_a');
            $order_cost['charge_time'] = date("Y-m-d H:i:s",time());
            $order_cost['trade_code'] = orderCostOrderCode();                    //交易订单号
            $order_cost['charge_road'] = 5;                                       //收费途径，5为收取违章押金
            $order_cost['pay_way'] = I('post.deposit_way');                       //收取押金方式
            $m->table('order_cost')->add($order_cost);
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'deposit_cost', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("收取押金完成！");location.href="'.U('Finance/financecost?act=charge').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("收取押金操作失败");history.go(-1);</script>');
        }
    }
//    退还押金，结单操作方法
    public function end($key){
        $m = M();
        $_POST = $key;

        $data['remarks'] = I('post.remarks');                                    //备注
        $where['id'] = $where_cost['order_id'] = I('post.id');
        $where_cost['pay_way'] = array('eq','0');
        $where_cost['order_class'] = array('neq','1');
        $costinfo = $m->table('order_cost')->where($where_cost)->find();

        $deposit_a = I('post.deposit_a');                                        //违章押金
        $dp_price = I('post.dp_price');                                          //违章金额
        $deduction_dep = I('post.deduction_dep');                                //押金抵押账款金额
//        if (($deposit_a - $dp_price - $deduction_dep)<0){
//            exit('<script type="text/javascript">alert("扣除金额以超出可扣除上限！");history.go(-1);</script>');
//        }
        $data['order_state'] = 7;
        $star_check = I('post.star_check');                                      //获取该订单初始的结账状态
        if ($star_check == 2){                                                   //订单初始状态为未结清，则更新补交金额以及结账状态
            $data['price_paided'] = I('post.price_paided');
            $data['collections_rec'] = I('post.collections_rec');
            $data['price_paided'] = $data['price_paided'] + $deduction_dep;
            $data['check_out'] = I('post.check_out');
        }
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){
            $order_cost_a['order_id'] = $where['id'];   //将收费记录写入收费表中

            $order_cost_a['charge_sum'] = -($deposit_a - $dp_price - $deduction_dep);//退还押金金额
            $order_cost_a['trade_code'] = orderCostOrderCode();                      //交易订单号
            $order_cost_a['charge_time'] = date("Y-m-d H:i:s",time());               //记录录入的时间（当前时间）
            if ($order_cost_a['charge_sum'] > 0){
                $order_cost_a['charge_road'] = 7;                                        //收费途径，7为补收违章押金
            }else{
                $order_cost_a['charge_road'] = 6;                                        //收费途径，6为退还违章押金
            }
            $order_cost_a['pay_way'] = I('post.deposit_way');                        //退还押金方式
            if ($deduction_dep != 0){
                $order_cost_b['order_id'] = $where['id'];
                $order_cost_b['charge_sum'] = $deduction_dep;
                $order_cost_b['charge_time'] = date("Y-m-d H:i:s",time());
                $order_cost_b['trade_code'] = orderCostOrderCode();                 //交易订单号
                $order_cost_b['charge_road'] = 3;                                   //收费途径，3为补收
                $order_cost_b['pay_way'] = 4;                                       //收取方式，4为抵扣押金

                //将消费产生的积分写入用户表
                $score = $order_cost_b['charge_sum'];
                $order_id = I('post.id');
                $this->score($order_id,$score);
            }
            if ($costinfo){
                $m->table('order_cost')->where($where_cost)->save($order_cost_b);
            }else{
                $m->table('order_cost')->add($order_cost_b);
            }
            $m->table('order_cost')->add($order_cost_a);
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order',$where['id'], 'deposit_refund', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("退还押金完成，以结单");location.href="'.U('Finance/financecost?act=refund').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("退还押金操作失败");history.go(-1);</script>');
        }
    }
    //会议订单结账方法
    public function checkout($key){
        $m = M();
        $_POST = $key;

        $rules = array(
            array('in_code','require','发票号为必填项！',0), //默认情况下用正则进行验证
            array('in_price','require','开票金额为必填项！',0), //默认情况下用正则进行验证
            array('in_cost','require','开票成本为必填项！',0), //默认情况下用正则进行验证
            array('in_dep','require','开票单位为必填项！',0), //默认情况下用正则进行验证
        );

        $act = $_POST['action'];
        $where['id'] = I('post.id');
        $data['mixed_cost'] = I('post.mixed_cost');                                   //杂项成本
        $data['collections_rec'] = I('post.collections_rec');                         //补收金额
        $price_paided = I('post.price_paided');                                       //尾款金额
        $data['remarks'] = I('post.remarks');                                         //备注
        $price_rec = I('post.price_rec');
        $check_state = I('post.check_state');
        if (($price_rec - $data['collections_rec']) <= 0 || $check_state == 1){
            $data['order_state'] = 3;
        }else{
            $data['order_state'] = 2;
        }
        $data['is_invoice'] = I('post.is_invoice');                                 //判断是否开票
        if ($data['is_invoice'] == 1){
            if (!$m->table('order_meeting')->validate($rules)->create()){
                exit($m->table('order_meeting')->getError());
            }else{
                $data['in_code'] = I('post.in_code');
                $data['in_price'] = I('post.in_price');
                $data['in_cost'] = I('post.in_cost');
                $data['in_dep'] = I('post.in_dep');
            }

        }
        $checkout = $m->table('order_meeting')->where($where)->save($data);
        if ($checkout){
            $cost['order_id'] = $where['id'];                                         //将收费情况写入收费表中
            $cost['order_class'] = 1;
            if ($act == 'checkout'){                                                 //不同的操作，收取的费用不一样
                $cost['charge_sum'] = $data['collections_rec'];
            }else{
                $cost['charge_sum'] = $price_paided;
            }
            $cost['charge_road'] = 2;
            $cost['trade_code'] = orderCostOrderCode();                    //交易订单号
            $cost['charge_time'] = date("Y-m-d H:i:s",time());
            $cost['pay_way'] = I('post.pay_way');
            $m->table('order_cost')->add($cost);

            $auserInfo = UserInfo();
            $log = self::writeLog('order_meeting', $where['id'], 'checkout', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("结帐完成，状态改变");location.href="'.U('Finance/financeMeeting').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("结帐失败");history.go(-1);</script>');
        }
    }

    //会议订单收账方法
    public function meeting_price($key){
        $m = M();
        $_POST = $key;
        $where['id'] = I('post.id');
        $data['collections_rec'] = I('post.collections_rec');
        $price_rec = I('post.price_rec');
        $check_state = I('post.check_state');
        if (($price_rec - $data['collections_rec']) <= 0 || $check_state == 1){
            $data['order_state'] = 3;
        }else{
            $data['order_state'] = 2;
        }
        $checkout = $m->table('order_meeting')->where($where)->save($data);
        if ($checkout){
            $cost['order_id'] = $where['id'];                                         //将收费情况写入收费表中
            $cost['order_class'] = 1;
            $cost['charge_sum'] = $data['collections_rec'];
            $cost['charge_road'] = 2;
            $cost['trade_code'] = orderCostOrderCode();                    //交易订单号
            $cost['charge_time'] = date("Y-m-d H:i:s",time());
            $cost['pay_way'] = I('post.pay_way');
            $m->table('order_cost')->add($cost);

            $auserInfo = UserInfo();
            $log = self::writeLog('order_meeting', $where['id'], 'checkout', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("结帐完成，状态改变");location.href="'.U('Finance/financeMeeting').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("结帐失败");history.go(-1);</script>');
        }
    }
//代理商订单结算方法
    public function agent_check(){

        if (isset($_GET['id'])){
            $id = I('id');
            $sql = "SELECT a.id,a.pack_id,a.price_rec,a.collections_rec,a.state,a.addtime,a.check_date,a.order_num,a.agent_id,b.`name` AS user_name,c.`name` as agent_name FROM order_agent a LEFT JOIN admin_user b ON a.user_id=b.id LEFT JOIN car_agent c ON a.agent_id=c.id WHERE (a.is_del = 0) AND (a.id = {$id})";
            $pack_info = M('order_agent')->query($sql);
            session('pack_info',$pack_info);
            $this->assign('list',$pack_info[0]);
            $this->display();
        }

        if (isset($_POST['state'])){
            if (isset($_POST['collections_rec']) && !isset($_POST['price_paided'])){
                $data['collections_rec'] = I('post.collections_rec');
                $data['check_date'] = date("Y-m-d H:i:s", time());//结款时间
            }else if (isset($_POST['price_paided'])){
                $data['collections_rec'] = I('post.collections_rec') + I('post.price_paided');
                $data['updatetime'] = date("Y-m-d H:i:s", time());//更新时间
            }

            $data['remarks'] = I('post.remarks');
            $data['state'] = I('post.state');
            if ($_POST['pack_id'] != ''){
                $pack = I('post.pack_id');
                $pack_id = explode(",", $pack);
                foreach ($pack_id as $key => $value){
                    $where_order['id'] = $value;
                    $order['agent_check_state'] = $data['state'];
                    M('order')->where($where_order)->save($order);      //更改订单表中的申请状态
                    if (!isset($_POST['price_paided'])){
                        $sql = "SELECT TIMESTAMPDIFF(DAY ,pk_date,re_date) as rent_time,cost_price,id  FROM `order` WHERE id = {$where_order['id']}";
                        $orderinfo = M('order')->query($sql);
                        $order_pack['check_price'] = $orderinfo['rent_time'] * $orderinfo['cost_price'];//计算单笔订单的结算金额
                        $order_pack['state'] = $data['state'];
                        $order_pack['order_id'] = $value;
                        $order_pack['agent_id'] = session('pack_info')[0]['agent_id'];
                        $order_pack['order_pack_id'] = session('pack_info')[0]['id'];
                        $order_pack['check_date'] = date("Y-m-d H:i:s", time());//结款时间
                        M('order_agentcheck')->add($order_pack);            //将结算的具体订单写入代理商结算详情表中
                    }else{
                        $where_agent['order_id'] = $value;
                        $where_agent['order_pack_id'] = session('pack_info')[0]['id'];
                        $data_order['state'] = $data['state'];
                        M('order_agentcheck')->where($where_agent)->save($data_order);
                    }
                }
            }
            $order_agent['id'] = session('pack_info')[0]['id'];
            $msg = M('order_agent')->where($order_agent)->save($data);
            if ($msg!==false){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog('order_agent', $order_agent['id'], '进行结算', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                    session('pack_info',null);
                    exit('<script type="text/javascript">alert("结算完成！");location.href="'.U('CarAgent/check_info').'";</script>');
                }
            }else{
                exit('<script type="text/javascript">alert("结算失败");history.go(-1);</script>');
            }
        }
    }
    /**
     * 快速查询公共方法
     * @access public
     * @param int $select_time 判断查询的时间段
     * @param timestamp $startDate 查询开始的时间
     * @param timestamp $stopDate 查询结束的时间
     * @param int $select 判断查询的字段
     * @param mixed $key 查询条件
     * @param string $themes 查询完成后跳转的页面
     * @param string $order_state 限制订单状态
     * @param int $order 排序方式（默认0：id倒序;1：取车时间正序;2、还车时间正序）
     * @return Action
     */
    public function q_query($select_time=3,$startDate=0,$stopDate=0,$select=100,$key,$themes='Index',$order_state=0,$sort=0){
        $sql = "SELECT a.*,b.carmodelname,c.username,c.check_cycle,c.usertype,c.phone,d.carno,now()as now_time FROM`order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0) ";
//        $sql = "SELECT id,tablename,dataid,operate,disposedate,adminname FROM site_log WHERE (disposedate BETWEEN '%s' AND '%s') AND (isdel=0) ";
//        $countSql = "SELECT COUNT(ID) FROM site_log WHERE (disposedate BETWEEN '%s' AND '%s') AND (isdel=0) ";
        $countSql = "SELECT COUNT(a.id) FROM`order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0) ";

        switch ($select_time){
            case 0:
                $time = "AND (a.order_date BETWEEN '%s' AND '%s')";
                break;
            case 1:
                $time = "AND (a.pk_date BETWEEN '%s' AND '%s')";
                break;
            case 2:
                $time = "AND (a.re_date BETWEEN '%s' AND '%s')";
                break;
            default:
                $time = '';
        }
        //判断查询的字段
        switch ($select){
            case 0:
                $where = "AND (a.order_code LIKE '%%%s%%')";
                break;
            case 1:
                $where = "AND (c.username LIKE '%%%s%%')";
                break;
            case 2:
                $where = "AND (b.carmodelname LIKE '%%%s%%')";
                break;
            case 3:
                $where = "AND (a.order_state = '%d')";
                break;
            case 4:
                $where = "AND (c.phone LIKE '%%%s%%')";
                break;
            case 5:
                $where = "AND (d.carno LIKE '%%%s%%')";
                break;
            case 6:

                switch ($key){
                    case '0':
                        $where = "AND (a.check_out = '2')";
                        break;
                    case '1':
                        $where = "AND ((a.check_out <> '2') AND (a.check_out <> 0))";
                        break;
                }
                $select = 100;
                break;
            default:
        }
        if ($order_state){
            $where .= $order_state;
        }

        if ($select_time == 3){
            if ($select == 100){
                $ary=[];
            }else{
                $ary=[$key];
            }
        }else{
            if ($select == 100){
                $ary = [$startDate,$stopDate];
            }else{
                $ary = [$startDate,$stopDate,$key];
            }
        }


        $sql .=$time.$where;
        $countSql .=$time.$where;
        switch ($sort){
            case 0:
                $sql .= " ORDER BY a.id DESC";
                break;
            case 1:
                $sql .= " ORDER BY a.pk_date ASC";
                break;
            case 2:
                $sql .= " ORDER BY a.re_date ASC";
                break;
        }
        $this->pageDisplay($sql, $countSql,16, $ary, 'count(a.id)', 'list', 'page', ture);
        /*        $m = M();
                $carinfo = $m->query($sql,$ary);
                echo "<pre>";
                var_dump($carinfo);
                $this->assign('list',$carinfo);*/
        $this->display($themes);
    }
    /**
     * 会议订单查询公共方法
     * @access public
     * @param int $select_time 判断查询的时间段
     * @param timestamp $startDate 查询开始的时间
     * @param timestamp $stopDate 查询结束的时间
     * @param int $select 判断查询的字段
     * @param mixed $key 查询条件
     * @param string $themes 查询完成后跳转的页面
     * @param string $order_state 限制订单状态
     * @param int $order 排序方式（默认0：id倒序;1：取车时间正序;2、还车时间正序）
     * @return Action
     */
    public function meet_query($select_time=3,$startDate=0,$stopDate=0,$select=100,$key,$themes='order_meeting',$order_state=0,$order=0){
        $sql = "SELECT * FROM `order_meeting` WHERE (is_del=0) ";
        $countSql = "SELECT count(id) FROM `order_meeting` WHERE (is_del=0)";
//      判断查询的时间段
        switch ($select_time){
            case 0:
                $time = "AND (order_date BETWEEN '%s' AND '%s')";                     //根据下单时间区间查询
                break;
            case 1:
                $time = "AND (pk_date BETWEEN '%s' AND '%s')";                        //根据取车时间区间查询
                break;
            case 2:
                $time = "AND (re_date BETWEEN '%s' AND '%s')";                        //根据还车时间区间查询
                break;
            default:
                $time = '';
        }
        //判断查询的字段
        switch ($select){
            case 0:
                $where = "AND (order_code LIKE '%%%s%%')";                             //在订单号中查询
                break;
            case 1:
                $where = "AND (meeting_name LIKE '%%%s%%')";                               //在会议名称中进行查询
                break;
            case 2:
                $where = "AND (carmodelname LIKE '%%%s%%')";                           //在车辆型号中查询
                break;
            case 3:
                switch ($key){
                    case '未支付':
                        $key = 0;
                        break;
                    case '已支付':
                        $key = 1;
                        break;
                    case '已派车':
                        $key = 2;
                        break;
                    case '已取车':
                        $key = 3;
                        break;
                    case '已还车':
                        $key = 4;
                        break;
                    case '已结账':
                        $key = 5;
                        break;
                    case '可退押金':
                        $key = 6;
                        break;
                    case '正常结单':
                        $key = 7;
                        break;
                    case '取消订单':
                        $key = 10;
                        break;
                    case '退款申请':
                        $key = 11;
                        break;
                    case '同意退款':
                        $key = 12;
                        break;
                    case '退款完成':
                        $key = 13;
                        break;
                    default:
                        $key = '';
                }
                $where = "AND (order_state = '%d')";                              //对订单状态进行查询
                break;
            case 4:
                $where = "AND (phone LIKE '%%%s%%')";                             //对客户账号或者手机进行查询
                break;
            case 5:
                $where = "AND (carno LIKE '%%%s%%')";                             //对车辆号牌进行查询
                break;
            default:
        }
        //判断是否需要添加额外的查询限制条件
        if ($order_state){
            $where .= $order_state;
        }
        if ($select_time == 3){
            $ary = [$key];
        }elseif($select ==100){
            $ary = [$startDate,$stopDate];
        }elseif(($select_time == 3) || ($select ==100)){
            $ary=[];
        }else{
            $ary = [$startDate,$stopDate,$key];
        }
        $sql .=$time.$where;                                                                //对SQL语句进行拼接
        $countSql .=$time.$where;
        switch ($order){
            case 0:
                $sql .= "ORDER BY id DESC";
                break;
            case 1:
                $sql .= "ORDER BY pk_date ASC";
                break;
            case 2:
                $sql .= "ORDER BY re_date ASC";
                break;
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true); //分页显示
        $this->display('finance/'.$themes);
    }
    /**
     * pageInfo 分页显示方法，
     * @param string $arr   进行分页的数组 注意进行分页的数组必须是多维数组
     * @param string $count 获取分页数据总条数
     * @param string $pageNum 每页显示几条数据
     * @param string $listName 模板name名，传递数据到模板关联
     * @param string $showPage 模板分页绑定的输出参数，显示分页导航
     * @param bool $isPage 分页开关，True显示分页，False不显示分页，只显数据
     * @return void
     */

    function pageInfo($arr, $count, $pageNum,$listName, $showPage, $isPage){
        if ($isPage == true) {
            $page = new Page($count, $pageNum);
            $page->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条记录  第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
            $page->setConfig('prev', '上一页');
            $page->setConfig('next', '下一页');
            $page->setConfig('last', '尾页');
            $page->setConfig('first', '首页');
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $show = $page->show();
            $times = 0;
            foreach ($arr as $key => $value){
                if ($key >= $page->firstRow && $page->listRows > $times){
                    $list[] = $value;
                    ++$times;
                }
            }
            $this->assign($listName, $list);
            $this->assign($showPage, $show);
        } else {
            $list = $arr;
            $this->assign($listName, $list);
        }

    }
    //积分计算方法
    public function score($order_id,$score){
        $m = M();
        $sql = "SELECT b.id,b.score FROM `order` a LEFT JOIN work_member b ON a.uid = b.id WHERE (a.id = '%d')";
        $arr = [$order_id];
        $member = $m->query($sql,$arr);
        $member = $member[0];
        $where['id'] = $member['id'];
        $mem['score'] = $score + $member['score'];
        $m->table('work_member')->where($where)->save($mem);

    }
    //统计订单信息
    public function order_cout($key){
        $arr = [];
        $m = M();

        $sql = "SELECT a.id,a.car_id,a.order_type,a.order_state,a.deposit,a.get_deposit,a.dp_price,a.drive_state,a.order_state,b.usertype FROM `order` a  LEFT JOIN work_member b ON a.uid = b.id WHERE (a.is_del = 0)";
        $meet_sql = "SELECT id,order_state FROM `order_meeting` WHERE (is_del = 0)";
        $deposit_sql = "SELECT a.deposit,a.get_deposit,a.dp_price,a.drive_state,a.order_state,b.usertype FROM `order` a  LEFT JOIN work_member b ON a.uid = b.id WHERE (a.is_del = 0) AND (a.order_state > 3 AND a.order_state < 10)";
        $deposit_trade_sql = "SELECT * FROM order_cost WHERE is_del=0 AND ((charge_road>4 AND charge_road<8) OR pay_way=4) AND order_class=0";
        if (!empty($key)){
            $sql .= " AND (order_date BETWEEN '%s' AND '%s')";
            $meet_sql .= " AND (order_date BETWEEN '%s' AND '%s')";
            $deposit_sql .= " AND (a.re_date BETWEEN '%s' AND '%s')";
            $deposit_trade_sql .= " AND (charge_time BETWEEN '%s' AND '%s')";
            $arr =[$key['start'],$key['stop']];
        }
//        $order = $m->table('order')->field('id,car_id,order_type,order_state')->where('is_del = 0')->select();
        $order = $m->query($sql,$arr);                            //订单信息
        $meet = $m->query($meet_sql,$arr);                        //会议订单信息
        $deposit = $m->query($deposit_sql,$arr);                  //违章押金信息
        $deposit_trade = $m->query($deposit_trade_sql,$arr);      //违章押金交易信息
        $orderinfo['times'] = 0;                                  //总订单数
        $orderinfo['order_times'] = 0;                            //已交易订单数
        $orderinfo['order_normal'] = 0;                           //正常结单数
        $orderinfo['cancel'] = 0;                                 //取消订单数
        $orderinfo['refund'] = 0;                                 //退款订单数
        $orderinfo['ing'] = 0;                                    //进行中订单数
        $orderinfo['meet_times'] = 0;                             //会议订单数

        $orderinfo['deposit_times'] = 0;                          //押金交易次数
        $orderinfo['deposit_sum'] = 0;                            //押金收取总数
        $orderinfo['deposit_refun'] = 0;                          //退还押金总数
        $orderinfo['deposit_surplus'] = 0;                        //剩余押金总数
        $orderinfo['deposit_use'] = 0;                            //需使用押金处理的违章
        $orderinfo['deposit_driver'] = 0;                         //需代驾司机处理的违章
        $orderinfo['deposit_customer'] = 0;                       //需客户自己处理的违章
//        $order = $m->table('order')->field('id,car_id,order_type')->where()->select();
//        $order['sum'] = count($order_state);
        foreach ($order as $key => $value){                //对这些记录进行循环，并判定是否符合再租条件
            $orderinfo['times'] += 1;
            if ($value['order_state'] >0 && $value['order_state'] != 10){
                $orderinfo['order_times'] += 1;
            }
            if ($value['order_state'] == 7){
                $orderinfo['order_normal'] += 1;
            }
            if ($value['order_state'] == 10){
                $orderinfo['cancel'] += 1;
            }
            if ($value['order_state'] == 13){
                $orderinfo['refund'] += 1;
            }
            if ($value['order_state'] >0 && $value['order_state'] < 7  ){
                $orderinfo['ing'] += 1;
            }
        }
        foreach ($meet as $key => $value){
            $orderinfo['times'] += 1;
            $orderinfo['meet_times'] += 1;
            if ($value['order_state'] == 10){
                $orderinfo['cancel'] += 1;
            }
            if ($value['order_state'] == 3){
                $orderinfo['order_normal'] += 1;
            }
            if ($value['order_state'] < 3){
                $orderinfo['ing'] += 1;
            }
        }
        foreach ($deposit as $key => $value){
            if ($value['get_deposit'] == 1){
//                $orderinfo['deposit_sum'] += $value['deposiit'];
            }
            if ($value['drive_state'] == 0 && $value['usertype'] == 0){
                $orderinfo['deposit_use'] += $value['dp_price'];
            }else if ($value['drive_state'] == 1){
                $orderinfo['deposit_driver'] += $value['dp_price'];
            }else if ($value['drive_state'] == 0 && $value['usertype'] == 1){
                $orderinfo['deposit_customer'] += $value['dp_price'];
            }
        }
        foreach ($deposit_trade as $key => $value){
            if ($value['charge_road'] > 4 && $value['charge_road'] < 8){
                $orderinfo['deposit_times'] += 1;
            }
            if ($value['charge_road'] == 5 || $value['charge_road'] == 7){
                $orderinfo['deposit_sum'] += $value['charge_sum'];
            }
            if ($value['charge_road'] == 6 || $value['pay_way'] == 4){
                $orderinfo['deposit_refun'] += abs($value['charge_sum']);
            }
        }
        $orderinfo['deposit_surplus'] = $orderinfo['deposit_sum'] - $orderinfo['deposit_refun'] - $orderinfo['deposit_use'];
        return $orderinfo;
    }
    //车辆信息统计
    public function car_count(){
        $m = M();
        $car = $m->table('car_carinfo')->field('id,carno,carmodel,brand,carproperty,usestatus,isdiscount,isdel')->where('isdel = 0')->select();
        $carinfo['car_sum'] = 0;                        //车辆总数
        $carinfo['car_our'] = 0;                        //自有车辆数
        $carinfo['car_others'] = 0;                     //外调车辆数
        $carinfo['car_discount'] = 0;                   //优惠车辆数
        $carinfo['car_free'] = 0;                       //空闲车辆数
        $carinfo['car_stop'] = 0;                       //空闲车辆数
        $carinfo['car_free_discount'] = 0;              //优惠且空闲车辆数
        foreach ($car as $key => $value){
            $carinfo['car_sum'] += 1;
            if ($value['carproperty'] == 1){
                $carinfo['car_our'] += 1;
            }else{
                $carinfo['car_others'] += 1;
            }
            if ($value['isdiscount'] == 1){
                $carinfo['car_discount'] += 1;
            }
            if ($value['usestatus'] == 0){
                $carinfo['car_free'] += 1;
            }
            if ($value['usestatus'] == 0 && $value['isdiscount'] == 1){
                $carinfo['car_free_discount'] += 1;
            }
            if ($value['usestatus'] == 3){
                $carinfo['car_stop'] += 1;
            }
        }
        return $carinfo;
    }
    //会员信息统计
    public function member_count(){
        $m = M();
        $member = $m->table('work_member')->field('id,usertype,sex,balance,state,addtime,teltype,now()')->select();
        $time = strtotime("-1 months", time());    //当前时间减少一个月（自然月）；

        $memberinfo['sum'] = count($member);
        $memberinfo['member'] = 0;                  //会员总数
        $memberinfo['member_ordinary'] = 0;         //普通会员数
        $memberinfo['member_big'] = 0;              //大客户数
        $memberinfo['member_add'] = 0;              //新增客户数
        $memberinfo['member_iphone'] = 0;           //苹果终端数
        $memberinfo['member_android'] = 0;          //安卓终端数
        $memberinfo['member_null'] = 0;             //未知终端数
        foreach ($member as $key => $value){
            $addtime = strtotime($value['addtime']);
            if (!$value['state']){                  //冻结会员，0为正常，1为冻结
                $memberinfo['member'] += 1;
            }
            if ($value['usertype']){                //大客户，0为普通用户，1为大客户
                $memberinfo['member_big'] += 1;
            }else{
                $memberinfo['member_ordinary'] += 1;
            }
            if ($time - $addtime <= 0){
                $memberinfo['member_add'] += 1;
            }
            if ($value['teltype'] == 'iphone'){
                $memberinfo['member_iphone'] += 1;
            }else if ($value['teltype'] == null || $value['teltype'] == ''){
                $memberinfo['member_null'] += 1;
            }else{
                $memberinfo['member_android'] += 1;
            }
        }
        return $memberinfo;
    }
//财务信息统计
    public function trade_count($key){
        $m = M();
        $arr =[];
        //所有以还车订单的车辆成本 和取车还车时间
        $cost_price_sql = "SELECT a.cost_price,a.pk_date,a.re_date,a.order_state,a.price_rec,a.collections_rec,a.check_out,a.oil_price,a.tolls,a.wash_price,a.re_price,a.in_cost,b.usertype FROM `order` a LEFT JOIN work_member b ON a.uid = b.id WHERE (a.order_state > 3) AND (a.order_state < 10) AND (a.is_del = 0)";
        //所有以还车订单的杂项成本和
        $mixed_cost_sql = "SELECT SUM(oil_price + tolls + wash_price + re_price) FROM `order` WHERE (order_state > 3) AND (order_state < 10) AND (is_del = 0)";
        //会议订单成本的和
        $meeting_cost = "SELECT out_cost,mixed_cost,order_state,price_rec,collections_rec FROM `order_meeting` WHERE (order_state > 0) AND (order_state < 10) AND (is_del = 0)";
        //交易表查询
        $trade_sql = "SELECT a.charge_sum,c.usertype,a.order_class FROM `order_cost` a LEFT JOIN `order` b ON a.order_id=b.id LEFT JOIN work_member c ON b.uid=c.id WHERE (a.charge_road < 5) AND (a.pay_way > 0) AND (a.is_del = 0) ";
        if (!empty($key)){

            $where_trade = " AND (charge_time BETWEEN '%s' AND '%s')";
            $where = " AND (re_date BETWEEN '%s' AND '%s')";
            $cost_price_sql .= $where;
            $mixed_cost_sql .= $where;
            $meeting_cost .= $where;
            $trade_sql .= $where_trade;
            $arr =[$key['start'],$key['stop']];
        }

        $tradeinfo['times'] = 0;                                   //有效交易次数
        $tradeinfo['profit'] = 0;                                  //利润
        $tradeinfo['sum'] = 0;                                     //总收入
        $tradeinfo['check_out'] = 0;                               //未结账目
        $tradeinfo['cost_price'] = 0;                              //车辆成本
        $tradeinfo['mixed_cost'] = 0;                              //杂项成本
        $tradeinfo['meeting_cost'] = 0;                            //会议订单总成本

        $tradebig['times'] = 0;                                    //大客户订单有效交易次数
        $tradebig['sum'] = 0;                                      //总收入
        $tradebig['expenditure'] = 0;                              //支出，退款
        $tradebig['check_out'] = 0;                                //大客户未结账目
        $tradebig['cost_price'] = 0;                               //大客户订单车辆成本
        $tradebig['mixed_cost'] = 0;                               //大客户订单杂项成本

        $tradeplain['times'] = 0;                                  //普通订单有效交易次数
        $tradeplain['sum'] = 0;                                    //普通订单总收入
        $tradeplain['expenditure'] = 0;                            //普通订单支出，退款
        $tradeplain['check_out'] = 0;                              //普通订单未结账目
        $tradeplain['cost_price'] = 0;                             //普通订单车辆成本
        $tradeplain['mixed_cost'] = 0;                             //普通订单杂项成本

        $trademeet['times'] = 0;                                   //会议订单有效交易次数
        $trademeet['sum'] = 0;                                     //会议订单总收入
        $trademeet['expenditure'] = 0;                             //会议订单支出，退款
        $trademeet['check_out'] = 0;                               //会议订单未结账目
        $trademeet['cost_price'] = 0;                              //会议订单外调成本
        $trademeet['mixed_cost'] = 0;                              //会议订单杂项成本

        //会议订单信息
        $meeting_cost = $m->query($meeting_cost,$arr);
        foreach ($meeting_cost as $key => $value){
            $tradeinfo['cost_price'] += $value['out_cost'];
            $tradeinfo['mixed_cost'] += $value['mixed_cost'];
            $trademeet['cost_price'] += $value['out_cost'];
            $trademeet['mixed_cost'] += $value['mixed_cost'];
            if ($value['order_state'] == 1 || $value['order_state'] == 2){
                $tradeinfo['check_out'] += $value['price_rec'] - $value['collections_rec'];
                $trademeet['check_out'] += $value['price_rec'] - $value['collections_rec'];
            }
        }

        //交易统计
        $trade = $m->query($trade_sql,$arr);
        $tradeinfo['times'] = 0;                              //有效交易次数
        foreach ($trade as $key => $value){
            $tradeinfo['times'] += 1;
            if ($value['order_class'] != 1){
                if ($value['usertype'] == 1){
                    ++$tradebig['times'];
                    if ($value['charge_sum']<0){
                        $tradebig['expenditure'] += -$value['charge_sum'];
                    }else{
                        $tradebig['sum'] += $value['charge_sum'];
                    }
                }else {
                    ++$tradeplain['times'];
                    if ($value['charge_sum']<0){
                        $tradeplain['expenditure'] += -$value['charge_sum'];
                    }else{
                        $tradeplain['sum'] += $value['charge_sum'];
                    }
                }
            }else{
                ++$trademeet['times'];
                if ($value['charge_sum']<0){
                    $trademeet['expenditure'] += -$value['charge_sum'];
                }else{
                    $trademeet['sum'] += $value['charge_sum'];
                }
            }
        }
        //订单信息
        $cost_price_info = $m->query($cost_price_sql,$arr);       //查询计算车辆成本所需的数据
        $cost_price = 0;
        foreach ($cost_price_info as $key => $value){
            //计算车辆成本
            $pk_date = strtotime($value['pk_date']);
            $re_date = strtotime($value['re_date']);
            $day = floor(($re_date - $pk_date)/86400);      //舍弃小数部分，取整
            $min = ($re_date - $pk_date)/60;                //租车时长（分钟）
            $min_sur = $min - $day*24*60;
            if ($min_sur > 120 && $min_sur <= 360){
                $day += 0.5;
            }elseif ($min_sur > 360){
                $day += 1;
            }
            $cost_price += $value['cost_price'] * $day;
            if ($value['usertype'] == 1){
                $tradebig['cost_price'] += $value['cost_price'];
            }else{
                $tradeplain['cost_price'] += $value['cost_price'] * $day;
            }
            //计算未结账的总账目
            if ($value['check_out']=0 || $value['check_out']=2){
                $tradeinfo['check_out'] += $value['price_rec'] - $value['collections_rec'];
            }
            //大客户未结账目
            if (($value['check_out']=0 || $value['check_out']=2) && $value['usertype'] == 1){
                $tradebig['check_out'] += $value['price_rec'] - $value['collections_rec'];
            }else{
                $tradeplain['check_out'] += $value['price_rec'] - $value['collections_rec'];
            }

            //计算杂项成本
            $tradeinfo['mixed_cost'] += $value['oil_price'] + $value['tolls'] + $value['wash_price'] + $value['re_price'] + $value['in_cost'];
            if ($value['usertype'] == 1){
                $tradebig['mixed_cost'] += $value['oil_price'] + $value['tolls'] + $value['wash_price'] + $value['re_price'] + $value['in_cost'];
            }else{
                $tradeplain['mixed_cost'] += $value['oil_price'] + $value['tolls'] + $value['wash_price'] + $value['re_price'] + $value['in_cost'];
            }
        }
        $tradeinfo['cost_price'] += $cost_price;                        //车辆成本
        $tradeinfo['expenditure'] = 0;                                  //支出，退款
        foreach ($trade as $key => $value){                            //对这些记录进行循环，并判定是否符合再租条件
            if ($value['charge_sum'] >=0){
                $tradeinfo['sum'] += $value['charge_sum'];
            }else{
                $tradeinfo['expenditure'] += -$value['charge_sum'];
            }
            $tradeinfo['profit'] += $value['charge_sum'];
        }
        //利润 = 总收入 - 支出 - 车辆成本 - 杂项成本 - 会议订单成本
        $tradeinfo['profit'] = $tradeinfo['sum'] - $tradeinfo['expenditure'] - $tradeinfo['cost_price'] -$tradeinfo['mixed_cost'] - $tradeinfo['meeting_cost'];
        //大客户订单利润
        $tradebig['profit'] = $tradebig['sum'] - $tradebig['expenditure'] - $tradebig['cost_price'] -$tradebig['mixed_cost'];
        //普通订单利润
        $tradeplain['profit'] = $tradeplain['sum'] - $tradeplain['expenditure'] - $tradeplain['cost_price'] -$tradeplain['mixed_cost'];
        //会议订单利润
        $trademeet['profit'] = $trademeet['sum'] - $trademeet['expenditure'] - $trademeet['cost_price'] -$trademeet['mixed_cost'];
        $trade_info['tradeinfo'] = $tradeinfo;
        $trade_info['tradebig'] = $tradebig;
        $trade_info['tradeplain'] = $tradeplain;
        $trade_info['trademeet'] = $trademeet;
        return $trade_info;
    }

    //订单成本计算
    public function order_info($cond){
        $m = M();
        $order_all = array();
        $order_sql = "SELECT a.id,DATE_FORMAT(a.pk_date, '%Y-%m-%d') as opk_date,a.pk_date,DATE_FORMAT(a.re_date, '%Y-%m-%d') as ore_date,a.re_date,DATE_FORMAT(a.order_date, '%Y-%m-%d') as order_date,a.order_state,a.cost_price,a.oil_price,a.tolls,a.wash_price,a.re_price,a.in_cost,a.collections_rec,a.check_out,a.price_rec,a.order_code,a.u_price,b.usertype,c.carno 
FROM `order` a LEFT JOIN work_member b ON a.uid=b.id LEFT JOIN car_carinfo c ON a.car_id = c.id 
WHERE a.order_state>3 AND a.order_state<10 AND a.is_del=0 ";
        $meet_sql = "SELECT id,DATE_FORMAT(re_date, '%Y-%m-%d') as ore_date,re_date,DATE_FORMAT(pk_date, '%Y-%m-%d') as opk_date,pk_date,DATE_FORMAT(order_date, '%Y-%m-%d') as order_date,collections_rec,out_cost,mixed_cost,price_rec,order_state,order_code,car_num FROM order_meeting WHERE order_state>0 AND order_state<10 AND is_del=0 ";
        if ($cond['start'] != null && $cond['stop'] != null){
            $start = $cond['start'];
//            $cond['stop'] = date("Y-m-d", strtotime("+1 day", strtotime($cond['stop'])));
            $stop = date("Y-m-d", strtotime("+1 day", strtotime($cond['stop'])));
            $order_sql .= "AND (a.order_date BETWEEN '{$start}' AND '{$stop}')";
            $meet_sql .= "AND (order_date BETWEEN '{$start}' AND '{$stop}')";
            $order_all['term'] = $start.'号<br/>至<br/>'.$cond['stop'].'号';
        } else {
            $order_all['term'] = '从始至今';
        }
        if ($cond['select'] != null){
            $key = $cond['select'];
            $order_all['select'] = $key;
        }
        switch ($key){
            case '1':
                $order_sql .= " AND b.usertype = 0 ORDER BY order_date ASC";
                $order_info = $m->query($order_sql);
                break;
            case '2':
                $order_sql .= " AND b.usertype = 1 ORDER BY order_date ASC";
                $order_info = $m->query($order_sql);
                break;
            case '3':
                $order_sql .= " ORDER BY order_date ASC";
                $order_info = $m->query($order_sql);
                break;
            case '4':
                $meet_sql .= " ORDER BY order_date ASC";
                $meet_info = $m->query($meet_sql);
                break;
            default :
                $order_sql .= " ORDER BY order_date ASC";
                $meet_sql .= " ORDER BY order_date ASC";

                $order_info = $m->query($order_sql);
                $meet_info = $m->query($meet_sql);
        }
        foreach ($meet_info as $key => $value){
            $order_info[] = $meet_info[$key];
        }
        foreach ($order_info as $key => $value){
            //计算租车时长，两小时以内不算时间，2-6小时算半天，6小时以上算一天
            $pk_date = strtotime($value['pk_date']);
            $re_date = strtotime($value['re_date']);
            $day = floor(($re_date - $pk_date)/86400);
            $min = ($re_date - $pk_date)/60;
            $min_sur = $min - $day*24*60;
            if ($min_sur > 120 && $min_sur <= 360){
                $day += 0.5;
            }elseif ($min_sur > 360){
                $day += 1;
            }
            $order_info[$key]['duration'] = $day;       //租车时长
            $order_all['duration'] += $day;             //租车总时长
            //区分订单类型
            if ($value['usertype'] === '0'){
                $order_info[$key]['type'] = '普通订单';
//                $date = $value['ore_date'] - $value['opk_date'];
                $order_all['carno'] += 1;
            }else if ($value['usertype'] === '1'){
                $order_info[$key]['type'] = '大客户订单';
//                $date = $value['ore_date'] - $value['opk_date'];
                $order_all['carno'] += 1;
            }else{
                $order_info[$key]['type'] = '会议订单';
                $order_info[$key]['carno'] = '用车数量：'.$value['car_num'];
                $order_all['carno'] += $value['car_num'];            //用车总计
            }
            //计算，非会议订单的车辆成本和所有订单的未结账目
            if ($value['usertype'] !== null){
                if ($value['usertype'] === '1'){
                    $order_info[$key]['out_cost'] = $value['cost_price'];    //车辆成本乘以租车天数
                }else{
                    $order_info[$key]['out_cost'] = $day * $value['cost_price'];    //车辆成本乘以租车天数
                }

                if ($value['check_out'] != 1){
                    $order_info[$key]['check_cost'] = $value['price_rec'] - $value['collections_rec'];
                }else{
                    $order_info[$key]['check_cost'] = 0;
                }
            }else{
                if($value['order_state'] < 3){
                    $order_info[$key]['check_cost'] = $value['price_rec'] - $value['collections_rec'];
                }else{
                    $order_info[$key]['check_cost'] = 0;
                }
            }

            //所有成本和
            $order_info[$key]['cost_all'] = $value['oil_price'] + $value['tolls'] + $value['wash_price'] + $value['re_price'] + $value['in_cost'] + $order_info[$key]['out_cost'] + $value['mixed_cost'];
            //净收入，实收减去成本和
            $order_info[$key]['profit'] = $value['collections_rec'] - $order_info[$key]['cost_all'];
            //对所有信息的统计相加
            $order_all['out_cost'] += $order_info[$key]['out_cost'];
            $order_all['oil_price'] += $order_info[$key]['oil_price'];
            $order_all['tolls'] += $order_info[$key]['tolls'];
            $order_all['wash_price'] += $order_info[$key]['wash_price'];
            $order_all['re_price'] += $order_info[$key]['re_price'];
            $order_all['in_cost'] += $order_info[$key]['in_cost'];
            $order_all['mixed_cost'] += $order_info[$key]['mixed_cost'];
            $order_all['cost_all'] += $order_info[$key]['cost_all'];
            $order_all['collections_rec'] += $order_info[$key]['collections_rec'];
            $order_all['price_rec'] += $order_info[$key]['price_rec'];
            $order_all['check_cost'] += $order_info[$key]['check_cost'];
            $order_all['profit'] += $order_info[$key]['profit'];


            if ($value['oil_price'] == null){
                $order_info[$key]['oil_price'] = '/';
            }
            if ($value['tolls'] == null){
                $order_info[$key]['tolls'] = '/';
            }
            if ($value['wash_price'] == null){
                $order_info[$key]['wash_price'] = '/';
            }
            if ($value['re_price'] == null){
                $order_info[$key]['re_price'] = '/';
            }
            if ($value['in_cost'] == null){
                $order_info[$key]['in_cost'] = '/';
            }
            if ($value['mixed_cost'] == null){
                $order_info[$key]['mixed_cost'] = '/';
            }
            if ($value['u_price'] == null){
                $order_info[$key]['u_price'] = '/';
            }
        }
        $order_all['carno'] = '用车总计：'.$order_all['carno'];
        $order_all['opk_date'] = '/';
        $order_all['ore_date'] = '/';
        $order_all['duration'] = '总计：'.$order_all['duration'].'天';
        $order_all['u_price'] = '/';

        $order_all['order_num'] = $count = count($order_info);
        $this->assign('order_all',$order_all);
        $this->assign('cond',$cond);
        session('excel',$order_info);               //将组合后的数组储存到session中，导出excel时会用到
        session('excel_all',$order_all);            //将组合后的数组储存到session中，导出excel时会用到
        $this->pageInfo($order_info, $count,10,'list','page',true);
    }

    //导出excel 引用类方法
    public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $_SESSION['account'].date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8

        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
//        for($j=0;$j<$cellNum;$j++){
//            $objPHPExcel->getActiveSheet()->getStyle($cellName[$j].($i+2))->getFont()->setSize(12);       //设置字体大小
//            $objPHPExcel->getActiveSheet()->getStyle($cellName[$j].($i+2))->getFont()->setBold(true);     //设置字体粗细
//        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);    //设置列的宽度为自动
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(13);

        ob_end_clean();//清除缓冲区,避免乱码
//        header('pragma:public');
//        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
//        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.$xlsTitle.'.xlsx');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    /**
     *
     * 导出Excel
     * 需导出数组的实例
     */
    public function expUser(){//导出Excel
        $xlsName  = "User";
        $xlsCell  = array(
            array('id','id'),
            array('uid','uid'),
            array('carmodelid','性别'),
            array('car_id','院系'),
            array('order_date','专业'),
            array('pk_date','班级'),
            array('re_date','毕业时间'),
            array('u_price','所在地'),
            array('cost_price','单位'),
            array('pre_price','职称'),
            array('price_rec','职务'),
            array('collections_rec','级别'),
            array('price_paided','电话'),
            array('check_cycle','qq'),
            array('be_driver','邮箱'),
            array('driver_price','荣誉'),
            array('drive_state','备注')
        );

        $xlsModel = M('order');

        $xlsData  = $xlsModel->Field('id,uid,carmodelid,car_id,order_date,pk_date,re_date,u_price,cost_price,pre_price,price_rec,collections_rec,price_paided,check_cycle,be_driver,driver_price,drive_state')->select();
        foreach ($xlsData as $k => $v) {
            $xlsData[$k]['drive_state']=$v['drive_state']==1?'男':'女';
        }
    }

    //大客户结账方法
    public function max_endCost(){
        $tab = M();
        $tab->startTrans();
        $cost = orderCostOrderCode();//交易订单号
        $id = I('post.id');//订单id
        $price_paided = I('post.price_paided');//差价补退
        $price_rec = I('post.price_rec');//应收金额
        $collections_rec = I('post.collections_rec');//实收金额
        $remarks = I('post.remarks');//备注
        $check_out = I('post.check_out');//结账状态
        $pay_way = I('post.pay_way');//支付方式
        $is_invoice = I('post.is_invoice');//是否开票
        $in_code = I('post.in_code');//发票号
        $in_price = I('post.in_price');//开票金额
        $in_cost = I('post.in_cost');//额外税收
        $in_dep = I('post.in_dep');//开票单位


        //查询当前订单交易信息
        $ordercodesql = "SELECT id,charge_sum FROM order_cost WHERE order_id = %d AND pay_way=%d ";
        $codeinfo = $tab->query($ordercodesql,[$id,0]);

        $sql = "UPDATE `order` SET price_rec=price_rec+%d,collections_rec=collections_rec+%d,check_out=%d,remarks='%s',order_state=%d,is_invoice=%d,in_code='%s',in_price=%d,in_cost=%d,in_dep='%s' WHERE id=%d";
        $order = $tab->execute($sql,[$in_cost,$price_paided,$check_out,$remarks,5,$is_invoice,$in_code,$in_price,$in_cost,$in_dep,$id]);

        if ($codeinfo[0]['charge_sum'] == $price_paided){
            $ordercost1 = "UPDATE order_cost SET pay_way=%d,charge_time='%s',charge_road=%d WHERE id=%d";
            $order_cost = $tab->execute($ordercost1,[$pay_way,date('Y-m-d H:m:s',time()),2,$codeinfo[0]['id']]);
            $ordercost_re = 1;
        }else {
            //计算交易金额
            $charge_sum = round($codeinfo[0]['charge_sum'] - $price_paided);

            $ordercosts = "UPDATE order_cost SET pay_way=%d,charge_time='%s',charge_sum=%d,charge_road=%d WHERE id=%d";
            $order_cost = $tab->execute($ordercosts,[$pay_way,date('Y-m-d H:i:s',time()),$price_paided,2,$codeinfo[0]['id']]);
            if ($check_out == 2){
                //新增未结完账的数据
                $order_add = "INSERT INTO order_cost (order_id,order_class,trade_code,charge_sum,charge_road,charge_time,pay_way) VALUES (%d,%d,'%s',%d,%d,'%s',%d)";
                $ordercost_re = $tab->execute($order_add,[$id,2,$cost,$charge_sum,2,date('Y-m-d H:i:s',time()),0]);
            } else{
                $ordercost_re = 1;
            }

        }
        
        if ($order > 0 && $order_cost > 0 && $ordercost_re > 0){
            $tab->commit();
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $id, 'max_return_cost', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
                exit('<script type="text/javascript">alert("结帐完成");location.href="'.U('Finance/financeBig').'";</script>');
            }

        } else {
            $tab->rollback();
            exit('失败');
        }
    }
}