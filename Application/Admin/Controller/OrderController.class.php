<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Common\Common\app;
use SuperClosure\Analyzer\TokenAnalyzer;
use Think\Controller;
use Think\Verify;

//echo "<meta charset='utf-8' />";
/**
 * Class CarController
 * @package Admin\Controller
 *网站后台订单管理操作控制器
 */
class OrderController extends BaseController {
    //订单管理列表页面
    public function index(){

        $sql = "FROM (
		SELECT a.id,a.car_number,g.carid as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,a.order_code,b.brand,c.username,c.phone,c.usertype,d.costprice,d.carno,f.carmodelname FROM `order` a 
		LEFT JOIN work_member c ON a.uid=c.id 
		LEFT JOIN order_car g ON a.id = g.orderid 
		LEFT JOIN car_carinfo d ON g.carid=d.id
		LEFT JOIN car_barand b ON d.brand=b.id
		LEFT JOIN car_carmodel f ON d.carmodel=f.id
		WHERE c.usertype = 1 AND a.is_del = 0 AND a.order_state < 4 AND (a.car_id > 0 OR a.car_number > 0 )
		UNION ALL
		SELECT a.id,a.car_number,d.id as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,a.order_code,b.brand,c.username,c.phone,c.usertype,d.costprice,d.carno,f.carmodelname FROM `order` a 
		LEFT JOIN work_member c ON a.uid=c.id 
		LEFT JOIN car_carinfo d ON a.car_id=d.id 
		LEFT JOIN car_barand b ON d.brand=b.id
		 LEFT JOIN car_carmodel f ON d.carmodel=f.id
		WHERE c.usertype = 0 AND a.is_del = 0 AND a.order_state < 4 AND (a.car_id > 0 OR a.car_number > 0 )
) a WHERE " ;
        $countSql = "SELECT count(a.id) ".$sql;
        $sql = "SELECT * ".$sql;

        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $msg['select_time'] = $select_time;
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            if ($select == 3){
                $key = I('get.key_state');
            }
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
        }else{
            $startDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $stopDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']+2592000);
        }

        switch ($select_time){
            case 0:
                $time = " (a.re_date BETWEEN '%s' AND '%s')";                        //根据还车时间区间查询
                break;
            case 1:
                $time = " (a.pk_date BETWEEN '%s' AND '%s')";                        //根据取车时间区间查询
                break;
            default:
                $time = " (a.re_date BETWEEN '%s' AND '%s')";
        }

        switch ($select){
            case 2:
                $where = " AND (a.carmodelname LIKE '%%%s%%')";                           //在车辆型号中查询
                break;
            case 5:
                $where = " AND (a.carno LIKE '%%%s%%')";                             //对车辆号牌进行查询
                break;
            default:
        }
        $ary = [$startDate,$stopDate,$key];
        $sql .= $time.$where;
        $countSql .= $time.$where;
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示
        $this->display('Index');
    }
    //会议订单管理列表页面
    public function order_meeting(){
        $m = M();
        $sql = "SELECT * FROM `order_meeting` WHERE (is_del=0) AND (order_state = 0);";
        $countSql = "SELECT count(id) FROM `order_meeting` WHERE (is_del=0) AND (order_state = 0);";
        $ary = '';

        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $msg['select_time'] = $select_time;
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            if ($select == 3){
                $key = I('get.key_state');
            }
//            $order_state = "AND ((a.order_state < 4) AND (a.car_id > 0))";
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->meet_query($select_time,$startDate,$stopDate,$select,$key,'order_meeting',0,0);
        }else{
//            $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示
//            $this->display('Order/order_meeting');
            $startDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']-2592000);
            $stopDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $this->meet_query(0,$startDate,$stopDate,100,'','order_meeting',0,0);
        }
    }
    //会议订单详情
    public function orderMeet_info(){
        $m = M();
        $where['id'] = I('get.id');
        if (isset($_GET)){
            $order_info = $m->table('order_meeting')->where($where)->find();

            $where_cost['order_id'] = $where['id'];                                  //根据订单id，找出该订单的交易记录
            $where_cost['order_class'] = array('eq',1);
            $where_cost['pay_way'] = array('gt','0');
            $cost = $m->table('order_cost')->where($where_cost)->select();
            $cost['long'] = count($cost);

            foreach ($cost as $key => $value) {
                switch ($value['pay_way']) {
                    case 1:
                        $cost[$key]['pay_way'] = '支付宝';
                        break;
                    case 2:
                        $cost[$key]['pay_way'] = '微信';
                        break;
                    case 3:
                        $cost[$key]['pay_way'] = '现金';
                        break;
                    case 4:
                        $cost[$key]['pay_way'] = '抵扣押金';
                        break;
                }
                switch ($value['charge_road']) {
                    case 1:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，预付了￥' . $value['charge_sum'] . '。（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 2:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，支付了￥' . $value['charge_sum'] . '，用于订单后期结账。（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 3:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，补交了￥' . $value['charge_sum'] . '，用于订单后期结账。（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 4:
                        $cost[$key]['charge_road'] = $value['charge_time'] . '，公司将客户的预付费用￥' . $value['charge_sum'] . '退还给用户（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 5:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，交付了违章押金共：￥' . $value['charge_sum'].'（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 6:
                        $cost[$key]['charge_road'] = $value['charge_time'] . '，公司将剩余违章押金￥' . $value['charge_sum'] . '退还给用户（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 7:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，补交了违章押金共：￥' . $value['charge_sum'].'（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                }
            }
            $this->assign('cost',$cost);

            $this->assign('list',$order_info);
            $this->display();
        }else{
            $this->display('Index/Index');
        }
    }
    //所有订单
    public function all(){
        $this->assign('act','all');
        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $msg['select_time'] = $select_time;

            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            if ($select == 3){
                $key = I('get.key_state');
            }
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'all');
        }else{
            if (isset($_GET['info'])){
                $order_state = " AND (a.order_state < 2)";
                $this->q_query(3,0,0,100,0,'all',$order_state);
            }else{
                $startDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']-2592000);
                $stopDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
                $order_state = " AND (a.order_state != 10)";
                $this->q_query(0,$startDate,$stopDate,100,0,'all',$order_state);
            }
        }
    }
    //    订单管理公共方法
    public function supervise(){
        $m = M();
        if (isset($_GET['action'])){

            $id = I('get.id');
            if ($_GET['big'] == 1){
                $sql="SELECT a.*,b.username,b.phone,d.carno
FROM `order` a LEFT JOIN work_member b ON a.uid=b.id LEFT JOIN order_car c ON a.id=c.carid LEFT JOIN car_carinfo d ON c.carid=d.id 
WHERE (a.is_del=0) AND (a.id=%d)";
            } else {
                $sql = "SELECT a.*,b.carmodelname,b.shortdayprice,b.weekdayprice,b.monthdayprice,b.barandid,c.username,c.phone,d.carno,e.id as driverid,e.drivername,e.phone as driverphone FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id LEFT JOIN car_driverinfo e ON a.be_driver=e.id WHERE (a.is_del=0) AND (a.id=%d)";
            }
            $ary = array($id);
            $orderinfo = $m->query($sql,$ary);
            $orderinfo = $orderinfo[0];
            $action = I('get.action');
            switch ($_GET['action']){
                case 'send' :
                    $barand = $m->table('car_barand')->field('id,brand')->where('isdel=0')->select();
                    $this->assign('barand',$barand);
                    $where_two['state'] = 0;
                    $where_two['isdel'] = 0;
                    $alldriver = $m->table('car_driverinfo')->field('id,drivername,phone,cost')->where($where_two)->select();
                    $this->assign('alldriver',$alldriver);
                    break;
                case 'max_send' :
                    $barand = $m->table('car_barand')->field('id,brand')->where('isdel=0')->select();
                    $this->assign('barand',$barand);
                    $where_two['state'] = 0;
                    $where_two['isdel'] = 0;
                    $alldriver = $m->table('car_driverinfo')->field('id,drivername,phone,cost')->where($where_two)->select();
                    $this->assign('alldriver',$alldriver);
                    break;
                case 'take_car' :
                    $barand = $m->table('car_barand')->field('id,brand')->where('isdel=0')->select();
                    $this->assign('barand',$barand);
                    break;
                case 'max_give_car' :
                    $barand = $m->table('car_barand')->field('id,brand')->where('isdel=0')->select();
                    $this->assign('barand',$barand);
                    break;
                case 'give_car' :
                    $barand = $m->table('car_barand')->field('id,brand')->where('isdel=0')->select();
                    $this->assign('barand',$barand);
                    break;
                case 'driver' :
                    $where_two['state'] = 0;
                    $where_two['isdel'] = 0;
                    $alldriver = $m->table('car_driverinfo')->field('id,drivername,phone,cost')->where($where_two)->select();
                    $this->assign('alldriver',$alldriver);
                    break;
                case 'max_driver' :
                    $where_two['state'] = 0;
                    $where_two['isdel'] = 0;
                    $alldriver = $m->table('car_driverinfo')->field('id,drivername,phone,cost')->where($where_two)->select();
                    $tab = M();
                    $sql = "SELECT a.drivername,a.id FROM car_driverinfo a LEFT JOIN order_drive b ON a.id=b.driveid LEFT JOIN `order` c ON c.id=b.orderid WHERE c.id=%d AND b.is_del=%d";
                    $info=$tab->query($sql,[$id,0]);
                    $orderinfo['drivername'] = $info;
                    $this->assign('alldriver',$alldriver);
                    break;
            }
            $this->assign('action',$action);

            $this->assign('list',$orderinfo);
            if (isset($_GET['big'])){
                $this->display('Order/edit_big');
            }else{
                $this->display('Order/edit');
            }
        }else{
            if (isset($_REQUEST['act'])){
                $act = I('request.act');
                switch ($act){
                    case 'send':
                        $order_state = "AND ((a.order_state <3) OR (a.order_state = 11) OR ((a.order_state = 3) AND (a.drive_state = 1))) AND (c.usertype = 0) ";
                        break;
                    case 'driver':
                        $order_state = "AND (((a.order_state = 2) OR ((a.order_state = 1) AND (a.car_id != 0))) AND ((a.drive_state = 1) AND (a.be_driver = ''))) AND (c.usertype = 0)";
                        break;
                    case 'take_car':
                        $order_state = "AND (((a.order_state = 2) OR ((a.order_state = 1) AND (a.car_id != 0))) AND ((a.drive_state = 0) OR ((a.drive_state = 1) AND (a.be_driver != '')))) AND (c.usertype = 0)";
                        break;
                    case 'cost':
                        $order_state = "AND (a.order_state = 3) AND (c.usertype = 0)";
                        break;
                    default:
                        $order_state = 0;
                }
                $this->assign('act',$act);

            }else{
                $order_state = "AND ((a.order_state < 3) OR (a.order_state = 11)) AND (c.usertype = 0)";
            }
            if(isset($_GET['select'])){
                $select = I('get.select');
                $select_time = I('get.select_time');
                $startDate = I('get.start');
                $stopDate = I('get.stop');
                $key = I('get.key');
                if ($select == 3){
                    $key = I('get.key_state');
                }
                $msg['select'] = $select;
                $msg['select_time'] = $select_time;
                $msg['start'] = $startDate;
                $msg['stop'] = $stopDate;
                $msg['key'] = $key;
                $this->assign('msg',$msg);

                $this->q_query($select_time,$startDate,$stopDate,$select,$key,'supervise',$order_state,1);
            }else{
                $this->q_query(3,0,0,100,0,'supervise',$order_state,1);
            }
        }
    }
    //会议订单修改方法
    public function edit_meeting(){
        $m = M();
        if (empty($_POST)){
            $where['id'] = I('get.id');
            $orderinfo = $m->table('order_meeting')->where($where)->find();
            $this->assign('list',$orderinfo);
            $this->display('edit_meeting');
        }else {
            $rules = array(
                array('pre_price', 'require', '会议名称不能为空！', 0), //默认情况下用正则进行验证
                array('re_date', 'require', '还车时间时间不能为空！', 0), //默认情况下用正则进行验证
                array('u_price', 'require', '车辆单价不能为空！', 0), //默认情况下用正则进行验证
                array('price_rec', 'require', '应收金额不能为空！', 0), //默认情况下用正则进行验证
            );
            if (!$m->table('order_meeting')->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $error = $m->table('order_meeting')->getError();
                exit("<script type='text/javascript'>alert('" . $error . "');history.go(-1);</script>");
            } else {
                $where['id'] = I('post.id');
                $data['pk_date'] = I('post.pk_date');
                $data['re_date'] = I('post.re_date');
                $pk_date = strtotime($data['pk_date']);                 //转换取车时间
                $re_date = strtotime($data['re_date']);                 //转换还车时间
                if (($pk_date - $re_date) > 0) {                         //对还车时间进行计算，并判定是否小于取车时间
                    exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
                }
                $data['price_rec'] = I('post.price_rec');
                $data['meeting_name'] = I('post.meeting_name');
                $data['car_num'] = I('post.car_num');
                $data['expect_date'] = I('post.expect_date');
                $data['out_cost'] = I('post.out_cost');
                $data['mixed_cost'] = I('post.mixed_cost');
//                $data['pre_price'] = I('post.pre_price');
                $data['order_state'] = 0;
                $data['remarks'] = I('post.remarks');
                $save = $m->table('order_meeting')->where($where)->save($data);
                if ($save !==false) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog('order_meeting', $save, 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        exit('<script type="text/javascript">alert("修改成功！");location.href="' . U('Order/order_meeting') . '";</script>');
                    }
                } else {
                    exit('<script type="text/javascript">alert("修改失败，请重试！");history.go(-1);</script>');
                }
            }
        }
    }
    //会议订单取消方法
    public function meet_cancel(){
        $m = M();
        $where['id'] = I('get.id');
        $data['order_state'] = 10;
        $msg = $m->table('order_meeting')->where($where)->save($data);
        if ($msg){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order_meeting', $msg, 'cancel', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id', null);
                exit('<script type="text/javascript">alert("订单已取消！");location.href="' . U('Order/order_meeting') . '";</script>');
            }
        } else {
            exit('<script type="text/javascript">alert("操作失败，请重试！");history.go(-1);</script>');
        }
    }
    //订单管理方法
    public function orderSup(){
        $order_state = "AND ((a.order_state < 3) OR (a.order_state = 11)) AND (c.usertype = 0)";
        if(isset($_POST['select'])){
            $select = I('post.select');
            $select_time = I('post.select_time');
            $startDate = I('post.start');
            $stopDate = I('post.stop');
            $key = I('post.key');
            if ($select == 3){
                $key = I('post.key_state');
            }
            $msg['select'] = $select;
            $msg['select_time'] = $select_time;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'orderSup',$order_state,1);
        }else{
            $this->q_query(3,0,0,100,0,'orderSup',$order_state,1);
        }
    }
