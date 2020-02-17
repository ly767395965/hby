<?php
namespace App\Controller;

use Common\Common\App;
use Common\Common\BaseHomeController;
use Common\Common\sendAPI;
use Admin\Controller\CarinfoManageController;
date_default_timezone_set('PRC');//设置时区


class CarAgentController extends BaseHomeController
{

    public function index()
    {

    }
    //代理商登录接口
    public function Login(){
        $app = App::getInstance();
        if (isset($_GET['loginUser']) && isset($_GET['loginPwd'])){
            $name = I('get.loginUser');
            $pwd = md5(I('get.loginPwd'));
        }else{
            $reg = ['code'=>0, 'msg'=>'参数出错'];
            exit(json_encode($reg));
        }

        $sql  = "SELECT a.id,a.`name`,a.username,a.type,a.sex, a.phonenumber, a.identity, a.addtime, b.id AS agent_id, b.name AS agent_name,b.agent_state,b.is_del FROM admin_user a LEFT JOIN car_agent b ON a.agent_id = b.id WHERE a.username='%s' AND a.`password`='%s'";
        $arr = [$name,$pwd];
        if ($name != '' and $pwd != '') {
            $list = M()->query($sql,$arr);
            if ($list) {
                if ($list[0]['agent_id'] != 0 && $list[0]['agent_state'] == 1){
                    $reg['code'] = 2;
                    $reg['msg'] = '该代理商已被冻结';
                }else{
                    $list = ['login' => $list[0], 'code' => 1];
                }

            } else {
                $list = ['msg' => '登陆失败', 'code' => 0];
            }
        }
        $json = json_encode($list);
        echo $json;
    }

    //车辆信息
    public function carinfo(){
        $user['id'] = I('get.user_id');
        $pagenum = I('get.pagenum');
        $app = App::getInstance();

        $auserInfo = M('admin_user')->where($user )->find();
        if (!$auserInfo){
            $reg['code'] = 0;
            $reg['msg'] = '请求非法!';
            exit(json_encode($reg));
        }
        if ($auserInfo['agent_id'] != 0){
            $condition = " AND a.agent_id = {$auserInfo['agent_id']}";
        }else{
            $condition = '';
        }
        //接受模糊查询的条件

        //将条件传给数组
        $selectid = I('get.selects');
        if ($selectid){
            $key = I('get.key');
        }

        $sql = "SELECT a.id as id,a.carno,a.color,a.isdiscount,a.goodprice,a.costprice,a.motorno,a.usedmileage,a.buydate,a.checkdate,a.usestatus,";
        $sql = $sql . "a.maintainmileage,a.carproperty,a.auditing_state,b.brand,c.carmodelname,a.addtime ";
        $sql = $sql . "FROM car_carinfo a  LEFT JOIN  car_barand b  ON a.brand = b.id  LEFT JOIN car_carmodel c ON a.carmodel = c.id  WHERE (a.isdel=0) AND (b.isdel=0) AND (c.isdel=0){$condition} ";
        $countSql = "SELECT COUNT(a.id) FROM car_carinfo a LEFT JOIN  car_barand b  ON a.brand = b.id  LEFT JOIN car_carmodel c ON a.carmodel = c.id WHERE (a.isdel=0) {$condition} AND (a.auditing_state = 1) AND ";
        switch ($selectid){
            case 1:
                $sql = $sql . "AND (b.brand LIKE '%%%s%%') ";
                $countSql = $countSql . "(b.brand LIKE '%%%s%%')";
                break;
            case 2:
                $sql = $sql . "AND (c.carmodelname LIKE '%%%s%%') ";
                $countSql = $countSql . "(c.carmodelname LIKE '%%%s%%')";
                break;
            case 3:
//                $auditing_state = I('get.auditing_state');
                $sql = $sql . "AND (a.auditing_state = '%d') ";
                $countSql = $countSql . " (a.auditing_state = '%d')";
                break;
            case 4:
                $sql = $sql . "AND (a.carno LIKE '%%%s%%') ";
                $countSql = $countSql . "(a.carno LIKE '%%%s%%')";
                break;
            default:
                $sql = $sql."ORDER BY a.id DESC";
                $countSql = "SELECT COUNT(a.id) FROM car_carinfo a LEFT JOIN  car_barand b  ON a.brand = b.id  LEFT JOIN car_carmodel c ON a.carmodel = c.id WHERE (a.isdel=0) {$condition} ";
        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid) {
            $sql = $sql . "ORDER BY a.id DESC";
            $ary = [$key];
        } else {
            $ary = [];
        }
        $list = $app->pageDisplay($sql, $countSql, $pagenum, $ary, 'count(a.id)', true);
        if ($list){
            $reg['code'] = 1;
            $reg['data'] = $list;
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '未查询到任何数据';
        }
        echo json_encode($reg);
    }

