<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Common\Common\app;
use Think\Controller;
use Think\Verify;

//echo "<meta charset='utf-8' />";
/**
 * Class CarController
 * @package Admin\Controller
 *网站后台订单管理操作控制器
 */
class OrderOldController extends BaseController {

    //老大客户订单
    public function index(){
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
            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'index');
        }else{
            if (isset($_GET['info'])){
                $order_state = " AND (a.order_state < 2)";
                $this->q_query(3,0,0,100,0,'index',$order_state);
            }else{
                $startDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']-2592000);
                $stopDate = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
                $order_state = " AND (a.order_state != 10)";
                $this->q_query(0,$startDate,$stopDate,100,0,'index',$order_state);
            }
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
        $this->display('count');
//        }
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
                $this->success("删除成功!", U('Order/index'));
            }
        } else {
            echo "<script>alert ('删除失败!');</script>";
            $delete->getError();
        }
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
    public function q_query($select_time=3,$startDate=0,$stopDate=0,$select=100,$key,$themes='index',$order_state=0,$order=0){

        $sql = "SELECT a.*,b.carmodelname,c.username,c.phone,c.usertype,d.carno FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0) ";
        $countSql = "SELECT count(a.id) FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0) ";


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
//        "GROUP BY id";
        $sql .= "ORDER BY a.id DESC";
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示
        $this->display($themes);
//        $this->display('Order/'.$themes);
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
                $tradebig['cost_price'] += $value['cost_price'] * $day;
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
                    $order_info[$key]['out_cost'] = $day * $value['cost_price'];    //车辆成本乘以租车天数
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

        $finance = new FinanceController();
        $finance->pageInfo($order_info, $count,10,'list','page',true);
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



}