//    订单管理操作方法、会议订单管理方法
    public function order_ope(){
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'charge':                       //收费操作
                    $this->charge($_POST);
                    break;
                case 'charge_two':                   //补交预付操作
                    $this->charge($_POST);
                    break;
                case 'send':                         //派车操作、指派代驾合并
                    $this->send($_POST);
                    break;
                case 'max_send':
                    $this->max_send($_POST);//大客户派车
                    break;
                case 'driver':                       //指派代驾操作
                    $this->driver($_POST);
                    break;
                case 'max_driver':                       //大客户指派代驾操作
                    $this->max_driver($_POST);
                    break;
                case 'take_car':                    //客户取车操作
                    $this->take_car($_POST);
                    break;
                case 'give_car':                    //送车上门操作
                    $this->take_car($_POST);
                    break;
                case 'max_give_car':                    //送车上门操作(大客户)
                    $this->max_give_car($_POST);
                    break;
                case 'cost':                        //成本录入操作
                    $this->cost($_POST);
                    break;
                case 'relet':                       //续租操作
                    $this->relet($_POST);
                    break;
                case 'max_relet':                       //续租操作
                    $this->max_relet($_POST);           //大客户续租
                    break;
                case 'return_car':                  //客户还车操作
                    $this->return_car($_POST);
                    break;
                case 'max_return_car':                  //大客户还车操作
                    $this->max_return_car($_POST);
                    break;
                case 'dp_price':                    //违章金额录入操作
                    $this->dp_price($_POST);
                    break;
                case 'deposit':                     //同意退押金操作
                    if ($_POST['big'] == 1){
                        $this->deposit_big($_POST);
                    }else{
                        $this->deposit($_POST);
                    }
                    break;
                case 'finish':                     //同意退押金操作
                    $this->deposit($_POST);
                    break;
                default:
            }
        }
        if (isset($_GET['action'])){
            switch ($_GET['action']) {
                case 'cancel':                      //取消订单操作 max_cancel
                    $this->cancel($_GET);
                    break;
                case 'refund':                      //客户退款操作
                    $this->refund($_GET);
                    break;
                case 'max_cancel':                      //大客户取消订单
                    $this->max_cancel($_GET);
                    break;
            }
        }
    }

    //大客户管理方法
    public function customerBig(){
        $order_state = "AND (c.usertype = 1) ";
        $time = date("Y-m-d H:i:s",time());
        $time = date("Y-m-d H:i:s", strtotime("-1 months", strtotime("$time")));
        $this->assign('term',$time);
        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $msg['select_time'] = $select_time;
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            if ($select == 3){
                $key = I('get.key_state');
            };
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->max_query($select_time,$startDate,$stopDate,$select,$key,'customerBig',$order_state);
        }else{
            $startDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']-2592000);
            $stopDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $this->max_query(0,$startDate,$stopDate,100,0,'customerBig',$order_state);
        }
    }
    //大客户录入完成方法
    public function dp_input(){
        if (isset($_GET['id'])){
            $m = M();
            $where['id'] = I('get.id');
            $order_state = $m->table('order')->field('order_state')->where($where)->find(); //通过id查看订单状态
            if ($order_state['order_state'] == 5){                                                         //如果订单为5（已结账）
                $data['order_state'] = 7;                                                   //则在录入完成后把状态改为7（正常结单）
            }else{
                $data['order_state'] = 6;                                                   //则在录入完成后改为可退押金状态
            }
            $dp = $m->table('order')->where($where)->save($data);
            if ($dp){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog('order', $where['id'], 'charge', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                    exit('<script type="text/javascript">alert("订单状态已改变");location.href="'.U('Order/customerBig').'";</script>');
                }
            }else{
                exit('<script type="text/javascript">alert("订单状态改变失败");history.go(-1);</script>');
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
            $data['order_state'] = 1;
            //将消费产生的积分写入用户表
            $score = $order_cost['charge_sum'];
            $order_id = I('post.id');
            $this->score($order_id,$score);

            $charge = $m->table('order')->where($where)->save($data);
            if ($charge){
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
//    派车方法
    public function send($key){
        $m = M();
        $_POST = $key;
        $rules = array(
            array('car_id','require','请为客户派选车辆！',0), //默认情况下用正则进行验证
        );
        if (!$m->table('order')->validate($rules)->create()){
            exit($m->table('order')->getError());
        }else{
            $where['id']     = I('post.id');
            $where_car['id'] = I('post.car_id');
            $order_state = I('post.order_state');
            $data_car['usestatus'] = 1;
            $carstate = $m->table('car_carinfo')->where($where_car)->save($data_car);
            $data['car_id'] =$where_car['id'];
            $car_costprice = $m->table('car_carinfo')->field('costprice, agent_id')->where($where_car)->find();
            $data['agent_id'] = $car_costprice['agent_id'];
            $data['cost_price'] = $car_costprice['costprice'];
            $data['carmodelid'] = I('post.carmodelid');     //车型id
            $data['drive_state'] = I('post.drive_state');   //是否代驾
            $data['remarks'] = I('post.remarks');           //备注
            if ($order_state == 0){                         //如果订单还未支付则改变预付为0
                $data['pre_price'] = 0;
            }
            $data['order_state'] = 2;

            if ($data['drive_state'] == 1){                 //判断是否代驾，并做处理
                $where_driver['id'] = I('post.be_driver');
                $data_driver['state'] = 1;
                $m->table('car_driverinfo')->where($where_driver)->save($data_driver);
                $data['be_driver'] = I('post.be_driver');
                $data['driver_price'] = I('post.driver_price');
                $data['order_state'] = 2;
            }
            $charge = $m->table('order')->where($where)->save($data);


            if ($charge && $carstate!==false){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog('order', $where['id'], 'send', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                    if($_POST['big'] == 1){
                        exit('<script type="text/javascript">alert("派车成功");location.href="'.U('Order/customerBig').'";</script>');
                    }
                    exit('<script type="text/javascript">alert("派车成功");location.href="'.U('Order/supervise?act=send').'";</script>');
                }
            }else{
                exit('<script type="text/javascript">alert("派车失败");history.go(-1);</script>');
            }
        }
    }


    //大客户换车方法
    public function modifyCar(){
        $tab = M();
        $tab->startTrans();
        $nowcarid = I('get.nowcarid');
        $oldcarid = I('get.oldcarid');
        $orderid = I('get.orderid');
        $re_date = I('get.re_date');
        $order_state = I('get.order_state');//订单状态

        $drive = "SELECT id,driveid FROM order_car WHERE carid=%d AND is_del=%d";
        $driveid = $tab->query($drive,[$oldcarid,0]);

        //查询将换入车辆状态
        $sel = "SELECT usestatus FROM car_carinfo WHERE id=%d";
        $statu = $tab->query($sel,[$nowcarid]);

        if ($order_state >= 3){
            //改车辆状态
            if ($statu[0]['usestatus'] == 1){
                $carinfo = 1;
            }else{
                $cartab = "UPDATE car_carinfo SET usestatus = %d WHERE id =%d ";
                $carinfo = $tab->execute($cartab,[1,$nowcarid]);
            }


            //标记被替换的车辆
            $ordercar = "UPDATE order_car SET endtime='%s',is_del=%d WHERE carid =%d AND is_del=%d";
            $order = $tab->execute($ordercar,[Date('Y-m-d H:i:s',time()),1,$oldcarid,0]);
            //写入换的车辆信息
            $sql = "INSERT INTO order_car (orderid,carid,begintime,endtime,driveid) VALUES (%d,%d,'%s','%s',%d)";
            $add = $tab->execute($sql,[$orderid,$nowcarid,Date('Y-m-d H:i:s',time()),$re_date,$driveid[0]['driveid']]);
        } else {
            //改车辆状态
            if ($statu[0]['usestatus'] == 2){
                $carinfo = 1;
            } else {
                $cartab = "UPDATE car_carinfo SET usestatus = %d WHERE id =%d ";
                $carinfo = $tab->execute($cartab,[2,$nowcarid]);
            }


            $ordercar = "UPDATE order_car SET carid=%d WHERE carid =%d ";
            $order = $tab->execute($ordercar,[$nowcarid,$oldcarid]);
            $add = 1;
        }

        $car = "UPDATE car_carinfo SET usestatus = %d WHERE id =%d ";
        $info = $tab->execute($car,[0,$oldcarid]);

        if ($carinfo > 0 && $order > 0 && $info > 0 && $add > 0){
            $tab->commit();
        } else {
            $tab->rollback();
        }
    }

    //大客户派车
    public function max_send($data){
        $m = M();
        $m->startTrans();//开启事务
        //代驾id
        $driverId = $data['be_driver'];
        $order_state = $data['order_state'];
        $remarks = $data['remarks'];
        $orderid = $data['id'];
        $pieceser = explode(",", $driverId);
        $drivercount  =  count($pieceser);
        //车辆id
        $arr = $data['car_id'];
        $pieces = explode(",", $arr);
        array_pop($pieces);
        $count  =  count($pieces);
        $uid = $this->getUid($orderid);
        //循环添加车辆
        if (!empty($arr)){
            for ($i=0;$i<$count;$i++){
                //加入交通车项目
                $join = "INSERT INTO sc_carts (cartid,bossid,orderid) VALUES ('%d','%d','%d')";
                $m->execute($join,[$pieces[$i],$uid,$data['id']]);
                $add_car = "insert into order_car (orderid,carid,begintime,endtime) VALUES (%d,%d,'%s','%s')";
                if ($order_state >= 3){
                    $addcar = $m->execute($add_car,[$data['id'],$pieces[$i],date('Y-m-d H:i:s',time()),$data['re_date']]);
                } else{
                    $addcar = $m->execute($add_car,[$data['id'],$pieces[$i],$data['pk_date'],$data['re_date']]);
                }

            }
        } else {
            $addcar = 1;
            $upcar = 1;
        }


        //循环添加驾驶员
        if (!empty($driverId)){
            for ($j=0;$j<$drivercount;$j++){
                $add_drive = "insert into order_drive (orderid,driveid) VALUES (%d,%d)";
                $rs = $m->execute($add_drive,[$data['id'],$pieceser[$j]]);
                if ($rs){
                    $sql = "UPDATE car_driverinfo SET state=%d WHERE id=%d";
                    $res=$m->execute($sql,[1,$pieceser[$j]]);
                }
            }
        } else {
            $res = 1;
        }


        //循环修改车辆状态
        for ($i=0;$i<$count;$i++){
            $up_car = "UPDATE car_carinfo SET usestatus = %d WHERE id=%d";
            $upcar = $m->execute($up_car,[2,$pieces[$i]]);

        }
        //代驾人数
        $sql = "SELECT COUNT(id) FROM order_drive WHERE (orderid = %d) AND (is_del=%d)";
        $drive_count = $m->query($sql,$data['id'],0);
        //车辆数量
        $sql = "SELECT carid FROM order_car WHERE orderid = %d  AND (is_del=%d)";
        $car_count = $m->query($sql,$data['id'],0);

        $ary = '';
        foreach ($car_count as $v){
            if ($ary == '' || $ary == null){
                $ary .= $v['carid'] ;
            }else{
                $ary .= ','.$v['carid'] ;
            }
        }

        if ($order_state >= 3){
            $up_order = "UPDATE `order` SET car_number=%d,drive_number=%d,remarks='%s' WHERE id=%d";
            $uporder = $m->execute($up_order,[count($car_count),$drive_count[0]['count(id)'],$remarks,$data['id']]);
        } else{
            $up_order = "UPDATE `order` SET order_state = %d,car_number=%d,drive_number=%d,remarks='%s' WHERE id=%d";
            $uporder = $m->execute($up_order,[2,count($car_count),$drive_count[0]['count(id)'],$remarks,$data['id']]);
        }

        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        $log = self::writeLog('order', $data['id'], '大客户派车', Date('Y-m-d H:i:sA'), $auserInfo['name']);

        if ($log > 0 && $addcar > 0 && $res > 0 && $upcar !==false &&  $uporder !==false){
            $m->commit();
            if (!empty($arr)){
                exit('<script type="text/javascript">alert("派车成功");location.href="'.U('Order/customerBig').'";</script>');
            } else {
                exit('<script type="text/javascript">alert("加派代驾成功");location.href="'.U('Order/customerBig').'";</script>');
            }

        } else {
            $m->rollback();
            exit('<script type="text/javascript">alert("派车失败");</script>');
        }

    }

    //获取用户id
    public function getUid($id){
        $m = M();
        $sql = "SELECT uid FROM `order` WHERE id = '%d' ";
        $uid = $m->query($sql,[$id]);
        return $uid[0]['uid'];
    }


    //车辆取消
    public function carCancel(){
        $tab = M();
        $id = I('get.id');
        $order_state = I('get.order_state');//订单状态
        $tab->startTrans();
        //查询数据
        $sql = "SELECT a.id,a.carid,a.driveid,a.orderid,c.id as cid FROM order_car a  LEFT JOIN order_drive c ON a.orderid=c.orderid AND a.driveid=c.driveid AND c.is_del=0  WHERE (a.is_del = 0) AND a.id = %d " ;
        $info = $tab->query($sql,[$id]);

        if($order_state >= 3){
            //修改订单车辆
            $order_car = "UPDATE order_car SET endtime='%s',is_del=%d WHERE id=%d";
            $ordercar = $tab->execute($order_car,[date('Y-m-d H:m:s',time()),2,$id]);

            //修改车辆状态
            $car_info = "UPDATE car_carinfo SET usestatus=%d WHERE id=%d";
            $carnfo = $tab->execute($car_info,[0,$info[0]['carid']]);
        } else {
            //修改订单车辆
            $order_car1 = "DELETE FROM  order_car  WHERE id=%d";
            $ordercar = $tab->execute($order_car1,[$id]);
            $carnfo = 1;
        }


        //重新提交车辆数量
        //车辆数量
        $carcount = "SELECT COUNT(id) FROM order_car WHERE orderid = %d  AND (is_del=%d)";
        $car_count = $tab->query($carcount,$info[0]['orderid'],0);

        if ($info[0]['driveid'] > 0){
            if($order_state >= 3){
                //修改订单司机
                $order_drive = "UPDATE order_drive SET distribution=%d WHERE id=%d";
                $orderdrive = $tab->execute($order_drive,[0,$info[0]['cid']]);

                $cardrive = 1;
            } else {
                //修改订单司机
                $order_drive1 = "DELETE FROM order_drive  WHERE id=%d";
                $orderdrive = $tab->execute($order_drive1,[$info[0]['cid']]);

                //恢复代驾表
                $car_drive = "UPDATE car_driverinfo SET state = %d WHERE id = %d";
                $cardrive = $tab->execute($car_drive,[0,$info[0]['driveid']]);
            }

            //代驾人数
            $drivecount = "SELECT COUNT(id) FROM order_drive WHERE (orderid = %d) AND (is_del=%d)";
            $drive_count = $tab->query($drivecount,$info[0]['orderid'],0);

            $sql = "UPDATE `order` SET car_number=%d,drive_number=%d WHERE id=%d";
            $order = $tab->execute($sql,[$car_count[0]['count(id)'],$drive_count[0]['count(id)'],$info[0]['orderid']]);

        }else{
            $sql = "UPDATE `order` SET car_number=%d WHERE id=%d";
            $order = $tab->execute($sql,[$car_count[0]['count(id)'],$info[0]['orderid']]);
            $cardrive = 1;
            $orderdrive = 1;
        }


        //执行取消代码
        if ($ordercar > 0 && $orderdrive > 0 && $carnfo !== false && $order > 0 && $cardrive > 0){
            $tab->commit();
            $this->ajaxReturn(array('error'=>0));
        } else {
            $tab->rollback();
            $this->ajaxReturn(array('error'=>1));
        }


    }

    //订单详情指定代驾
    public function orderDrive(){
        $tab = M();
        $orderid = I('get.id');
        $sql = "SELECT a.id,a.driveid,a.distribution,b.drivername FROM order_drive a LEFT JOIN car_driverinfo b ON a.driveid=b.id WHERE is_del=%d AND orderid = %d AND a.driveid !=%d ";
        $data = $tab->query($sql,[0,$orderid,0]);
        $num = count($data);
        for ($i=0;$i<$num;$i++){
            if ($data[$i]['distribution'] == 0){
                $data[$i]['distribution'] = '未指派';
            } else{
                $data[$i]['distribution'] = '已指派';
            }
        }


        $this->ajaxReturn(array('driveinfo'=>$data));
    }

    //指派代驾
    public function setdrive(){
        $tab = M();
        $tab->startTrans();//开启事务
        $cardaleid = I('get.cardaleid');//车辆数据id
        $drivedataid = I('get.drivedataid');//代驾数据id
        $driveid = I('get.driveid');//代驾id
        $order_state = I('get.order_state');//订单状态
        $re_date = I('get.re_date');//还车时间
        $orderid = I('get.orderid');//订单id

        //查询该车辆开始时间
        $sql = "SELECT a.orderid,a.begintime,a.endtime,a.carid,a.driveid,b.id as bid FROM order_car a LEFT JOIN order_drive b ON a.driveid=b.driveid WHERE a.id=%d AND b.orderid = %d";
        $cartime = $tab->query($sql,[$cardaleid,$orderid]);

        //更改代驾
        if ($cartime[0]['driveid'] > 0){
            if ($order_state >= 3){
                //修改代驾表
                $order_drive = "UPDATE order_drive SET distribution = %d WHERE id =%d";
                $orderdrive = $tab->execute($order_drive,[1,$drivedataid]);

                //释放原有代驾
                $oldsql = "UPDATE order_drive SET distribution = %d WHERE id =%d";
                $old = $tab->execute($oldsql,[0,$cartime[0]['bid']]);

                //标记该条数据
                $updata = "UPDATE order_car SET is_del = %d,endtime='%s' WHERE id = %d";
                $up_data = $tab->execute($updata,[4,date('Y-m-d H:i:s',time()),$cardaleid]);

                //生成新的代驾数据
                $adddata = "INSERT INTO order_car (orderid,carid,driveid,begintime,endtime) VALUES (%d,%d,%d,'%s','%s')";
                $add = $tab->execute($adddata,[$cartime[0]['orderid'],$cartime[0]['carid'],$driveid,date('Y-m-d H:i:s',time()),$re_date]);
            } else{
                $add = 1;
                //修改代驾表
                $order_drive = "UPDATE order_drive SET distribution = %d WHERE id =%d";
                $orderdrive = $tab->execute($order_drive,[1,$drivedataid]);

                //释放原有代驾
                $oldsql = "UPDATE order_drive SET distribution = %d WHERE id =%d";
                $old = $tab->execute($oldsql,[0,$cartime[0]['bid']]);

                //标记该条数据
                $updata = "UPDATE order_car SET driveid=%d WHERE id = %d";
                $up_data = $tab->execute($updata,[$driveid,$cardaleid]);
            }
            $ordercar = 1;

        } else{
            $old = 1;
            $up_data = 1;
            $add = 1;
            //修改代驾表
            $order_drive = "UPDATE order_drive SET distribution = %d WHERE id =%d";
            $orderdrive = $tab->execute($order_drive,[1,$drivedataid]);

            //将代驾分配给车辆
            $order_car = "UPDATE order_car SET driveid = %d WHERE id = %d";
            $ordercar = $tab->execute($order_car,[$driveid,$cardaleid]);

        }




        if ($orderdrive > 0 && $ordercar > 0 && $old >0 && $up_data > 0 && $add > 0){
            $tab->commit();
            $this->ajaxReturn(array('error'=>0));
        } else {
            $this->ajaxReturn(array('error'=>1));
            $tab->rollback();
        }
    }

    //订单司机删除方法
    public function delOrderDrive(){
        $tab = M();
        $id = I('get.id');
        $driveid = I('get.driveid');
        $order_state = I('get.order_state');
        $orderid = I('get.orderid');
        $tab->startTrans();

        $sql = "SELECT distribution FROM order_drive WHERE id=%d";
        $state=$tab->query($sql,[$id]);
        if ($state[0]['distribution'] > 0){
            $this->ajaxReturn(array('error'=>2));
        }else {
            $order_drive = "DELETE FROM order_drive  WHERE id = %d";
            $orderdrive = $tab->execute($order_drive,[$id]);

            // 重新统计订单司机人数
            $drive = "SELECT COUNT(id) FROM order_drive WHERE (orderid = %d) AND (is_del=%d OR is_del=%d)";
            $drivecount = $tab->query($drive,[$orderid,0,2]);

            $order = "UPDATE `order` SET drive_number = %d WHERE id=%d";
            $re = $tab->execute($order,[$drivecount[0]['count(id)'],$orderid]);

            $car_driverinfo = "UPDATE car_driverinfo SET state = %d WHERE id = %d";
            $cardriverinfo = $tab->execute($car_driverinfo,[0,$driveid]);

        }

        if ($orderdrive > 0 && $cardriverinfo > 0 && $re > 0){
            $tab->commit();
            $this->ajaxReturn(array('error'=>0));
        }else{
            $tab->rollback();
            $this->ajaxReturn(array('error'=>1));
        }

    }

    //    大客户指派代驾方法
    public function max_driver($key){
        $table = M();
        $table->startTrans();
        $arr = $key['be_driver'];
        $drive_state = $key['drive_state'];
        $driver_price = $key['driverprice'];
        $pieces = explode(",", $arr);
        $info = array();
        $count  =  count($pieces);
        $num = 0;
        for ($i=0;$i<$count;$i++){
            $info['orderid'] = $key['id'];
            $info['driveid'] = $pieces[$i];
            $add = $table->table('order_drive')->add($info);

            $where_driver['id'] = $pieces[$i];;
            $data_car['state'] = 1;
            $re = $table->table('car_driverinfo')->where($where_driver)->save($data_car);

            if ($add !== false && $re !== false){
                $num += 1;
            }
        }
        $sql = "SELECT COUNT(id) FROM order_drive WHERE orderid = %d";
        $car_count = $table->query($sql,$key['id']);

        if ($drive_state == 0){
            $order_state['drive_state'] = 1;
        }
        $id['id'] = $key['id'];
        $order_state['drive_number'] = $car_count[0]['count(id)'];
        $order_state['driver_price'] = $driver_price;
        $order = $table->table('order')->where($id)->save($order_state);

        if ($order !== false && $count == $num){
            $table->commit();
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $key['id'], '大客户指派代驾', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
                exit('<script type="text/javascript">alert("指派代驾成功");location.href="'.U('Order/customerBig').'";</script>');
            }
        } else {
            $table->rollback();
            exit('<script type="text/javascript">alert("指派代驾失败");</script>');
        }



    }
//    指派代驾方法
    public function driver($key){
        $m = M();
        $_POST = $key;

        $rules = array(
            array('be_driver','require','请为客户指派代驾人员！',0), //默认情况下用正则进行验证
        );
        if (!$m->table('order')->validate($rules)->create()){
            exit($m->table('order')->getError());
        }else{
            $where['id'] = I('post.id');
            $where_driver['id'] = I('post.be_driver');
            $driver_sql = "SELECT a.be_driver,a.car_id,a.pk_date,a.re_date,a.price_rec,a.pre_price,a.drive_state,a.order_state,b.usertype FROM `order` a LEFT JOIN work_member b ON a.uid=b.id WHERE a.id = %d";
            $driver_id = $m->query($driver_sql,$where);
            $driver_id = $driver_id[0];
//            $driver_id = $m->table('order')->where($where)->field('be_driver,car_id,pk_date,re_date,price_rec,pre_price,drive_state,order_state')->find();
            if ($driver_id['be_driver'] != '' && $driver_id['be_driver'] != $where_driver['id']){
                $m->table('car_driverinfo')->where(array('id'=>$driver_id['be_driver']))->save(array('state'=>0));
            }/* elseif (!$driver_id['car_id']){
                $data['order_state'] = 1;
            }else{
                $data['order_state'] = 2;
            }*/
            if (I('post.be_driver')){
                $num = strtotime($driver_id['re_date']) - strtotime($driver_id['pk_date']);
                $d = floor($num / 3600 / 24);
                $h = floatval($num / 3600);  //%取余
                $h = ceil($h);
                $h = $h % 24;
                if ($h>2 && $h<=6){
                    $d +=0.5;
                }elseif ($h > 6){
                    $d +=1;
                }
                $drive_state = 1;
            }else{
                $d = 0;
                $drive_state = 0;
            }

            if ($driver_id['order_state'] == 0 && $driver_id['usertype'] != 1){
                if ($driver_id['drive_state']){
                    if (!$drive_state){
                        $driver_id['pre_price'] -= $d * 200;
                        if ($driver_id['pre_price'] < 0){
                            $driver_id['pre_price'] = 0;
                        }
                    }
                }else{
                    if ($drive_state){
                        $driver_id['pre_price'] += $d * 200;
                    }
                }
            }

            $data_driver['state'] = 1;
            $driverstate = $m->table('car_driverinfo')->where($where_driver)->save($data_driver);
            $data['driver_price'] = $d * 200;
            $data['be_driver'] = I('post.be_driver');
            $data['drive_state'] = $drive_state;
            $data['remarks'] = I('post.remarks');
            $data['pre_price'] = $driver_id['pre_price'];
            if ($driver_id['drive_state']){
                $add_price = 0;
            }else{
                $add_price = $data['driver_price'];
            }
            $data['price_rec'] = $driver_id['price_rec'] + $add_price;
            $charge = $m->table('order')->where($where)->save($data);

            if ($charge && $driverstate){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog('order', $where['id'], 'driver', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                    if($_POST['big'] == 1){
                        exit('<script type="text/javascript">alert("指派代驾成功");location.href="'.U('Order/customerBig').'";</script>');
                    }
                    exit('<script type="text/javascript">alert("指派代驾成功");location.href="'.U('Order/supervise?act=send').'";</script>');
                }
            }else{
                exit('<script type="text/javascript">alert("指派代驾失败");history.go(-1);</script>');
            }
        }
    }
    //大客户取车或送车方法
    public function max_give_car($key){
        $tab = M();
        $tab->startTrans();
        //接受数据
        $id = I('post.id');//订单id
        $send_location = I('post.send_location');//送车地址
        $pk_date = I('post.pk_date');//取车时间
        $re_date = I('post.re_date');//还车时间
        $u_price = I('post.u_price');//车辆成本
        $remarks = I('post.remarks');//备注
        $driver_price = I('post.driver_price');//代驾费
        $price_rec = I('post.price_rec');//订单金额
        $time = strtotime($re_date) - strtotime($pk_date);
        if ($time <= 0){
            //对还车时间进行计算，并判定是否小于取车时间
            exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
        }else{
            $order_cost = "SELECT a.id,a.charge_sum,b.collections_rec FROM order_cost a LEFT JOIN `order` b ON a.order_id=b.id WHERE (a.is_del = %d) AND (a.order_id = %d) AND (a.pay_way > %d)";
            $cost = $tab->query($order_cost,[0,$id,0]);

            //操作订单表
            $sql = "UPDATE `order` SET price_rec=%d,send_location='%s',pk_date='%s',re_date='%s',cost_price='%s',remarks='%s',driver_price='%s',order_state=%d WHERE id=%d";
            $re = $tab->execute($sql,[$price_rec,$send_location,$pk_date,$re_date,$u_price,$remarks,$driver_price,3,$id]);

            //操作交易表
            $sum = round($price_rec - $cost[0]['collections_rec']);
            $order_cost = "UPDATE order_cost SET charge_sum = %d,charge_time='%s' WHERE is_del=%d AND order_id=%d AND pay_way=%d";
            $ordercost = $tab->execute($order_cost,[$sum,date('Y-m-d H:i:s',time()),0,$id,0]);
            if ($re !== false && $ordercost > 0){
                $tab->commit();
                exit('<script type="text/javascript">alert("客户以取车成功，订单状态改变");location.href="'.U('Order/customerbig').'";</script>');
            } else{
                $tab->rollback();
                exit('<script type="text/javascript">alert("(取/送)车失败");history.go(-1);</script>');
            }
        }


    }



//    取车或送车方法
    public function take_car($key){
        $m = M();
        $_POST = $key;
        $rules = array(
            array('pk_date', 'require', '取车时间不能为空！', 0), //默认情况下用正则进行验证
            array('re_date', 'require', '还车时间不能为空！', 0), //默认情况下用正则进行验证
            array('u_price', 'require', '车辆单价不能为空！', 0), //默认情况下用正则进行验证
            array('pre_price', 'require', '预付金额不能为空！', 0), //默认情况下用正则进行验证
        );
        if (!$m->table('order')->validate($rules)->create()) {
            $error = $m->table('order')->getError();
            exit("<script type='text/javascript'>alert('".$error."');history.go(-1);</script>");
        } else {
            $where['id'] = I('post.id');
            $where_car['id'] = I('post.car_id');
            $car_costprice = $m->table('car_carinfo')->field('costprice')->where($where_car)->find();       //获取车辆成本

            $car_is = $m->table('order')->field('car_id, re_date, collections_rec, agent_id')->where($where)->find();   //获取该订单的车辆id
            $data['re_date'] = $car_is['re_date'];                                                                      //顺便获取还车时间
            $data['pk_date'] = I('post.pk_date');
            $data['agent_id'] = $car_is['agent_id'];
            $pk_date = strtotime($data['pk_date']);                 //转换取车时间
            $re_date = strtotime($data['re_date']);                 //转换还车时间
            $car_time = $re_date - $pk_date;
            if ($car_time < 0){                                    //对还车时间进行计算，并判定是否小于取车时间
                exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
            }
            if ($car_is['car_id'] != $where_car['id']){                                                     //判断订单是否改派车辆
                $where_two['id'] = $where_one['car_id'] = $car_is['car_id'];
                $where_one['order_state'] = array('lt','4');
                $where_one['is_del'] = 0;
                $car = $m->table('order')->field('car_id,id')->where($where_one)->select();
                if (count($car)<2){
                    $car_state['usestatus'] = 0;
                    $m->table('car_carinfo')->where($where_two)->save($car_state);
                }
            }
            $data_car['usestatus'] = 1;
            $m->table('car_carinfo')->where($where_car)->save($data_car);

            $data['drive_state'] = I('post.drive_state');
            if ($data['drive_state'] == 1){
                $data['driver_price'] = I('post.driver_price');
            }
            $car_day = ($car_time/86400);                                //把租车时间转换成天数（有小数）
            $data['cost_price'] = $car_costprice['costprice'];//车辆成本

            $data['price_rec'] = I('post.price_rec');
            $data['carmodelid'] = I('post.carmodelid');
            $data['send_location'] = I('post.send_location');
            $data['pre_price'] = $car_is['collections_rec'];
            $data['car_id'] = I('post.car_id');
            $data['remarks'] = I('post.remarks');
            $data['order_state'] = 3;
            $take_car = $m->table('order')->where($where)->save($data);
            if ($take_car) {
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog('order', $where['id'], 'take_car', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    if($_POST['big'] == 1){
                        exit('<script type="text/javascript">alert("客户以取车，订单状态改变");location.href="'.U('Order/customerbig').'";</script>');
                    }
                    exit('<script type="text/javascript">alert("客户以取车，订单状态改变");location.href="' . U('Order/supervise?act=send') . '";</script>');
                }
            } else {
                exit('<script type="text/javascript">alert("订单状态改变失败");history.go(-1);</script>');
            }
        }
    }
//    成本录入
    public function cost($key){
        $m = M();
        $_POST = $key;
        $where['id'] = I('post.id');
        $data['oil_price'] = I('post.oil_price');
        $data['tolls'] = I('post.tolls');
        $data['wash_price'] = I('post.wash_price');
        $data['re_price'] = I('post.re_price');
        $data['remarks'] = I('post.remarks');           //备注
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'cost', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                if($_POST['big'] == 1){
                    exit('<script type="text/javascript">alert("成本录入成功");location.href="'.U('Order/customerBig').'";</script>');
                }
                exit('<script type="text/javascript">alert("成本录入成功");location.href="'.U('Order/returnCar?act=cost').'";</script>');
            }

        }else{
            exit('<script type="text/javascript">alert("成本录入失败");history.go(-1);</script>');
        }
    }

    //    续租方法
    public function max_relet($key){
        $tab = M();
        $_POST = $key;
        $tab->startTrans();
        //获取数据
        $orderid = I('post.id');                                                                    //订单id
        $pk_car = I('post.pk_date');                                                                //获取取车时间
        $re_car = I('post.re_date');                                                                //获取还车时间
        $price_rec = I('post.price_rec');                                                              //获取订单金额
        $cost_price = I('post.u_price');                                                              //获取车辆成本
        $driver_price = I('post.driver_price');                                                      //获取代驾费
        $remarks = I('post.remarks');                                                               //备注
        $pk_time = strtotime($pk_car);                                                              //转换取车时间
        $re_time = strtotime($re_car);                                                              //转换还车时间
        $car_time = $re_time - $pk_time;

        if ($car_time <= 0){                                                             //对还车时间进行计算，并判定是否小于取车时间
            exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
        }
        $list = $this->judgeOrdercar($orderid,$pk_car,$re_car,'max_relet','','');

        if ($list){                                                                      //判断$car_tf的值，如果为0，说明该车续租时间和其他订单有冲突
            $msg = '';
            foreach ($list as $k => $v){
                $msg .= '当前订单['.$v['carno'].']车辆续租时间和'.$v['id'].'号订单产生冲突!\\n';
            }
            exit('<script type="text/javascript">alert("'.$msg.'");history.go(-1);</script>');
        } else {
            $sql = "UPDATE `order` SET re_date = '%s',price_rec=%d,cost_price=%d,driver_price=%d,remarks='%s' WHERE id=%d";
            $order  = $tab->execute($sql,[$re_car,$price_rec,$cost_price,$driver_price,$remarks,$orderid]);

            $ordercar = "UPDATE order_car SET endtime = '%s' WHERE orderid = %d AND is_del = %d";
            $car = $tab->execute($ordercar,[$re_car,$orderid,0]);

            $code = "UPDATE order_cost SET charge_sum = %d WHERE order_id = %d AND pay_way = %d";
            $cost = $tab->execute($code,[$price_rec,$orderid,0]);
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog('order',$orderid, 'max_relet',Date('Y-m-d H:i:sA'),$auserInfo['name']);
            if ($order !==false && $car !== false && $cost !== false){
                $tab->commit();
                exit('<script type="text/javascript">alert("续租成功!");location.href="'.U('Order/customerbig').'";</script>');
            } else {
                $tab->rollback();
                exit('<script type="text/javascript">alert("续租失败!");history.go(-1);</script>');
            }
        }

    }


    //派车，送车，续租判断车辆是否有冲突的函数
    /**
     * @param $orderid 订单id
     * @param $pk_car 取车时间
     * @param $re_car 还车时间
     * @param $num 判断是哪个方法发起请求
     * @param $carno 车牌号（可选）
     * @param $brandid 品牌id（派车操作必选）
     * @return  返回数据
     */
    public function judgeOrdercar($orderid,$pk_car,$re_car,$num,$carno,$brandid){
        $tab = M();
        //续租操作
        if ($num == 'max_relet'){
            //查询当前订单下的用车id
            $ordercar = "SELECT carid FROM order_car WHERE (orderid=%d) AND (is_del = %d)";
            $car = $tab->query($ordercar,[$orderid,0]);
            //定义数组存储当前订单的车辆id
            $carid = [];
            //循环将当前订单下的车辆id组装成数组
            foreach ($car as $str){
                array_push($carid,$str['carid']) ;
            }
            ////将车辆id数组转换为字符串
            $carid = implode(',', $carid);
            $sql = "SELECT a.*,b.carno FROM (
	SELECT a.id, a.pk_date, a.re_date,a.car_id FROM`order` a LEFT JOIN work_member b ON a.uid = b.id WHERE (b.usertype = 0) AND (a.is_del=0) AND a.order_state < 4 AND a.id != %d 
	UNION ALL
 	SELECT a.id, c.begintime as pk_date, c.endtime as re_date,c.carid as car_id FROM `order` a LEFT JOIN work_member b ON a.uid = b.id LEFT JOIN order_car c ON a.id=c.orderid WHERE (b.usertype = 1) AND a.order_state < 4 AND a.id !=%d 
) a LEFT JOIN car_carinfo b ON a.car_id = b.id WHERE a.car_id IN (%s) 
 AND (('%s' BETWEEN a.pk_date AND a.re_date) OR ('%s' BETWEEN a.pk_date AND a.re_date) OR ((unix_timestamp('%s') < unix_timestamp(a.pk_date)) AND (unix_timestamp('%s') > unix_timestamp(a.re_date))))";
            if ($brandid > 0){
                $list = $tab->query($sql,[$orderid,$orderid,$brandid,$pk_car,$re_car,$pk_car,$re_car]);//根据当前车辆id到订单表中进行查询
            } else {
                $list = $tab->query($sql,[$orderid,$orderid,$carid,$pk_car,$re_car,$pk_car,$re_car]);//根据当前车辆id到订单表中进行查询
            }


            return $list;
        }

        //派车操作
        if ($num == '派车'){
            $sql = "SELECT * FROM (
			SELECT a.id, a.pk_date, a.re_date,a.car_id,c.brand as brand_id,c.carno,c.costprice,c.carproperty,e.brand,d.carmodelname,d.shortdayprice,d.weekdayprice,d.monthdayprice FROM`order` a LEFT JOIN work_member b ON a.uid = b.id LEFT JOIN car_carinfo c ON a.car_id = c.id
			LEFT JOIN car_carmodel d ON c.carmodel = d.id LEFT JOIN car_barand e ON c.brand = e.id
			WHERE (b.usertype = 0) AND (a.is_del=0) AND a.order_state < 4 
			UNION ALL
			SELECT a.id, c.begintime as pk_date, c.endtime as re_date,c.carid as car_id,e.brand as brand_id,e.carno,e.costprice,e.carproperty,g.brand,f.carmodelname,f.shortdayprice,f.weekdayprice,f.monthdayprice  FROM `order` a 
			LEFT JOIN work_member b ON a.uid = b.id LEFT JOIN order_car c ON a.id=c.orderid LEFT JOIN car_carinfo e ON c.carid=e.id
			LEFT JOIN car_carmodel f ON e.carmodel = f.id LEFT JOIN car_barand g ON e.brand=g.id
			WHERE (b.usertype = 1) AND a.order_state < 4  
		) a WHERE brand_id=%d AND carno LIKE '%%%s%%' ";

            $list = $tab->query($sql,[$brandid,$carno]);//根据当前车辆id到订单表中进行查询


            $sql = "select a.id,a.carno,a.costprice,a.carproperty,b.id as carmodelid,b.carmodelname,b.shortdayprice,b.weekdayprice,b.monthdayPrice,c.brand from car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel=b.id LEFT JOIN car_barand c ON a.brand=c.id where (a.brand = %d) AND (a.usestatus = 0) AND (a.isdel = 0) AND (a.isdiscount = 0) AND (a.carno LIKE '%%%s%%')";
            $carinfo = $tab->query($sql,[$brandid,$carno]);
            foreach ($list as $key => $value){                                                           //对这些记录进行循环，并判定是否符合再租条件
                $pk_date[$key] = $this->time_js($re_car,$value['pk_date']);                                //再租条件一：还车时间大于记录中的取车时间
                $re_date[$key] = $this->time_js($value['re_date'],$pk_car);                                //再租条件二：取车时间大于记录中的还车时间
                if ($pk_date[$key] || $re_date[$key]){                                                     //满足其中一个再租条件便，把该车辆id记录在$car_id数组中
                    if ($car_id[$value['car_id']] !== 0){
                        $car_id[$value['car_id']] = $value['car_id'];
                    }
                }else{
                    $car_id[$value['car_id']] = 0;                                                                       //如果某条记录两个条件都不满足，那把$car_id中关于该车的记录清0
                }
            }
            $car_id = array_unique($car_id);
//                循环$car_id，查找其中是否有符合条件的车辆，并把符合条件的车辆信息拿出来
            foreach ($car_id as  $value2){
                if ($value2 != 0){
                    $sql2 = "select a.id,a.carno,a.costprice,a.carproperty,b.id as carmodelid,b.carmodelname,b.shortdayprice,b.weekdayprice,b.monthdayPrice,c.brand from car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel=b.id LEFT JOIN car_barand c ON a.brand=c.id where  (a.isdiscount = 0) AND (a.id =%d )";
                    $car_carinfo3 = $tab->query($sql2,[$value2]);
                    if ($car_carinfo3){
                        $carinfo[] = $car_carinfo3[0];
                    }
                }
            }
              return $carinfo;
        }

    }