    //代理商申请添加车辆
    public function addCar(){
//        $auserInfo = cookie('auserInfo');
        if (!isset($_REQUEST['user_id'])){
            $reg['code'] = 0;
            $reg['msg'] = '参数出错';
            exit(json_encode($reg));
        }
        $user_id = I('request.user_id');
        $agent_id = $this->agent_id($user_id);
        $car = M('car_carinfo');
        //判断输出模板
        if (empty($_POST)){
            //查询品牌
            $sqlbarand = M('car_barand');
            $brand = $sqlbarand->where(array('isdel=0'))->select();
            if ($brand){
                $reg['code'] = 1;
                $reg['brand'] = $brand;
            }else{
                $reg['code'] = 0;
                $reg['msg'] = '车辆品牌获取失败';
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '未知错误';
        }

        //检查页面传值
        if(!empty($_POST)) {
//            验证表单提交的信息
            $rules = array(
                array('brand', 'require', '<script>alert("品牌不能为空");history.back(-1);</script>', 0),
                array('carmodel', 'require', '<script>alert("车辆类型不能为空！");history.back(-1);</script>', 0),
                array('carno', 'require', '<script>alert("车牌号不能为空！");history.back(-1);</script>', 0),
                array('carproperty', 'require', '<script>alert("车辆性质不能为空！");history.back(-1);</script>', 0),
                array('usestatus', 'require', '<script>alert("使用状态不能为空！");history.back(-1);</script>', 0),
                array('isdiscount', 'require', '<script>alert("是否优惠不能为空！");history.back(-1);</script>', 0),
//                array('costprice','require','<script>alert("成本价格不能为空");history.back(-1);</script>',0),
            );

            //判断并接受页面传递的值
            if (!$car->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $reg['code'] = 0;
                $reg['msg'] = $car->getError();
            } else {
                if (!empty(I('post.costprice'))) {
                    $costprice = I('post.costprice');
                } else {
                    $costprice = 0;
                }
                $data = array();
                $data['brand'] = I('post.brand');
                $data['carmodel'] = I('post.carmodel');
                $data['carno'] = I('post.carno');
                $data['color'] = I('post.color');
                $data['motorno'] = I('post.motorno');
                $data['usedmileage'] = I('post.usedmileage');
                $data['maintainmileage'] = I('post.maintainmileage');
                $data['buydate'] = I('post.buydate');
                $data['checkdate'] = I('post.checkdate');
                $data['carproperty'] = I('post.carproperty');
                $data['usestatus'] = I('post.usestatus');
                $data['isdiscount'] = I('post.isdiscount');
                $data['goodprice'] = I('post.goodprice');
                $data['costprice'] = $costprice;
                $data['addtime'] = new Date('Y-m-d H:i:s', time());
                if ($agent_id != 0) {
                    $data['auditing_state'] = 0;
                    $data['agent_id'] = $agent_id;
                }
                $new_id = $car->add($data);
                //记录操作日志
                if ($new_id) {
                    //获取添加成功返回的数据id
//                    $returnid = M(self::$table)->field('id')->order('id desc')->find();
                    $username = M('admin_user')->where(array('id'=>$user_id))->field('name')->find();
                    $log = self::writeLog(self::$table, $new_id, '添加车辆', Date('Y-m-d H:i:sA'), $username['name']);
                    if ($log) {
                        $reg['code'] = 1;
                        $reg['msg'] = '车辆添加成功，等待审核';
                    }
                } else {
                    $reg['code'] = 0;
                    $reg['msg'] = '车辆添加失败';
                }
            }
        }
        echo json_encode($reg);
    }


// //车辆修改方法加载车型ajax方法
    public function ajax1 () {
        $where['isdel'] = 0;
        $where['barandid'] = I('get.mid');//获取品牌id

        if ($where['barandid']){
            //查询车型
            $sqlcarmodel = M('car_carmodel');
            //根据品牌查询相对应的车型
            $model = $sqlcarmodel->where($where)->field('id,barandid,carmodelname')->select();
            if ($model){
                $reg['code'] = 1;
                $reg['data'] = $model;
            }else{
                $reg['code'] = 2;
                $reg['msg'] = '未查询到该品牌下的车型';
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '未接收到参数';
        }
        $this->ajaxReturn($reg);

    }

    //代理商管理账号信息
    public function auserinfo(){
        $user['id'] = I('get.user_id');
        if ($user['id']){
            $pagenum = I('get.pagenum');
            if (!$pagenum){
                $pagenum = 1;
            }
            $app = App::getInstance();

            $auserinfo = M('admin_user')->where($user)->find();
            if (!$auserinfo){
                $reg['code'] = 0;
                $reg['msg'] = '请求非法!';
                exit(json_encode($reg));
            }

            $condition = "agent_id = {$auserinfo['agent_id']}";
            if(isset($_GET['demand'])){
                $name = I('post.demand');
                $sql="SELECT * FROM admin_user WHERE NAME Like '%%%s%%' ";
                $countSql = "SELECT count(id) FROM admin_user WHERE NAME Like '%%%s%%' ";
                $ary = array($name);
            }else{
                $sql = "SELECT * FROM admin_user ";
                $countSql = "SELECT count(id) FROM admin_user";
                $ary = array();
            }

            if ($auserinfo['agent_id'] != 0){
                if(isset($_GET['demand'])){
                    $sql .= " AND {$condition}";
                    $countSql .= " AND {$condition}";
                }else{
                    $sql .= " WHERE {$condition}";
                    $countSql .= " WHERE {$condition}";
                }
            }
            $list = $app->pageDisplay($sql, $countSql, $pagenum, $ary, 'count(id)', true);
            if ($list){
                $reg['code'] = 1;
                $reg['data'] = $list;
            }else{
                $reg['code'] = 2;
                $reg['msg'] = '未查询到数据';
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '参数错误';
        }
        echo json_encode($reg);
    }

    //代理商订单信息
    public function orderinfo() {
        $user['id'] = I('get.user_id');
        if (isset($_GET['pagenum'])){
            $pagenum = I('get.pagenum');
        }else{
            $pagenum = 5;
        }
        if ($user['id']){
            $auserinfo = M('admin_user')->where($user)->find();
            $agent_id = $auserinfo['agent_id'];
            if ($agent_id != 0){
                $orderstate = " AND (a.agent_id = {$agent_id})";
            }
            if(isset($_GET['select']) && $_GET['select'] != 100){
                $select = I('get.select');
                $select_time = I('get.select_time');
                $msg['select_time'] = $select_time;
                switch ($select_time){
                    case 0:
                        $select_time = 2;
                        break;
                    case 2:
                        $select_time = 0;
                        break;
                }
                $startDate = I('get.start');
                $stopDate = I('get.stop');
                $key = I('get.key');
//                if ($select == 3){
//                    $key = I('get.key_state');
//                }
                $list = $this->q_query(3,$startDate,$stopDate,$select,$key,$orderstate,0,$pagenum);
            }else{
                $list = $this->q_query(3,0,0,100,0,$orderstate,0,$pagenum);
            }
            if ($list){
                $reg = ['code'=>1, 'data'=>$list];
            }else{
                $reg = ['code'=>0, 'msg'=>'未查询到数据'];
            }
        }else{
            $reg = ['code'=>0, 'msg'=>'参数错误'];
        }
        echo json_encode($reg);
    }

    //订单详情页管理器
    public function orderDetails(){
        $m = M();
        if (isset($_GET['id'])){
            $id = $_GET['id'];
            $sql = "SELECT a.*,b.carmodelname,b.shortdayprice,b.weekdayprice,b.monthdayprice,b.barandid,c.username,c.phone,d.carno,d.carproperty,d.costprice,e.id as driverid,e.drivername,e.phone as driverphone FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id LEFT JOIN car_driverinfo e ON a.be_driver=e.id WHERE (a.is_del=0) AND (a.id=%d)";
            $ary = array($id);
            $orderinfo = $m->query($sql,$ary);
            if (!$orderinfo){
                $reg['msg'] = '未查询到该订单信息';
                $reg['code'] = 0;
            }else{
                $orderinfo = $orderinfo[0];
                if ($orderinfo['be_driver']){                                    //判断是否有代驾人
                    $where['id'] = $orderinfo['be_driver'];
                    $driver = $m->table('car_driverinfo')->where($where)->find();
                    $orderinfo['be_driver'] = $driver['drivername'];             //根据代驾人id 找出姓名，并替换id
                }

                $where_cost['order_id'] = $id;                                  //根据订单id，找出该订单的交易记录
                $where_cost['order_class'] = array('neq',1);
                $where_cost['pay_way'] = array('gt','0');
                $cost = $m->table('order_cost')->where($where_cost)->select();
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
                if ($cost){
                    $orderinfo['cost_info'] = $cost;
                }else{
                    $orderinfo['cost_info'] = [];
                }

                $reg['data'] = $orderinfo;
                $reg['code'] = 1;
            }

        }else{
            $reg['msg'] = '参数出错';
            $reg['code'] = 0;
        }
        $this->ajaxReturn($reg);
    }

    //代理商申请结账信息
    public function check_info() {
        $app = App::getInstance();
        $sql ="SELECT a.id,a.pack_id,a.price_rec,a.collections_rec,a.state,a.addtime,a.check_date,b.`name` AS user_name,c.`name` as agent_name FROM order_agent a LEFT JOIN admin_user b ON a.user_id=b.id LEFT JOIN car_agent c ON a.agent_id=c.id WHERE (a.is_del = 0)";
        $countSql = "SELECT count(a.id) FROM order_agent a LEFT JOIN admin_user b ON a.user_id=b.id LEFT JOIN car_agent c ON a.agent_id=c.id WHERE (a.is_del = 0)";

        if (isset($_GET['pagenum'])){$pagenum  = I('get.pagenum');}else{$pagenum = 5;}
        if (isset($_GET['user_id'])){
            $agent_id = $this->agent_id(I('get.user_id'));
        }else{
            $reg = ['code'=>0, 'msg'=>'参数错误'];
            echo json_encode($reg);
            exit;
        }

        if ($agent_id != 0){
            $sql .= " AND (a.agent_id = {$agent_id})";
            $countSql .= " AND (a.agent_id = {$agent_id})";
        }
        $ary = [];
        if (isset($_GET['select']) && $_GET['select'] != 100){
            $select = I('get.select');
            $key = I('get.key');
            switch ($select){
                case 0:
                    $sql .= " AND (b.name LIKE '%%%s%%')";
                    break;
                case 1:
                    $sql .= " AND (c.name LIKE '%%%s%%')";
                    break;
                case 3:
                    $key = I('get.key_state');
                    $sql .= " AND (a.state LIKE '%%%s%%')";
                    break;
            }
            $select_time = I('get.select_time');
            $start = I('get.start');
            $stop = I('get.stop');
            switch ($select_time){
                case 0:
                    $sql .= "AND (a.addtime BETWEEN '%s' AND '%s')";                     //根据申请时间区间查询
                    break;
                case 1:
                    $sql .= "AND (a.check_date BETWEEN '%s' AND '%s')";                  //根据结算时间区间查询
                    break;
            }
            $ary = [$key,$start,$stop];

            $msg['select'] = $select;
            $msg['select_time'] = $select_time;
            $msg['start'] = $start;
            $msg['stop'] = $stop;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
        }

        $sql .= " ORDER BY id DESC";
        $list = $app->pageDisplay($sql, $countSql, $pagenum, $ary, 'count(a.id)', true);
        if ($list){
            $reg['code'] = 1;
            $reg['data'] = $list;
        }else{
            $reg['code'] = 2;
            $reg['msg'] = '未查询到相关数据';
        }
        echo json_encode($reg);
    }

    //已打包订单详情
    public function packInfo() {

        if (isset($_GET['id'])){
            $id = I('get.id');          //订单包id
            $sql_agent = "SELECT a.id,a.pack_id,a.price_rec,a.collections_rec,a.state,a.addtime,a.check_date,a.order_num,b.`name` AS user_name,c.`name` as agent_name FROM order_agent a LEFT JOIN admin_user b ON a.user_id=b.id LEFT JOIN car_agent c ON a.agent_id=c.id WHERE (a.is_del = 0) AND (a.id = {$id})";
            $pack = M('order_agent')->query($sql_agent);
            $pack_id = explode(",", $pack[0]['pack_id']);
            $sql_pack = "";
            foreach ($pack_id as $key => $value){
                if ($key == 0){
                    $sql_pack .= "(a.id = {$value})";
                }else {
                    $sql_pack .= " OR (a.id = {$value})";
                }
            }
//            $sql = "SELECT a.id, a.order_code, a.pk_date, a.re_date, a.cost_price, a.order_date, a.check_out, a.order_state, b.name, c.carmodelname, d.carno FROM `order` a LEFT JOIN car_agent b ON a.agent_id = b.id LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_carinfo d ON a.car_id = d.id WHERE a.is_del=0 AND ".$sql_pack.")";

            $sql = " FROM ( 
	SELECT a.id,a.order_code,a.car_number,g.carid as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,g.begintime,g.endtime)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a LEFT JOIN work_member c ON a.uid=c.id 
	LEFT JOIN order_car g ON a.id = g.orderid 
	LEFT JOIN car_carinfo d ON g.carid=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id 
	LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 1 AND a.is_del = 0 
UNION 
	SELECT a.id,a.order_code,a.car_number,d.id as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,a.pk_date,a.re_date)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a 
	LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 0 AND a.is_del = 0 
) a WHERE {$sql_pack} ";
            $carno_sql = "SELECT id,carno ".$sql;
            $sql = "SELECT id,car_number,car_id,pk_date,re_date,check_out,order_state,agent_check_state,agent_id,agent_name,SUM(a.car_cost) as all_cost,usertype,order_code ".$sql;

            $ary = [];
            if (isset($_GET['select']) && $_GET['select'] != 100){
                $select = I('get.select');
                $key = I('get.key');
                switch ($select){
                    case 0 :
                        $where = " AND (a.order_code LIKE '%%{$key}%%')";
                        break;
                    case 2 :
                        $where = " AND (c.carmodelname LIKE '%%{$key}%%')";
                        break;
                    case 3 :
                        $key = I('get.key_state');
                        $where = " AND (a.order_state LIKE '%%{$key}%%')";
                        break;
                    case 5 :
                        $where = " AND (d.carno LIKE '%%{$key}%%')";
                        break;
                }
                $select_time = I('get.select_time');
                $star_time = I('get.start');
                $stop_time = I('get.stop');

                switch ($select_time){
                    case 0:
                        $time = "AND (a.re_date BETWEEN '%s' AND '%s')";                     //根据还车时间区间查询
                        break;
                    case 1:
                        $time = "AND (a.pk_date BETWEEN '%s' AND '%s')";                        //根据取车时间区间查询
                        break;
                    case 2:
                        $time = "AND (a.order_date BETWEEN '%s' AND '%s')";                        //根据下单时间区间查询
                        break;
                }
                $ary = [$star_time,$stop_time];

                $msg['select'] = $select;
                $msg['select_time'] = $select_time;
                $msg['start'] = $star_time;
                $msg['stop'] = $stop_time;
                $msg['key'] = $key;
                $this->assign('msg',$msg);
            }

            $sql .= $where.$time."  GROUP BY a.id";
            $carno_sql .= $where.$time;
            $orderinfo = M('order')->query($sql,$ary);
            $packInfo['pack'] = $pack[0];
            $packInfo['order'] = $orderinfo;
            $reg['code'] = 1;
            $reg['data'] = $packInfo;
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '未获取到订单包id';
        }
        echo json_encode($reg);
    }