//    续租方法
    public function relet($key){
        $tab = M();
        $_POST = $key;
        $orderid = I('post.id');
        $carid = I('post.car_id');
        $price_rec = I('post.price_rec');                                                             //应付金额
        $driver_price = I('post.driver_price');                                                         //代驾费
        $remarks = I('post.remarks');                                                               //备注
        $re_car = I('post.re_date');                                                                //获取还车时间
        $pk_car = I('post.pk_date');                                                                //获取取车时间
        $pk_time = strtotime($pk_car);                                                              //转换取车时间
        $re_time = strtotime($re_car);                                                              //转换还车时间
        $car_time = $re_time - $pk_time;
        if ($car_time <= 0){                                                             //对还车时间进行计算，并判定是否小于取车时间
            exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
        }

        $list = $this->judgeOrdercar($orderid,$pk_car,$re_car,'max_relet','',$carid);
        if ($list){                                                                      //判断$car_tf的值，如果为0，说明该车续租时间和其他订单有冲突
            $msg = '';
            foreach ($list as $k => $v){
                $msg .= '当前订单['.$v['carno'].']车辆续租时间和'.$v['id'].'号订单产生冲突!\\n';
            }
            exit('<script type="text/javascript">alert("'.$msg.'");history.go(-1);</script>');
        } else {
            $sql = "UPDATE `order` SET re_date = '%s',price_rec=%d,driver_price=%d,remarks='%s' WHERE id=%d";
            $order  = $tab->execute($sql,[$re_car,$price_rec,$driver_price,$remarks,$orderid]);

            $code = "UPDATE order_cost SET charge_sum = %d WHERE order_id = %d AND pay_way = %d";
            $cost = $tab->execute($code,[$price_rec,$orderid,0]);
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog('order',$orderid, 'relet',Date('Y-m-d H:i:sA'),$auserInfo['name']);
            if ($order !== false && $cost !== false){
                $tab->commit();
                exit('<script type="text/javascript">alert("续租车辆成功");location.href="'.U('Order/returnCar?act=relet').'";</script>');
            } else {
                $tab->rollback();
                exit('<script type="text/javascript">alert("续租失败!");history.go(-1);</script>');
            }
        }
    }




    //大客户还车方法
    public function max_return_car(){
        $tab = M();
//        开启事务
        $tab->startTrans();
        //接收数据
        $id = I('post.id');//订单id
        $pre_price = I('post.price_rec');//应收金额(订单金额)
        $oil_price = I('post.oil_price');//油费
        $tolls = I('post.tolls');//过路费
        $pk_date = I('post.pk_date');//取车时间
        $re_date = I('post.re_date');//还车时间
        $wash_price = I('post.wash_price');//洗车费
        $re_price = I('post.re_price');//维修费
        $in_price = I('post.in_price');//开票金额
        $in_cost = I('post.in_cost');//开票成本
        $is_invoice = I('post.is_invoice');//是否开票
        $in_code = I('post.in_code');//发票号
        $in_dep = I('post.in_dep');//开票单位
        $remarks = I('post.remarks');//备注
        $u_price = I('post.u_price');//车辆成本
        $driver_price = I('post.driver_price');//代驾费
        $big = I('post.big');


//        操作订单order表
        $sql = "UPDATE `order` SET cost_price=%d,driver_price=%d,pk_date='%s',re_date='%s',price_rec=%d,order_state=%d,oil_price=%d,tolls=%d,wash_price=%d,re_price=%d,in_price=%d,in_cost=%d,remarks='%s',is_invoice=%d,in_code=%d,in_dep='%s' WHERE id=%d";
        $order = $tab->execute($sql,[$u_price,$driver_price,$pk_date,$re_date,$pre_price,4,$oil_price,$tolls,$wash_price,$re_price,$in_price,$in_cost,$remarks,$is_invoice,$in_code,$in_dep,$id]);

        //查询该订单下的所有用车
        $sql_ordercar = "SELECT id,carid FROM order_car WHERE (is_del=%d) AND (orderid=%d)";
        $ordercar = $tab->query($sql_ordercar,[0,$id]);

        for ($i=0;$i<count($ordercar);$i++){
            $upcar = "UPDATE order_car SET is_del = %d WHERE id = %d ";
            $re = $tab->execute($upcar,[3,$ordercar[$i]['id']]);
        }

        $cardid = [];
        foreach ($ordercar as $str){
            array_push($cardid,$str['carid']) ;
        }
        $pieceser = implode(',', $cardid);
        //将车辆改为空闲状态
        $cardeta = "UPDATE car_carinfo SET usestatus = %d WHERE id IN (%s)";
        $carinfo = $tab->execute($cardeta,[0,$pieceser]);
//        }

        //查询该订单下的代驾司机
        $orderdrive = "SELECT id,driveid FROM order_drive WHERE (is_del=%d) AND (orderid=%d)";
        $drive = $tab->query($orderdrive,[0,$id]);

        $num1 = 0;
        if (!empty($drive)){
            for ($j=0;$j<count($drive);$j++){
                //将代驾表数据标记为还车
                $updrive = "UPDATE order_drive SET is_del = %d WHERE id=%d";
                $re = $tab->execute($updrive,[2,$drive[$j]['id']]);
                if ($re !== false){
                    $num1 += 1;
                }
            }

            $driveid = [];
            foreach ($drive as $name){
                if ($name['driveid'] > 0){
                    array_push($driveid,$name['driveid']);
                }
            }
            $rs = implode(',',$driveid);
            //改变司机改为空闲状态
            $drivedata = "UPDATE car_driverinfo SET state = %d WHERE id IN (%s)";
            $driveinfo = $tab->execute($drivedata,[0,$rs]);
        } else{
            $driveinfo = 1;
        }



        //查看交易表记录信息
        $order_cost = "SELECT a.id,a.charge_sum,b.collections_rec FROM order_cost a LEFT JOIN `order` b ON a.order_id=b.id WHERE (a.order_id = %d) AND (a.is_del = %d) AND (a.pay_way=%d)";
        $cost = $tab->query($order_cost,[$id,0,0]);
        $num = count($cost);
        //判断交易记录是该追加还是修改
        if ($num > 0){
            $sum = round($pre_price - $cost[0]['collections_rec']);
            $order_cost = "UPDATE order_cost SET charge_sum = %d,charge_time='%s' WHERE is_del=%d AND order_id=%d AND pay_way=%d";
            $costinfo = $tab->execute($order_cost,[$sum,date('Y-m-d H:i:s',time()),0,$id,0]);
        } else {
            $costinfo = 1;
        }



        if ($order > 0 && $carinfo > 0 && $driveinfo > 0 && $costinfo > 0 && $re >0 && count($drive) == $num1){
            $tab->commit();
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $id, 'max_return_car', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                exit('<script type="text/javascript">alert("还车成功");location.href="'.U('Order/customerBig').'";</script>');
            }
        } else {
            $tab->rollback();
            exit('<script type="text/javascript">alert("失败!还车失败，或者状态更改失败");history.go(-1);</script>');
        }





    }


//    还车方法
    public function return_car($key){
        $m = M();
        $_POST = $key;

        $rules = array(
            array('in_code','require','发票号为必填项！',0), //默认情况下用正则进行验证
            array('in_price','require','开票金额为必填项！',0), //默认情况下用正则进行验证
            array('in_cost','require','开票成本为必填项！',0), //默认情况下用正则进行验证
            array('in_dep','require','开票单位为必填项！',0), //默认情况下用正则进行验证
        );
        $where['id'] = I('post.id');                            //订单id
        $car_id['id'] = I('post.car_id');                       //车辆id
        $driver_id['id'] = I('post.be_driver');                 //代驾人id

        $data['u_price'] = I('post.u_price');                   //获取单价
//        $data['deposit'] = I('post.deposit');                   //获取违章押金
        $data['remarks'] = I('post.remarks');                   //获取备注
//        $data['price_paided'] = I('post.price_paided');
//        $data['collections_rec'] = I('post.collections_rec');
        $data['price_rec'] = I('post.price_rec');               //获取应付金额
        $data['oil_price'] = I('post.oil_price');               //获取油费
        $data['tolls'] = I('post.tolls');                       //获取过路费
        $data['wash_price'] = I('post.wash_price');             //获取洗车费
        $data['re_price'] = I('post.re_price');                 //获取维修费
        $data['pk_date'] = I('post.pk_date');                   //获取取车时间
        $data['re_date'] = I('post.re_date');                   //获取还车时间
        $pk_date = strtotime($data['pk_date']);                 //转换取车时间
        $re_date = strtotime($data['re_date']);                 //转换还车时间
        $car_time = $re_date - $pk_date;
        if ($car_time <= 0){                                   //对还车时间进行计算，并判定是否小于取车时间
            exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
        }
        $data['drive_state'] = I('post.drive_state');
        if ($data['drive_state'] == 1){
            $data['driver_price'] = I('post.driver_price');
        }
        $data['order_state'] = 4;
        $data['is_invoice'] = I('post.is_invoice');

        if ($driver_id['id']){                                                         //判断是否有代驾人，更改代驾人状态
            $driver_state['state'] = 0;
            $m->table('car_driverinfo')->where($driver_id)->save($driver_state);
        }
        $carid['car_id'] = $car_id['id'];
        $carid['order_state'] = array('lt','4');
        $carid['is_del'] = 0;
        $car = $m->table('order')->where($carid)->select();                           //根据所还车辆id，在订单表中查询是否还有租用该车的订单
        if (count($car) < 2){                                                       //如果只有当前一条记录，则在还车后更改车辆为空闲状态，否则不对车辆状态进行改动
            $car_state['usestatus'] = 0;
            $m->table('car_carinfo')->where($car_id)->save($car_state);
        }
        $return_car = $m->table('order')->where($where)->save($data);
        if ($return_car){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'return_car', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                if($_POST['big'] == 1){
                    exit('<script type="text/javascript">alert("还车成功");location.href="'.U('Order/customerBig').'";</script>');
                }
                exit('<script type="text/javascript">alert("还车成功");location.href="'.U('Order/returnCar').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("失败!还车失败，或者状态更改失败");history.go(-1);</script>');
        }
    }
//    写入违章金额方法
    public function dp_price($key){
        $m = M();
        $_POST = $key;
        $where['id'] = I('post.id');
        $data['dp_price'] = I('post.dp_price');
        $data['remarks'] = I('post.remarks');
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){

            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'dp_price', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                if($_POST['big'] == 1){
                    exit('<script type="text/javascript">alert("违章金额以更改");location.href="'.U('Order/customerBig').'";</script>');
                }
                exit('<script type="text/javascript">alert("违章金额以更改");location.href="'.U('Order/orderEnd').'";</script>');
            }

        }else{
            exit('<script type="text/javascript">alert("违章金额更改失败");history.go(-1);</script>');
        }
    }