    //代理商可申请打包订单
    public function checkout() {
//        $agent_id = cookie('auserInfo')['agent_id'];
        if (isset($_GET['pagenum'])){$pagenum = I('get.pagenum');}else{$pagenum = 5;};
        if (isset($_GET['user_id'])){
            $agent_id = $this->agent_id(I('get.user_id'));
        }else{
            $reg = ['code'=>0,'msg'=>'参数错误'];
            exit(json_encode($reg));
        }

        if ($agent_id != 0){
            $orderstate = " AND (a.agent_id = {$agent_id}) AND (a.order_state > 3) AND (a.agent_check_state = 0 ) AND (a.check_out = 1 )";
        }else{
            $orderstate = "AND (a.agent_id != 0) AND (a.order_state > 3) AND (a.agent_check_state = 0 ) AND (a.check_out = 1 )";
        }
        if(isset($_GET['select']) && $_GET['select'] != 100){
            $select = I('get.select');
            $select_time = I('get.select_time');
            if (!$select_time){
                $select_time = 3;
            }
            $msg['select_time'] = $select_time;
            switch ($select_time){
                case 0:
                    $select_time = 2;
                    break;
                case 2:
                    $select_time = 0;
                    break;
            }
            $startDate = I('get.start');
            $stopDate = I('get.stop');
            $key = I('get.key');
            $list = $this->q_query($select_time,$startDate,$stopDate,$select,$key,$orderstate,0,$pagenum);
        }else{
            $list = $this->q_query(3,0,0,100,0,$orderstate,0,$pagenum);
        }
        if ($list){
            $reg = ['code'=>1, 'data'=>$list];
        }else{
            $reg = ['code'=>2, 'msg'=>'未查询到任何数据'];
        }
        echo json_encode($reg);
    }

    //取消申请
    public function cancel(){
        $model = M();
        if (isset($_GET['id'])){
            $where['id'] = I('get.id');     //订单包id
            $order_agent = $model->table('order_agent')->field('pack_id')->where($where)->find();
            $order_data['agent_check_state'] = 0;
            $pack_id = explode(",", $order_agent['pack_id']);
            foreach ($pack_id as $key => $value){
                $where_order['id'] = $value;
                $model->table('order')->where($where_order)->save($order_data);
            }

            $data['state'] = 3;
            $msg = M('order_agent')->where($where)->save($data);

            if ($msg!==false){
                //接收当前管理员登陆名
                $sql = "SELECT b.name FROM order_agent a LEFT JOIN admin_user b ON a.user_id=b.id WHERE a.id={$where['id']}";
                $auser = M()->query($sql);
                $auserInfo = $auser[0]['name'];
                $log = self::writeLog('order_agent', $where['id'], '取消申请', Date('Y-m-d H:i:sA'), $auserInfo);
                if ($log) {
                    $reg['code'] = 1;
                }
            }else{
                $reg['code'] = 2;
                $reg['msg'] = '取消申请失败';
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '未获取到id';
        }
        echo json_encode($reg);
    }

    //对代理商申请结账的订单打包
    public function pack() {
        $day = date("d", time());       //判断是否为每月的1号或者15号
        if ($day !=1 && $day !=29){    //如果不是，则不能申请结账
            $reg['code'] = 4;
            $reg['msg'] = '注：申请时间为每月1号或15号！';
            exit(json_encode($reg));
        }
        if (isset($_POST['arr']) && !empty($_POST['arr'])){
            if (isset($_POST['user_id'])){
//                $auserInfo['agent_id'] = I('post.agent_id');
                $where['user_id'] = I('post.user_id');
//                $auserInfo['username'] = I('post.username');
                $auserInfo = M('admin_user')->field('id,username,agent_id')->where($where)->find();
            }else{
                $reg = ['code'=>0, 'msg'=>'参数错误'];
                exit(json_encode($reg));
            }

            $arr = I('post.arr');
            $str = '';
            $order_sql = '';
            $price_rec = 0;
            for ($i=0;$i<count($arr);$i++){
                if ($str == ''){
                    $str = $arr[$i];
                }else{
                    $str .= ','.$arr[$i];
                }
                $where['id'] = $arr[$i];
                $order['agent_check_state'] = 3;
                M('order')->where($where)->save($order);        //改变订单表中的申请状态(3为申请中)

                $sql = "SELECT SUM(a.car_cost) as all_cost FROM ( 
	SELECT a.id,a.order_code,a.car_number,g.carid as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,g.begintime,g.endtime)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a LEFT JOIN work_member c ON a.uid=c.id 
	LEFT JOIN order_car g ON a.id = g.orderid 
	LEFT JOIN car_carinfo d ON g.carid=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id 
	LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 1 AND a.is_del = 0 
UNION 
	SELECT a.id,a.order_code,a.car_number,d.id as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,a.pk_date,a.re_date)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a 
	LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 0 AND a.is_del = 0 
) a WHERE id = {$arr[$i]}";
                $all_cost = M()->query($sql);
                if ($all_cost[0]['all_cost']){
                    $price_rec += $all_cost[0]['all_cost'];
                }
            }
            $data['agent_id'] = $auserInfo['agent_id'];
            $data['user_id'] = $auserInfo['id'];
            $data['pack_id'] = $str;                    //打包订单
            $data['price_rec'] = $price_rec;
            $data['order_num'] = count($arr);           //订单数
            $data['addtime'] = date("Y-m-d H:i:s", time());//添加时间
            $data['updatetime'] = $data['addtime'];        //更新时间
            $msg = M('order_agent')->add($data);
            if ($msg){
                //接收当前管理员登陆名
                self::writeLog('order_agent', $msg, '申请结账', Date('Y-m-d H:i:sA'), $auserInfo['username']);
                $reg['code'] = 1;
                $reg['msg'] = '申请已发送,等待处理';
            }else{
                $reg['code'] = 2;
                $reg['msg'] = '添加失败';
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '未获取到需打包的订单';
            $reg['post'] = I('post.arr');
            $reg['get'] = I('get.arr');
        }
        echo json_encode($reg);
    }

    //查看订单所用车辆
    public function orderCar(){
        $sql= "SELECT id,carno FROM ( 
	SELECT a.id,a.order_code,a.car_number,g.carid as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,g.begintime,g.endtime)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a LEFT JOIN work_member c ON a.uid=c.id 
	LEFT JOIN order_car g ON a.id = g.orderid 
	LEFT JOIN car_carinfo d ON g.carid=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id 
	LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 1 AND a.is_del = 0 