//    同意退还押金方法
    public function deposit($key){
        $m = M();
        $_POST = $key;
        $order_state = I('post.order_state');
        if ($order_state != 5 ){
            exit('<script type="text/javascript">alert("订单还未结账，无法继续操作");history.go(-1);</script>');
        }
        $where['id'] = I('post.id');
//        $data['deposit'] = I('post.deposit');
        $data['remarks'] = I('post.remarks');
        $drive_state = I('post.drive_state');
        if ($drive_state){                      //判断该订单是否代驾，
            $data['order_state'] = 7;           //如果是代驾，则无需退押金，直接结单
        }else{
            $data['order_state'] = 6;           //如果非代驾，则需要退还押金后才能结单
        }
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'deposit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("订单状态改变，可以退还押金");location.href="'.U('Order/orderEnd').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("状态改变失败");history.go(-1);</script>');
        }
    }

//    大客户违章录入完成
    public function deposit_big($key){
        $m = M();
        $_POST = $key;
        $order_state = I('post.order_state');
        $where['id'] = I('post.id');
//        $data['deposit'] = I('post.deposit');
        $data['remarks'] = I('post.remarks');
        $drive_state = I('post.drive_state');
        if ($order_state == 4){                     //判断是否已结账
            $data['order_state'] = 6;               //如果未结账则状态改为6（违章录入完成）
        }else if($order_state == 5){
            $data['order_state'] = 7;               //如果已结账则状态改为7（正常结单）
        }
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'deposit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("订单状态改变，可以退还押金");location.href="'.U('Order/customerbig').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("状态改变失败");history.go(-1);</script>');
        }
    }

    //大客户订单取消
    public function max_cancel($id){
        $tab = M();

        $id = I('get.id');
        //查询是否有代驾
        $sql = "SELECT id,driveid FROM order_drive WHERE  (is_del=0) AND (orderid=%d)";
        $drive = $tab->query($sql,[$id]);
        $num = count($drive);
        //查询是否已派车
        $car = "SELECT id,carid FROM `order_car` WHERE  (is_del=0) AND orderid=%d";
        $car_number = $tab->query($car,[$id]);
        $carnum = count($car_number);

        //取消订单
        $where['id'] = I('get.id');
        $data['order_state'] = 10;
        $data['car_number'] = 0;
        $cost = $tab->table('order')->where($where)->save($data);
        if ($cost !== false){
            //判断是否已经指派代驾
            if ($num > 0){
                //循环取消代驾
                for ($i=0;$i<$num;$i++){
                    $where_driver['id'] = $drive[$i]['driveid'];
                    $state['state']=0;
                    $re = $tab->table('car_driverinfo')->where($where_driver)->save($state);

                    if ($re !== false){
                        $where['id'] = $drive[$i]['id'];
                        $del['is_del']=1;
                        $rs=$tab->table('order_drive')->where($where)->save($del);
                    }

                }
            }

            if ($carnum > 0 ){
                for ($i=0;$i<$carnum;$i++){
                    $carid['id'] = $car_number[$i]['carid'];
                    $carstate['usestatus']=0;
                    $re = $tab->table('car_carinfo')->where($carid)->save($carstate);

                    if ($re !== false){
                        $carinfoid['id'] = $car_number[$i]['id'];
                        $del['is_del']=1;
                        $rs = $tab->table('order_car')->where($carinfoid)->save($del);
                    }
                }
            }
            if ($rs){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog('order', $id, 'max_cancel', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log){
                    exit('<script type="text/javascript">alert("订单取消成功");location.href="'.U('Order/customerbig').'";</script>');
                }
            }else{
                exit('<script type="text/javascript">alert("订单取消失败");location.href="'.U('Order/customerbig').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("订单取消失败");location.href="'.U('Order/customerbig').'";</script>');
        }


    }



//    取消订单方法
    public function cancel($key){
        $m = M();
        $_GET = $key;
        $where['id'] = I('get.id');
        $data['order_state'] = 10;
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){
            cancelCoupon($where['id']);  //修改订单获取的优惠信息
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'cancel', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                if ($log) {
                    cookie('id',null);
                    exit('<script type="text/javascript">alert("订单以取消，状态改变");location.href="'.U('Order/orderEnd').'";</script>');
                }
                exit('<script type="text/javascript">alert("订单以取消，状态改变");location.href="'.U('Order/orderSup').'";</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("取消结单操作失败");history.go(-1);</script>');
        }
    }
//    同意退款方法
    public function refund($key){
        $m = M();
        $_GET = $key;
        $where['id'] = I('get.id');
        $data['order_state'] = 12;
        $cost = $m->table('order')->where($where)->save($data);
        if ($cost!==false){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $where['id'], 'refund', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("以同意退款，状态改变");location.href="'.U('Order/supervise?act=send').'";</script>');
            }

        }else{
            exit('<script type="text/javascript">alert("操作失败，请重试");history.go(-1);</script>');
        }
    }
//    会议订单录入完成方法
    public function finish(){
        $m = M();
        $order_state = 1;
        $id = I('get.id');
        $sql = "UPDATE order_meeting SET order_state='%s' WHERE (id='%s')";
        $arr = [$order_state,$id];
        $cost = $m->execute($sql,$arr);
        if ($cost!==false){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order_meeting', $id, 'finish', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                exit('<script type="text/javascript">alert("订单录入完成，状态改变");location.href="'.U('Order/order_meeting').'";</script>');
            }

        }else{
            exit('<script type="text/javascript">alert("状态改变失败，请重试");history.go(-1);</script>');
        }
    }
    //订单详情页管理器
    public function orderInfo(){
        $m = M();
        if (isset($_GET['id'])){
            $id = $_GET['id'];
            $sql = "SELECT a.*,b.carmodelname,b.shortdayprice,b.weekdayprice,b.monthdayprice,b.barandid,c.username,c.phone,d.carno,d.carproperty,d.costprice,e.id as driverid,e.drivername,e.phone as driverphone FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id LEFT JOIN car_driverinfo e ON a.be_driver=e.id WHERE (a.is_del=0) AND (a.id=%d)";
            $ary = array($id);
            $orderinfo = $m->query($sql,$ary);
            $strAry = $this->carADHandle($orderinfo[0]['send_location']);
            $orderinfo[0]['send_location'] = $strAry[0][0];
            $orderinfo = $orderinfo[0];
            if ($orderinfo['be_driver']){                                    //判断是否有代驾人
                $where['id'] = $orderinfo['be_driver'];
                $driver = $m->table('car_driverinfo')->where($where)->find();
                $orderinfo['be_driver'] = $driver['drivername'];             //根据代驾人id 找出姓名，并替换id
            }
            $this->assign('list',$orderinfo);
            $where_cost['order_id'] = $id;                                  //根据订单id，找出该订单的交易记录
            $where_cost['order_class'] = array('neq',1);
            $where_cost['pay_way'] = array('gt','0');
            $cost = $m->table('order_cost')->where($where_cost)->select();
            $cost['long'] = count($cost);
            foreach ($cost as $key => $value) {
                switch ($value['pay_way']) {
                    case 1:
                        $cost[$key]['pay_way'] = '支付宝';
                        break;
                    case 2:
                        $cost[$key]['pay_way'] = '微信';
                        break;
                    case 3:
                        $cost[$key]['pay_way'] = '现金';
                        break;
                    case 4:
                        $cost[$key]['pay_way'] = '抵扣押金';
                        break;
                }
                switch ($value['charge_road']) {
                    case 1:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，预付了￥' . $value['charge_sum'] . '。（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 2:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，支付了￥' . $value['charge_sum'] . '，用于订单后期结账。（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 3:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，补交了￥' . $value['charge_sum'] . '，用于订单后期结账。（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 4:
                        $cost[$key]['charge_road'] = $value['charge_time'] . '，公司将客户的预付费用￥' . $value['charge_sum'] . '退还给用户（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 5:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，交付了违章押金共：￥' . $value['charge_sum'].'（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 6:
                        $cost[$key]['charge_road'] = $value['charge_time'] . '，公司将剩余违章押金￥' . $value['charge_sum'] . '退还给用户（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                    case 7:
                        $cost[$key]['charge_road'] = '该客户于' . $value['charge_time'] . '，补交了违章押金共：￥' . $value['charge_sum'].'（交易方式：'.$cost[$key]['pay_way'].'）';
                        break;
                }
            }
//            echo "<pre>";
//            print_r($cost);

            $this->assign('cost',$cost);
            $this->display('Order/orderinfo');
        }
    }

    public function max_orderInfo(){
        $m = M();
        $id = I('get.id');
        $sql = "SELECT a.id,a.in_code,a.car_number,a.drive_number,a.remarks,a.in_price,a.in_dep,a.in_cost,a.order_code,a.cost_price,a.send_location,a.driver_price,a.order_state,a.is_invoice,a.order_date,a.pk_date,a.re_date,a.price_rec,a.collections_rec,a.drive_state,a.pk_way,a.dp_price,a.oil_price,a.tolls,a.wash_price,a.re_price,a.is_invoice,b.username,b.phone,c.carmerber,d.drivemerber
 FROM `order` a LEFT JOIN work_member b ON a.uid=b.id LEFT JOIN (SELECT COUNT(orderid) as carmerber,orderid FROM order_car GROUP BY orderid) c ON a.id=c.orderid LEFT JOIN (SELECT COUNT(orderid) as drivemerber,orderid FROM order_drive GROUP BY orderid) d ON a.id=d.orderid WHERE a.id=%d";
        $arr = [$id];
        $list = $m->query($sql,$arr);
//        $strAry = $this->carADHandle($list[0]['send_location']);
//        $list[0]['send_location'] = $strAry[0][0];
        //计算租车租时长
        $num = strtotime($list[0]['re_date']) - strtotime($list[0]['pk_date']);
        $d = floor($num / 3600 / 24);
        $h = floatval($num / 3600);  //%取余
        $h = ceil($h);
        $h = $h % 24;
        $data = $d . "天" . $h . "小时<br>";
        $list[0]['time'] = $data;

        $trade = "SELECT charge_time,charge_road,charge_sum,pay_way FROM order_cost WHERE order_class = 2 AND order_id =%d ";
        $tradeinfo = $m->query($trade,$arr);
        $arraynum = count($tradeinfo);
        for ($i=0;$i<$arraynum;$i++){
            if ($tradeinfo[$i]['charge_road'] == 1){
                $tradeinfo[$i]['charge_road'] = '预付';
            }elseif ($tradeinfo[$i]['charge_road'] == 2){
                $tradeinfo[$i]['charge_road'] = '结账';
            }elseif ($tradeinfo[$i]['charge_road'] == 3){
                $tradeinfo[$i]['charge_road'] = '补收';
            }elseif ($tradeinfo[$i]['charge_road'] == 4){
                $tradeinfo[$i]['charge_road'] = '退款';
            }elseif ($tradeinfo[$i]['charge_road'] == 5){
                $tradeinfo[$i]['charge_road'] = '收违章押金';
            }elseif ($tradeinfo[$i]['charge_road'] == 6){
                $tradeinfo[$i]['charge_road'] = '退违章押金';
            }elseif ($tradeinfo[$i]['charge_road'] == 7){
                $tradeinfo[$i]['charge_road'] = '补交违章押金';
            }
            if ($tradeinfo[$i]['pay_way'] == 1){
                $tradeinfo[$i]['pay_way'] = '支付宝';
            } elseif ($tradeinfo[$i]['pay_way'] == 2){
                $tradeinfo[$i]['pay_way'] = '微信';
            }elseif ($tradeinfo[$i]['pay_way'] == 3){
                $tradeinfo[$i]['pay_way'] = '现金';
            }elseif ($tradeinfo[$i]['pay_way'] == 4){
                $tradeinfo[$i]['pay_way'] = '抵扣押金';
            }else{
                $tradeinfo[$i]['pay_way'] = '未支付';
            }
            $str[] = "该用户于".$tradeinfo[$i]['charge_time'].$tradeinfo[$i]['charge_road'].'￥'.$tradeinfo[$i]['charge_sum']."。(交易类型:".$tradeinfo[$i]['pay_way'].")";
            $list[0]['charge_road'] = $str;
        }
        $this->assign('list',$list[0]);
        $this->assign('arraynum',$arraynum);
        $this->display('max_orderinfo');
    }

    //品牌查询
    public function sqlBarand(){
        $tab = M();
        $sql = "SELECT id,brand FROM car_barand";
        $brand = $tab->query($sql);
        $this->ajaxReturn(array('brand'=>$brand));
    }


    //订单车辆信息
    public function carinfo(){
        $id = I('get.id');
        $order_state = I('get.order_state');
        $tab = M();
        //查询代驾司机
        $drive = "SELECT a.drivername FROM car_driverinfo a LEFT JOIN order_drive b ON a.id=b.driveid WHERE b.orderid=%d AND (is_del=0)";
        $arr = [$id];
        $driveinfo = $tab->query($drive,$arr);
        $drivecount = count($driveinfo);
        foreach ($driveinfo as $v)
        {
            $v = join(",",$v); //可以用implode将一维数组转换为用逗号连接的字符串
            $temp[] = $v;
        }
        $t="";
        foreach($temp as $v){
            $t.=$v.",";
        }
        if ($drivecount > 0 ){
            $t=substr($t,0,-1);
        } else {
            $t='未分配';
        }

        //车辆信息
        if ($order_state >= 4){
            $sql = "SELECT a.id,a.begintime,a.endtime,d.brand,d.id as brandid,c.carmodelname,c.carmodeltype,a.carid,b.carno,b.carproperty,e.drivername
FROM order_car a LEFT JOIN car_carinfo b ON a.carid=b.id LEFT JOIN car_carmodel c ON b.carmodel=c.id LEFT JOIN car_barand d ON c.barandid=d.id LEFT JOIN car_driverinfo e ON a.driveid=e.id  WHERE a.orderid=%d AND (a.is_del = 3  OR a.is_del =5)";
        } else {
            $sql = "SELECT a.id,a.begintime,a.endtime,d.brand,d.id as brandid,c.carmodelname,c.carmodeltype,a.carid,b.carno,b.carproperty,e.drivername
FROM order_car a LEFT JOIN car_carinfo b ON a.carid=b.id LEFT JOIN car_carmodel c ON b.carmodel=c.id LEFT JOIN car_barand d ON c.barandid=d.id LEFT JOIN car_driverinfo e ON a.driveid=e.id  WHERE a.orderid=%d AND (a.is_del = 0)";
        }

        $ary = [$id];
        $car = $tab->query($sql,$ary);
        $count = count($car);
        for ($i = 0;$i<$count;$i++){
            if ($car[$i]['carproperty'] == 1){
                $car[$i]['carproperty'] = '自有';
            } elseif ($car[$i]['carproperty'] == 2){
                $car[$i]['carproperty'] = '外调';
            } elseif ($car[$i]['carproperty'] == 3){
                $car[$i]['carproperty'] = '代理商';
            }

            if ($car[$i]['carmodeltype'] == 1){
                $car[$i]['carmodeltype'] = '商务车';
            }elseif ($car[$i]['carmodeltype'] == 2){
                $car[$i]['carmodeltype'] = '越野车';
            }elseif ($car[$i]['carmodeltype'] == 3){
                $car[$i]['carmodeltype'] = '面包车';
            }elseif ($car[$i]['carmodeltype'] == 4){
                $car[$i]['carmodeltype'] = '轿车';
            }elseif ($car[$i]['carmodeltype'] == 5){
                $car[$i]['carmodeltype'] = '客车';
            }
        }

        //历史车辆查询
        $sqlcar = "SELECT a.id,a.is_del,a.begintime,a.endtime,d.brand,d.id as brandid,c.carmodelname,c.carmodeltype,a.carid,b.carno,b.carproperty,e.drivername
FROM order_car a LEFT JOIN car_carinfo b ON a.carid=b.id LEFT JOIN car_carmodel c ON b.carmodel=c.id LEFT JOIN car_barand d ON c.barandid=d.id LEFT JOIN car_driverinfo e ON a.driveid=e.id  WHERE a.orderid=%d AND (a.is_del = %d OR a.is_del = %d OR is_del = %d)";
        $sql_car = $tab->query($sqlcar,$id,1,2,4);
        $counts = count($sql_car);
        for ($i = 0;$i<$counts;$i++){
            if ($sql_car[$i]['carproperty'] == 1){
                $sql_car[$i]['carproperty'] = '自有';
            } elseif ($sql_car[$i]['carproperty'] == 2){
                $sql_car[$i]['carproperty'] = '外调';
            } elseif ($sql_car[$i]['carproperty'] == 3){
                $sql_car[$i]['carproperty'] = '代理商';
            }

            if ($sql_car[$i]['carmodeltype'] == 1){
                $sql_car[$i]['carmodeltype'] = '商务车';
            }elseif ($sql_car[$i]['carmodeltype'] == 2){
                $sql_car[$i]['carmodeltype'] = '越野车';
            }elseif ($sql_car[$i]['carmodeltype'] == 3){
                $sql_car[$i]['carmodeltype'] = '面包车';
            }elseif ($sql_car[$i]['carmodeltype'] == 4){
                $sql_car[$i]['carmodeltype'] = '轿车';
            }elseif ($sql_car[$i]['carmodeltype'] == 5){
                $sql_car[$i]['carmodeltype'] = '客车';
            }
            if ($sql_car[$i]['drivername'] == null){
                $sql_car[$i]['drivername'] = '未指定';
            } else{
                $sql_car[$i]['drivername'] = $sql_car[$i]['drivername'];
            }
            if ($sql_car[$i]['is_del'] == 1){
                $sql_car[$i]['is_del'] = '更还车辆';
            }
            if ($sql_car[$i]['is_del'] == 2){
                $sql_car[$i]['is_del'] = '取消车辆';
            }
            if ($sql_car[$i]['is_del'] == 4){
                $sql_car[$i]['is_del'] = '更换代驾';
            }
        }
        echo json_encode(array('carinfo'=>$car,'drivename'=>$t,'historicaldata'=>$sql_car));



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

    //添加订单信息
    public function add(){
        $model = M();
        //获取服务器时间
        $time =  Date('Ymd',time());
        $this->assign('time',$time);

        //查询车辆品牌
        $where_bar['isdel'] = 0;
        $barand = $model->table('car_barand')->field('id,brand')->where($where_bar)->select();
        $this->assign('barand',$barand);
        $rules = array(
            array('uid','require','必须选择客户编号！',0), //默认情况下用正则进行验证
            array('carmodelid','require','车型不能为空！',0), //默认情况下用正则进行验证
            array('trade_no','require','订单交易号为空！',0), //默认情况下用正则进行验证
            array('order_date','require','下单时间必须填写！',0), //默认情况下用正则进行验证
            array('re_date','require','还车时间必须填写！',0), //默认情况下用正则进行验证
            array('u_price','require','车辆单价必须填写！',0), //默认情况下用正则进行验证
            array('pre_price','require','预付金额必须填写！',0), //默认情况下用正则进行验证
            array('price_rec','require','应收金额必须填写！',0), //默认情况下用正则进行验证
            array('deposit','require','违章押金必须填写！',0), //默认情况下用正则进行验证

        ) ;
        if($_POST){
            if (!$model->table('order')->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $error = $model->table('order')->getError();
                exit("<script type='text/javascript'>alert('".$error."');history.go(-1);</script>");
            }else{
//计算租车租时长
                $num = strtotime(I('post.re_date')) - strtotime(I('post.pk_date'));
                $d = floor($num / 3600 / 24);
                $h = floatval($num / 3600);  //%取余
                $h = ceil($h);
                $h = $h % 24;
                $d += $this->sumData($h);

                $all_price = I('post.all_price');
                $user_id = I('post.uid');
                $active = coupon_active(2,$user_id);                 //查询可参与的活动
                if ($active['activity_num'] != 0){          //如果有活动可参与则传入下单时间,用车时间进行判定
                    $active_re = couponClass($active['activity'],Date('Y-m-d H:i:s'),$all_price,$_POST['pk_date'],$_POST['re_date']);
                    if ($active_re[0]['coupon_pass']){
                        if ($active_re[0]['coupon_pass'][2]){   //如果该活动有直接减免的优惠,并且该订单符合该优惠条件,则改变订单总价
                            $all_price -= $active_re[0]['coupon_pass'][2]['specific'];
                            $grant_coupon = reliefCoupon($user_id,$active_re[0]['coupon'.$active_re[0]['coupon_pass'][2]['coupon_num']]);
                            $data['activity'] = $active_re[0]['id'];
                        }

                        foreach ($active_re[0]['coupon_pass'] as $key => $val){ //对符合条件的优惠方式进行循环
                            $grant[$key] = $active_re[0]['coupon'.$val['coupon_num']];
                        }

                        if ($active_re[0]['coupon_pass']){
                            //发放优惠劵
                            $coupun_gent = grantCoupon($user_id,$grant);
                            if ($coupun_gent['add']){
                                if ($grant_coupon){
                                    $grant_coupon .= ','.$coupun_gent['add'];
                                }else{
                                    $grant_coupon = $coupun_gent['add'];
                                }
                                $data['activity'] = $active_re[0]['id'];
                            }
                        }
                    }
                }


                //订单编号：当前时间戳加上5位随机数
                $order_code = orderCode();
                //生成订单时间
                $order_date = date("Y-m-d H:i:s", time());
                if ($grant_coupon){
                    $data['grant_coupon'] = $grant_coupon;
                }
                $data['uid'] = I('post.uid');
                $data['carmodelid'] = I('post.carmodelid');
                $data['order_code'] =$order_code;
                $data['order_date'] = $order_date;
                $data['pk_date'] = I('post.pk_date');
                $data['re_date'] = I('post.re_date');
                $pk_date = strtotime($data['pk_date']);                 //转换取车时间
                $re_date = strtotime($data['re_date']);                 //转换还车时间
                if (($pk_date - $re_date) > 0){                         //对还车时间进行计算，并判定是否小于取车时间
                    exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
                }
                $data['u_price'] = I('post.u_price');                   //获取单价
                $data['pre_price'] = $all_price;               //获取预付金额
                $data['price_rec'] = $all_price;
                $data['pk_way'] = I('post.pk_way');
                $data['order_state'] = 0;
                $data['order_type'] = 1;
                $data['drive_state'] = I('post.drive_state');
                $data['remarks'] = I('post.remarks');
                if ($data['drive_state'] == 1){                        //判断是否代驾
                    $data['driver_price'] = I('post.driver_price');    //获取代驾费
                }/*else{
                    $data['deposit'] = I('post.deposit');              //如果无需代驾则不需要交违章押金
                }*/
                if($data['pk_way'] == 2){                              //判断取车方式
//                    $data['send_price'] = I('post.send_price');      //获取送车费
                    $data['send_location'] = I('post.send_location');
                }
                $add = $model->table('order')->add($data);
                if($add){
                    $order_cost['order_id'] = $add;                  //订单id
                    $order_cost['charge_sum'] = $data['pre_price'];  //交易金额
                    $order_cost['charge_road'] = 1;                  //收费项目（1、预付）
                    $order_cost['charge_time'] = $order_date;        //添加时间
                    $order_cost['trade_code'] = orderCostOrderCode();//交易订单号
                    $model->table('order_cost')->add($order_cost);
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog('order', $add, 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id',null);
                        exit('<script type="text/javascript">alert("添加成功");location.href="'.U('Order/supervise?act=send').'";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }else{
            $active = coupon_active(2);
            if ($active['activity_num'] != 0){
                $act['num'] = $active['activity_num'];
                foreach ($active['activity'] as $key => $val){
                    $act['act'][$key]['id'] = $val['id'];
                    $act['act'][$key]['name'] = $val['name'];
                    $act['act'][$key]['start_time'] = $val['start_time'];
                    $act['act'][$key]['end_time'] = $val['end_time'];
                    $act['act'][$key]['act_synopsis'] = $val['act_synopsis'];
                    $act['act'][$key]['act_content'] = $val['act_content'];
                }

                $active = json_encode($active['activity']);
                $this->assign('active',$active);   //具体活动及优惠方式
            }else{
				$act['num'] = 0;
			}
			$this->assign('act',$act);  //活动id及活动名
            $this->display('Order/add');
        }
    }

    //添加大客户订单信息
    public function add_big(){
        $model = M();
        $rules = array(
            array('uid','require','必须选择客户编号！',0), //默认情况下用正则进行验证
            array('order_date','require','下单时间必须填写！',0), //默认情况下用正则进行验证
            array('re_date','require','还车时间必须填写！',0), //默认情况下用正则进行验证
            array('u_price','require','订单价格必须填写！',0), //默认情况下用正则进行验证

        );
        if($_POST){
            if (!$model->table('order')->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $error = $model->table('order')->getError();
                exit("<script type='text/javascript'>alert('".$error."');history.go(-1);</script>");
            }else{
                //订单编号：当前时间戳加上5位随机数
                $order_code = orderCode();
                //生成订单时间
                $order_date = date("Y-m-d H:i:s", time()) ;
                $data['uid'] = $where_user['id'] = I('post.uid');
                $usertype = $model->table('work_member')->field('usertype,check_cycle')->where($where_user)->find();
                $data['check_cycle'] = $usertype['check_cycle'];//获取结账周期
                if ($usertype == 0){
                    exit("<script type='text/javascript'>alert('该客户为普通用户，无法进行大客户订单录入');history.go(-1);</script>");
                }
//                $total = round(I('post.u_price') + I('post.driver_price'));
                $data['order_code'] =$order_code;
                $data['order_date'] = $order_date;
                $data['pk_date'] = I('post.pk_date');
                $data['re_date'] = I('post.re_date');
                $data['driver_price'] = I('post.driver_price');
                $pk_date = strtotime($data['pk_date']);                 //转换取车时间
                $re_date = strtotime($data['re_date']);                 //转换还车时间
                if (($pk_date - $re_date) > 0){                         //对还车时间进行计算，并判定是否小于取车时间
                    exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
                }
                //获取单价
                $data['price_rec'] = I('post.orderprice');
                $data['cost_price'] = I('post.cost_price');
                $data['pk_way'] = I('post.pk_way');
                $data['order_state'] = 0;
                $data['drive_state'] = I('post.drive_state');
                $data['remarks'] = I('post.remarks');
                $data['order_type'] = 1;
                $Whether = I('post.Whether');

                if($data['pk_way'] == 2){                              //判断取车方式
                    $data['send_location'] = I('post.send_location');
                }
                $add = $model->table('order')->add($data);
                if($add){
                    if ($Whether == 2){
                        $schoolid = I('post.school');
                        $bossid = I('post.uid');
                        $boss = $this->getBoosid($bossid);
                        if ($boss == 0){
                            $into = "INSERT INTO sc_school (schoolid,bossid) VALUES ('%d','%d')";
                            $model->execute($into,[$schoolid,$bossid]);
                        } else{
                            $set = "UPDATE sc_school SET schoolid = '%d' WHERE bossid = '%d'";
                            $model->execute($set,[$schoolid,$bossid]);
                        }
                    }
                    $order_cost['order_id'] = $add;                  //订单id
                    $order_cost['charge_sum'] = I('post.orderprice');  //交易金额
                    $order_cost['order_class'] = 2;                  //订单类型（2、大客户订单）
                    $order_cost['charge_road'] = 1;                  //收费项目（1、预付）
                    $order_cost['charge_time'] = $order_date;        //添加时间
                    $order_cost['trade_code'] = orderCostOrderCode();//交易订单号
                    $model->table('order_cost')->add($order_cost);
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog('order', $add, 'max_add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id',null);
                        exit('<script type="text/javascript">alert("添加成功");location.href="'.U('Order/customerbig').'";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }else{
            $m = M();
            $sql = "SELECT id,schoolname FROM sc_academy";
            $school = $m->query($sql);
            $this->assign('school',$school);


            $this->display('Order/add_big');
        }
    }

    //查询当前用户是否存在
    public function getBoosid($boosid){
        $m = M();
        $sql = "SELECT count(id) FROM sc_school WHERE bossid = '%d' ";
        $res = $m->query($sql,[$boosid]);
        return $res[0]['count(id)'];
    }

    public function currentUser(){
        $uid = I('post.id');
        $m = M();
        $sql = "SELECT a.schoolid,b.schoolname FROM sc_school a LEFT JOIN sc_academy b ON a.schoolid=b.id WHERE a.bossid = '%d' ";
        $list = $m->query($sql,[$uid]);
        $this->ajaxReturn($list);

    }
    //添加会议订单信息
    public function add_meeting(){
        $model = M();

        //查询客户信息，获取客户名及id
        $where_bar['isdel'] = 0;
        $barand = $model->table('car_barand')->field('id,brand')->where($where_bar)->select();
        $this->assign('barand',$barand);

        $rules = array(
            array('pre_price','require','会议名称必须填写！',0), //默认情况下用正则进行验证
            array('re_date','require','还车时间必须填写！',0), //默认情况下用正则进行验证
            array('u_price','require','车辆单价必须填写！',0), //默认情况下用正则进行验证
            array('price_rec','require','应收金额必须填写！',0), //默认情况下用正则进行验证

        );
        if($_POST){
            if (!$model->table('order_meeting')->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $error = $model->table('order_meeting')->getError();
                exit("<script type='text/javascript'>alert('".$error."');history.go(-1);</script>");
            }else{
                //订单编号：当前时间戳加上5位随机数
                $order_code = orderCode();
                //生成订单时间
                $order_date = date("Y-m-d H:i:s", time());
                $data['order_code'] =$order_code;
                $data['order_date'] = $order_date;
                $data['pk_date'] = I('post.pk_date');
                $data['re_date'] = I('post.re_date');
                $pk_date = strtotime($data['pk_date']);                 //转换取车时间
                $re_date = strtotime($data['re_date']);                 //转换还车时间
                if (($pk_date - $re_date) > 0){                         //对还车时间进行计算，并判定是否小于取车时间
                    exit("<script type='text/javascript'>alert('还车时间不能小于取车时间');history.go(-1);</script>");
                }
                $data['price_rec'] = I('post.price_rec');
                $data['meeting_name'] = I('post.meeting_name');
                $data['phone'] = I('post.phone');
                $data['contacts'] = I('post.contacts');             //联系人
                $data['car_num'] = I('post.car_num');               //车辆总数
                $data['expect_date'] = I('post.expect_date');       //预计还款日
                $data['out_cost'] = I('post.out_cost');
                $data['mixed_cost'] = I('post.mixed_cost');
                $data['order_state'] = 0;
                $data['remarks'] = I('post.remarks');
                $add = $model->table('order_meeting')->add($data);
                if($add){
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog('order_meeting', $add, 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id',null);
                        exit('<script type="text/javascript">alert("添加成功");location.href="'.U('Order/order_meeting').'";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }else{
            if (isset($_GET['action'])){
                $id = I('get.id');
                $id = I('get.id');
            }
            $this->display('Order/add_meeting');
        }
    }
    //订单信息逻辑删除方法 del（）
    public function del(){

        $delete = M('order');

        //获取需要操作的数据id
        $id = $_GET['id'];
        $where['id'] = $id;
        $arr['is_del'] = 1;
        $result = $delete->where($where)->save($arr);
        //记录操作日志
        if ($result){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                $this->success("删除成功!", U('Order/Index'));
            }
        } else {
            echo "<script>alert ('删除失败!');</script>";
            $delete->getError();
        }
    }
    //查询客户信息
    public function query(){
        $m = M();

        $reg['code'] = 0;
        $reg['msg'] = '未获得数据！';
        if (isset($_GET['phone_query'])) {
            $phone = I('get.phone_query');              //通过手机（账号）查询客户相关信息
            $where['phone'] = $phone;
            $user = $m->table('work_member')->field('id,username,state,phone,usertype')->where($where)->find();
            if ($user){
                if($user['state'] != 0 ){
                    $reg['msg'] = '该客户已被冻结，无法录单！';
                }else{
                    $reg['user'] = $user;
                    $activite = coupon_active(2,$user['id']);
                    $reg['activite'] = $activite['activity'];
                    $reg['code'] = 1;
                    $reg['msg'] = '已获得客户信息，请核实客户信息！';
                }
            }else{
                $reg['msg'] = '该用户不存在，请确实账号是否正确,或者添加为新用户！';
            }
        }
        echo json_encode($reg);
    }
    //查询车辆信息
    public function car_query(){
        $m = M();

        $reg['code'] = 0;
        $reg['msg'] = '未获得查询条件！';
        if (isset($_GET)) {
            $brand = I('get.brand');
            $query_info = I('get.query_info');
            $act = I('get.act');
            $re_car = I('get.re_date');
            $pk_car = I('get.pk_date');
            if ($act == 'brand') {
                $sql = "SELECT a.carmodelname,a.id,b.brand,a.shortdayprice,a.weekdayprice,a.monthdayprice FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE  (a.isdel = 0) AND (a.barandid = {$brand}) AND (a.carmodelname LIKE '%%%s%%')";
                $ary = [$query_info];
                $carinfo = $m->query($sql, $ary);
            }else{
                $carinfo = $this->judgeOrdercar('',$pk_car,$re_car,'派车',$query_info,$brand);

            }
            if ($carinfo){
                $reg['code'] = 1;
                $reg['carinfo'] = $carinfo;
                $reg['msg'] = '以搜索到你想要的车型';
            }else{
                $reg['msg'] = '该类车型没有空闲车辆！';
            }

        }
        echo json_encode($reg);
    }
    //批量删除
    public function delAll(){

        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M('order');
            $where['id'] = array('IN', $ids);
            $data['is_del'] = 1;
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog('order', $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }
    //还车管理
    public function returnCar(){
        $time = date("Y-m-d H:i:s",time());
        $this->assign('term',$time);
        $order_state = "AND (a.order_state = 3) AND (c.usertype = 0)";
        if (isset($_GET['act'])){
            $act = I('get.act');
        }else{
            $act = 'return_car';
        }
        $this->assign('act',$act);
        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            if ($select == 3){
                $key = I('get.key_state');
            }
            $msg['select'] = $select;
            $msg['select_time'] = $select_time;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            if ($startDate=='' || $stopDate==''){
                $this->q_query(3,0,0,$select,$key,'returnCar',$order_state,2);
            }else{
                $this->q_query($select_time,$startDate,$stopDate,$select,$key,'returnCar',$order_state,2);
            }

        }else{
            $this->q_query(3,0,0,100,0,'returnCar',$order_state,2);
        }
    }
    //结单管理
    public function orderEnd(){
        $m = M();
        $time = date("Y-m-d H:i:s",time());
        $time = date("Y-m-d H:i:s", strtotime("-1 months", strtotime("$time")));    //当前时间减少一个月（自然月）；
        $this->assign('term',$time);

        $order_state = "AND (( a.order_state = 4) OR (a.order_state = 5)) AND (c.usertype = 0)";

        if(isset($_GET['select'])){
            $select = I('get.select');
            $select_time = I('get.select_time');
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            if ($select == 3){
                $key = I('get.key_state');
            }
            $msg['select'] = $select;
            $msg['select_time'] = $select_time;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);

            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'orderEnd',$order_state,2);
        }else{
            $this->q_query(3,0,0,100,0,'orderEnd',$order_state,2);
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
    public function q_query($select_time=3,$startDate=0,$stopDate=0,$select=100,$key,$themes='Index',$order_state=0,$order=0){

        $sql = "SELECT a.*,b.carmodelname,c.username,c.phone,c.usertype,d.carno FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0) AND (c.usertype = 0)";
        $countSql = "SELECT count(a.id) FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0) AND (c.usertype = 0)";


//      判断查询的时间段
        switch ($select_time){
            case 0:
                $time = "AND (a.order_date BETWEEN '%s' AND '%s')";                     //根据下单时间区间查询
                break;
            case 1:
                $time = "AND (a.pk_date BETWEEN '%s' AND '%s')";                        //根据取车时间区间查询
                break;
            case 2:
                $time = "AND (a.re_date BETWEEN '%s' AND '%s')";                        //根据还车时间区间查询
                break;
            default:
                $time = '';
        }
        //判断查询的字段
        switch ($select){
            case 0:
                $where = "AND (a.order_code LIKE '%%%s%%')";                             //在订单号中查询
                break;
            case 1:
                $where = "AND (c.username LIKE '%%%s%%')";                               //在客户姓名中进行查询
                break;
            case 2:
                $where = "AND (b.carmodelname LIKE '%%%s%%')";                           //在车辆型号中查询
                break;
            case 3:
                $where = "AND (a.order_state = '%d')";                              //对订单状态进行查询
                break;
            case 4:
                $where = "AND (c.phone LIKE '%%%s%%')";                             //对客户账号或者手机进行查询
                break;
            case 5:
                $where = "AND (d.carno LIKE '%%%s%%')";                             //对车辆号牌进行查询
                break;
            default:
        }
        //判断是否需要添加额外的查询限制条件
        if ($order_state){
            $where .= $order_state;
        }
        if ($select_time == 3){
            if ($key != 0){
                $ary = [$key];
            }else{
                $ary = [];
            }
        }elseif($select ==100){
            $ary = [$startDate,$stopDate];
        }elseif(($select_time == 3) || ($select ==100)){
            $ary=[];
        }else{
            $ary = [$startDate,$stopDate,$key];
        }
        $sql .=$time.$where;                                                                //对SQL语句进行拼接
        $countSql .=$time.$where;
//        "GROUP BY id";
        $sql .= "ORDER BY a.id DESC";
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示
        $this->display($themes);
//        $this->display('Order/'.$themes);
    }

    public function max_query($select_time=3,$startDate=0,$stopDate=0,$select=100,$key,$themes='Index',$order_state=0,$order=0){

        $sql = "SELECT a.*,c.username,c.phone,c.usertype FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON b.carid = d.id WHERE (a.is_del = 0) ";
        $countSql = "SELECT count(a.id) as id FROM `order` a LEFT JOIN order_car b ON a.id=b.orderid LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON b.carid = d.id WHERE (a.is_del = 0) ";

//      判断查询的时间段
        switch ($select_time){
            case 0:
                $time = "AND (a.order_date BETWEEN '%s' AND '%s') ";                     //根据下单时间区间查询
                break;
            case 1:
                $time = "AND (a.pk_date BETWEEN '%s' AND '%s') ";                        //根据取车时间区间查询
                break;
            case 2:
                $time = "AND (a.re_date BETWEEN '%s' AND '%s') ";                        //根据还车时间区间查询
                break;
            default:
                $time = '';
        }
        //判断查询的字段
        switch ($select){
            case 0:
                $where = "AND (a.order_code LIKE '%%%s%%') ";                             //在订单号中查询
                break;
            case 1:
                $where = "AND (c.username LIKE '%%%s%%') ";                               //在客户姓名中进行查询
                break;
            case 2:
                $where = "AND (b.carmodelname LIKE '%%%s%%') ";                           //在车辆型号中查询
                break;
            case 3:
                $where = "AND (a.order_state = '%d') ";                              //对订单状态进行查询
                break;
            case 4:
                $where = "AND (c.phone LIKE '%%%s%%') ";                             //对客户账号或者手机进行查询
                break;
            case 5:
                $where = "AND (d.carno LIKE '%%%s%%') AND (b.is_del = 0 OR b.is_del = 3)  ";                             //对车辆号牌进行查询
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
                $sql .= " group by a.id ORDER BY a.id DESC";
                break;
            case 1:
                $sql .= " group by a.id ORDER BY a.pk_date ASC";
                break;
            case 2:
                $sql .= " group by a.id ORDER BY a.re_date ASC";
                break;
        }
        $countSql = "SELECT COUNT(a.id) FROM ( ".$countSql." group by a.id) as a";
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示
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
        $sql = "SELECT * FROM `order_meeting` WHERE (is_del=0) AND (order_state = 0)";
        $countSql = "SELECT count(id) FROM `order_meeting` WHERE (is_del=0) AND (order_state = 0)";
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
        $this->display('Order/'.$themes);
    }
//        时间差计算，并反回布尔值
    public function time_js($star,$stop){
        $star = strtotime($star);
        $stop = strtotime($stop);
        if (($star-$stop)<0){
            return true;
        }else{
            return false;
        }
    }
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

    //查询代驾信息
    public function sqlDriver($id){
        $tab = M();
        $sql = "SELECT be_driver,price_rec,pre_price,driver_price,order_state,drive_state FROM `order`  WHERE id = %d";
        $arr = [$id];
        $list = $tab->query($sql,$arr);
        return $list;
    }

    //取消代驾
    public function unDriver(){

        $driverId = I('post.id');
        $list = $this->sqlDriver($driverId);
        if($list[0]['drive_state'] == 1){      //判断此单是否需要代驾,无需代驾的订单不能取消代驾
            $tab = M();
            $tab->startTrans();
            $sql = "UPDATE `order` SET drive_number = %d,drive_state = %d,driver_price=%d WHERE id = '%s'";
            $arr = [0,0,0,$driverId];
            $gg= $tab->execute($sql,$arr);

//            查询该订单下的代驾
            $order_drive = "SELECT id,driveid FROM order_drive WHERE orderid = %d AND is_del = %d ";
            $orderdrive = $tab->query($order_drive,[$driverId,0]);

            $num = 0;
            for ($i=0;$i<count($orderdrive);$i++){
                $sql_driverinfo = "UPDATE car_driverinfo SET state = %d WHERE id = %d";
                $ary = [0,$orderdrive[$i]['driveid']];
                $re = $tab->execute($sql_driverinfo,$ary);

                //删除代驾表数据
                $deldrive = "DELETE FROM order_drive WHERE id = %d";
                $del = $tab->execute($deldrive,[$orderdrive[$i]['id']]);

                if ($re !== false && $del !== false){
                    $num += 1;
                }
            }

            if ($gg > 0 && count($orderdrive) == $num){
                $tab->commit();
                $auserInfo = UserInfo();
                $log = self::writeLog('order', $driverId, '取消代驾', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log){
                    $reg['code'] = 1;
                    $reg['msg'] = '取消代驾操作成功';
                }
            } else {
                $tab->rollback();
                $reg['code'] = 0;
                $reg['msg'] = '取消代驾操作失败';
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '此订单无需代驾,不用取消代驾';
        }

        $this->ajaxReturn($reg);
    }

    //大客户处理违章数据
    public function Illegal_data(){
        $tab = M();
        $id = I('get.id');
        $sql = "SELECT a.id,a.orderid,a.begintime,a.illegalcost,a.remarks,a.endtime,b.carno,c.drivername FROM `order_car` a LEFT JOIN car_carinfo b ON a.carid=b.id LEFT JOIN car_driverinfo c ON a.driveid=c.id WHERE a.orderid=%d";
        $list = $tab->query($sql,[$id]);
        for ($i=0;$i<count($list);$i++){
            if (empty($list[$i]['drivername'])){
                $list[$i]['drivername'] = '无代驾';
            }
        }

        $this->assign('list',$list);
        $this->display('max_iilegal');
    }

    //大客户违章信息录入
    public function max_order_ope(){
        $tab = M();
        $tab->startTrans();
        $ids = I('post.dataid');
        $illegalcost = I('post.illegalcost');
        $remarks = I('post.remarks');
        $orderid = I('post.orderid');

        $num = 0;
        for ($i=0;$i<count($ids);$i++){
            $sql = "UPDATE order_car SET illegalcost=%d,remarks='%s' WHERE id=%d";
            $re = $tab->execute($sql,[$illegalcost[$i],$remarks[$i],$ids[$i]]);
            if ($re !== false){
                $num += 1;
            }
        }
        $sqlmoney = "SELECT SUM(illegalcost) FROM order_car WHERE orderid=%d AND is_del > %d";
        $money = $tab->query($sqlmoney,[$orderid,0]);

        //统计订单表违章费用
        $order = "UPDATE `order` SET dp_price = %d WHERE id=%d";
        $orders = $tab->execute($order,[$money[0]['sum(illegalcost)'],$orderid]);
        if (count($ids) == $num && $orders >0 ){
            $tab->commit();
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $orderid, 'max_order_opes', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            exit('<script type="text/javascript">alert("违章录入成功!");location.href="'.U('Order/customerBig').'";</script>');
        }else{
            $tab->rollback();
            exit('<script type="text/javascript">alert("违章录入失败!");</script>');
        }

    }

    //大客户取消订单
    public function cancelOrder(){
        $tab = M();
        $tab->startTrans();
        $id = I('get.id');
        //查询该订单下的车辆
        $ordercar = "SELECT id,carid FROM order_car WHERE orderid = %d AND is_del =%d";
        $ordercarinfo = $tab->query($ordercar,[$id,0]);

        //将该订单下所有车辆释放为空闲状态
        $num = 0;
        for ($i=0;$i<count($ordercarinfo);$i++){
            //标记订单车辆数据为取消订单
            $order_car = "UPDATE order_car SET is_del=%d WHERE id=%d";
            $order_carinfo = $tab->execute($order_car,[5,$ordercarinfo[$i]['id']]);

            $car = "UPDATE car_carinfo SET usestatus=%d WHERE id=%d";
            $carinfo = $tab->execute($car,[0,$ordercarinfo[$i]['carid']]);
            if ($carinfo !== false && $order_carinfo !== false){
                $num += 1;
            }
        }

        // //查询该订单下的代驾
        $orderdrive = "SELECT id,driveid FROM order_drive WHERE orderid = %d AND is_del =%d";
        $orderdriveinfo = $tab->query($orderdrive,[$id,0]);

        //将该订单下所有代驾释放为空闲状态
        $num1 = 0;
        for ($j=0;$j<count($orderdriveinfo);$j++){
            //标记代驾数据为取消订单
            $order_drive = "UPDATE order_drive SET is_del=%d WHERE id=%d";
            $order_driveinfo = $tab->execute($order_drive,[5,$orderdriveinfo[$j]['id']]);


            $drive = "UPDATE car_driverinfo SET state=%d WHERE id=%d";
            $driveinfo = $tab->execute($drive,[0,$orderdriveinfo[$j]['driveid']]);
            if ($driveinfo !== false && $order_driveinfo !== false){
                $num1 += 1;
            }
        }


        $order = "UPDATE `order` SET order_state=%d WHERE id=%d";
        $orderinfo = $tab->execute($order,[10,$id]);


        if (count($ordercarinfo)  == $num && count($orderdriveinfo) == $num1 && $orderinfo > 0){
            $tab->commit();
            $auserInfo = UserInfo();
            $log = self::writeLog('order', $id, 'cancelOrder', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            exit('<script type="text/javascript">alert("订单取消成功!");location.href="'.U('Order/customerBig').'";</script>');
        } else {
            $tab->rollback();
        }

    }




}