UNION 
	SELECT a.id,a.order_code,a.car_number,d.id as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,a.pk_date,a.re_date)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a 
	LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 0 AND a.is_del = 0 
) a 
WHERE ";

        if (isset($_GET['order_id'])){
            $id = I('get.order_id');
            $sql .= " a.id = {$id}";
            $list = M()->query($sql);
            if ($list){
                $reg['code'] = 1;
                $reg['data'] = $list;
            }else{
                $reg['code'] = 2;
                $reg['msg'] = '未查询到该订单下的车辆信息';
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '参数出错';
        }
        echo json_encode($reg);
    }

    //修改密码
    public function editPassword(){
        $rules = array(
            array('Password','require','新密码不能为空！',0), //默认情况下用正则进行验证
            array('Password ','6,16','密码必须在6~16位之间！','length'), // 验证标题长度

        );
        $m = M();
        if (isset($_POST['id']) && isset($_POST['old_password']) && isset($_POST['password'])){
            if (!$m->table('admin_user')->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $reg['code'] = 0;
                $reg['msg'] = $m->getError();
                exit(json_encode($reg));
            }else {
                $where['id'] = I('post.id');
                $pwd_old = trim(I('post.old_password'));
                $pwd_old = md5($pwd_old);
                $pwd_one = I('post.password');
                $pwd_two = I('post.password_two');

                $user_old = $m->table('admin_user')->field('password,name')->where($where)->find();
                $user_old['password'] = trim($user_old['password']);
                if ($pwd_old != $user_old['password']){
                    $reg['code'] = 2;
                    $reg['msg'] = '原始密码错误';
                    exit(json_encode($reg));
                }
                if ($pwd_one != $pwd_two){
                    $reg['code'] = 0;
                    $reg['msg'] = '两次输入的密码不一致';
                    exit(json_encode($reg));
                }

                $data['password'] = md5($pwd_one);
                if ($pwd_old == $data['password']){
                    $reg['code'] = 0;
                    $reg['msg'] = '新密码和原始密码重复';
                    exit(json_encode($reg));
                }
                $user_pwd = $m->table('admin_user')->where($where)->save($data);
                if ($user_pwd){
                    self::writeLog('admin_user', $user_pwd, '修改员工密码', Date('Y-m-d H:i:sA'), $user_old['name']);
                    $reg['code'] = 1;
                    $reg['msg'] = '修改成功';
                }else{
                    $reg['code'] = 0;
                    $reg['msg'] = '修改失败';
                }
            }
        }else{
            $reg['code'] = 0;
            $reg['msg'] = '参数错误';
        }

        echo json_encode($reg);
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
     * @param int $pagenum 每页显示条数
     * @param string $order_state 限制订单状态
     * @param int $order 排序方式（默认0：id倒序;1：取车时间正序;2、还车时间正序）
     * @return Action
     */
    public function q_query($select_time=3,$startDate=0,$stopDate=0,$select=100,$key,$order_state=0,$order=0,$pagenum=5){

        $sql = "SELECT a.*,b.carmodelname,c.username,c.phone,c.usertype,d.carno FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0)";
        $countSql = "SELECT count(a.id) FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0)";


        $sql= " FROM ( 
	SELECT a.id,a.order_code,a.car_number,g.carid as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,g.begintime,g.endtime)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a LEFT JOIN work_member c ON a.uid=c.id 
	LEFT JOIN order_car g ON a.id = g.orderid 
	LEFT JOIN car_carinfo d ON g.carid=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id 
	LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 1 AND a.is_del = 0 
UNION 
	SELECT a.id,a.order_code,a.car_number,d.id as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,c.usertype,FLOOR(TIMESTAMPDIFF(HOUR,a.pk_date,a.re_date)/(24/d.costprice)) as car_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name 
	FROM `order` a 
	LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id 
	LEFT JOIN car_agent f ON d.agent_id=f.id LEFT JOIN car_barand b ON d.brand=b.id 
	WHERE c.usertype = 0 AND a.is_del = 0 
) a 
WHERE a.id > 0 ";

        $countSql = "SELECT count(id) ".$sql;
        $sql = "SELECT id,car_number,car_id,pk_date,re_date,check_out,order_state,agent_check_state,agent_id,agent_name,SUM(a.car_cost) as all_cost,usertype,order_code ".$sql;

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
        }elseif(($select_time == 3) && ($select ==100)){
            $ary=[];
        }else{
            $ary = [$startDate,$stopDate,$key];
        }
        $sql .= $time.$where." GROUP BY a.id";                                                                //对SQL语句进行拼接
        $countSql .= $time.$where." GROUP BY a.id";

//        if ($agent_id != 0){
//            $sql .= " AND (a.agent_id = {$agent_id}) ";
//            $countSql .= " AND (a.agent_id = {$agent_id}) ";
//        }
        switch ($order){
            case 0:
                $sql .= " ORDER BY id DESC";
                break;
            case 1:
                $sql .= " ORDER BY a.pk_date ASC";
                break;
            case 2:
                $sql .= " ORDER BY a.re_date ASC";
                break;
        }

        $app = App::getInstance();
        $list = $app->pageDisplay($sql, $countSql, $pagenum, $ary, 'count(a.id)', true);
//        if ($list){
//            $reg['code'] = 1;
//            $reg['list'] = $list;
//        }else{
//            $reg['code'] = 0;
//            $reg['msg'] = '未查询到相关信息';
//        }
        return $list;
    }

    //根据操作员id获取代理商id
    public function agent_id($user_id){
        $sql = "SELECT b.id AS agent_id FROM admin_user a LEFT JOIN car_agent b ON a.agent_id = b.id WHERE a.id = {$user_id}";
        $agent = M()->query($sql)[0];
        if ($agent['agent_id'] == null){$agent['agent_id'] = 0;}
        return $agent['agent_id'];
    }

}