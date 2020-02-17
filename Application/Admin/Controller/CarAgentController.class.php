<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Admin\Controller\UserController;

class CarAgentController extends BaseController {
    public $sexArray = array(0,'男','女');
    public function carinfo(){
        $auserInfo = UserInfo();

        if ($auserInfo['agent_id'] != 0){
            $condition = " AND a.agent_id = {$auserInfo['agent_id']}";
        }else{
            $condition = '';
        }
        //接受模糊查询的条件
        $key = I('get.key');
        //将条件传给数组
        $ary = [];
        $selectid = I('get.select');
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
                $auditing_state = I('get.auditing_state');
                $sql = $sql . "AND (a.auditing_state = {$auditing_state}) ";
                $countSql = $countSql . " (a.auditing_state = {$auditing_state})";
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
        if ($selectid != null) {
            $sql = $sql . "ORDER BY a.id DESC";
            $ary = [$key];
        } else {
            $ary = [];
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true);
        $this->display();
    }
    //代理商申请添加车辆
    public function addCar(){
        $user = new CarinfoManageController();
        $user->addCarinfoManage();
    }
    //代理商管理账号信息
    public function auserinfo(){
        $auserinfo = UserInfo();

        $condition = "agent_id = {$auserinfo['agent_id']}";
        if($_GET['demand']){
            $name = I('get.demand');
            $sql="SELECT * FROM admin_user WHERE NAME Like '%%%s%%' ";
            $countSql = "SELECT count(id) FROM admin_user WHERE NAME Like '%%%s%%' ";
            $ary = array($name);
        }else{
            $sql = "SELECT * FROM admin_user ";
            $countSql = "SELECT count(id) FROM admin_user";
            $ary = array();
        }

        if ($auserinfo['agent_id'] != 0){
            if($_GET['demand']){
                $sql .= " AND {$condition}";
                $countSql .= " AND {$condition}";
            }else{
                $sql .= " WHERE {$condition}";
                $countSql .= " WHERE {$condition}";
            }

        }
//        $this->pageDisplay($sql,$countSql,$pageNum, $keyName,$listName,$showPage,$isPage);
        $this->pageDisplay($sql,$countSql,16,$ary,'count(id)','list','page',true);
        $this->assign('sex',$this->sexArray);
        $authModel=D('AuthGroup')->where('is_del = 0')->field('id,title')->select();
        $agent = M('car_agent')->field('id,name')->select();
        $this->assign('auth',$authModel);
        $this->assign('agent',$agent);
        $this->display();

    }
//    //代理商申请添加操作员
//    public function auseradd() {
//        $user = new UserController();
//        $user->add();
//    }
    //代理商订单信息
    public function orderinfo() {
        $user = new OrderController();
        $auserInfo = UserInfo();

        $agent_id = $auserInfo['agent_id'];
        $orderstate = " AND (a.order_state != 10)";
        if ($agent_id != 0){
            $orderstate .= " AND (a.agent_id = {$agent_id})";
        }
        if(isset($_GET['select'])){
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
            if ($select == 3){
                $key = I('get.key_state');
            }
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'CarAgent/Index',$orderstate);
        }else{
            $this->q_query(3,0,0,100,0,'CarAgent/Index',$orderstate);
        }
    }
    //代理商申请结账信息
    public function check_info() {
        $sql ="SELECT a.id,a.pack_id,a.price_rec,a.collections_rec,a.state,a.addtime,a.check_date,b.`name` AS user_name,c.`name` as agent_name FROM order_agent a LEFT JOIN admin_user b ON a.user_id=b.id LEFT JOIN car_agent c ON a.agent_id=c.id WHERE (a.is_del = 0)";
        $countSql = "SELECT count(a.id) FROM order_agent a LEFT JOIN admin_user b ON a.user_id=b.id LEFT JOIN car_agent c ON a.agent_id=c.id WHERE (a.is_del = 0)";
        $auserInfo = UserInfo();
        $agent_id = $auserInfo['agent_id'];
        if ($agent_id != 0){
            $sql .= " AND (a.agent_id = {$agent_id})";
            $countSql .= " AND (a.agent_id = {$agent_id})";
        }
        $ary = [];
        if (isset($_GET['select'])){
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
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true);
        $this->display();
    }
    //打包订单详情
    public function packInfo() {

        if (isset($_GET['id'])){
            $id = I('get.id');
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
            if (isset($_POST['select'])){
                $select = I('post.select');
                $key = I('post.key');
                switch ($select){
                    case 0 :
                        $where = " AND (a.order_code LIKE '%%{$key}%%')";
                        break;
                    case 3 :
                        $key = I('post.key_state');
                        $where = " AND (a.order_state LIKE '%%{$key}%%')";
                        break;
                    case 5 :
                        $where = " AND (a.carno LIKE '%%{$key}%%')";
                        break;
                    case 6 :
                        $where = " AND (a.agent_name LIKE '%%{$key}%%')";
                        break;
                }
                $select_time = I('post.select_time');
                $star_time = I('post.start');
                $stop_time = I('post.stop');

                switch ($select_time){
                    case 0:
                        $time = " AND (a.re_date BETWEEN '%s' AND '%s')";                     //根据还车时间区间查询
                        break;
                    case 1:
                        $time = " AND (a.pk_date BETWEEN '%s' AND '%s')";                        //根据取车时间区间查询
                        break;
                    case 2:
                        $time = " AND (a.order_date BETWEEN '%s' AND '%s')";                        //根据下单时间区间查询
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
            $orderinfo = M('order')->query($sql);
            $packInfo['pack'] = $pack[0];
            $packInfo['order'] = $orderinfo;

            $carno_ary = M()->query($carno_sql);
            $this->assign('pack',$pack[0]);
            $this->assign('carno_ary',$carno_ary);
            $this->assign('orderinfo',$orderinfo);

            $this->display();
        }
    }
    //代理商可申请打包订单
    public function checkout() {
        $auserInfo = UserInfo();

        $agent_id = $auserInfo['agent_id'];
        if ($agent_id != 0){
            $orderstate = " AND (a.agent_id = {$agent_id}) AND (a.order_state > 3) AND (a.agent_check_state = 0 ) AND (a.check_out = 1 )";
        }else{
            $orderstate = "AND (a.agent_id != 0) AND (a.order_state > 3) AND (a.agent_check_state = 0 ) AND (a.check_out = 1 )";
        }
        $order = new OrderController();
        if(isset($_GET['select'])){
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
            if ($select == 3){
                $key = I('get.key_state');
            }
            $msg['select'] = $select;
            $msg['start'] = $startDate;
            $msg['stop'] = $stopDate;
            $msg['key'] = $key;
            $this->assign('msg',$msg);
            $this->q_query($select_time,$startDate,$stopDate,$select,$key,'CarAgent/checkout',$orderstate);
        }else{
            $this->q_query(3,0,0,100,0,'CarAgent/checkout',$orderstate);
        }
    }
    //取消申请
    public function cancel(){
        $model = M();
        if (isset($_GET['id'])){
            $where['id'] = I('get.id');
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
                $auserInfo = UserInfo();
                $log = self::writeLog('order_agent', $where['id'], '取消申请', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                    exit('<script type="text/javascript">alert("已取消申请！");location.href="'.U('CarAgent/check_info').'";</script>');
                }
            }else{
                exit('<script type="text/javascript">alert("取消申请失败");history.go(-1);</script>');
            }
        }
    }
    //结账申请批量删除
    public function delAll(){
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M('order_agent');
            $where['id'] = array('IN', $ids);
            $data['is_del'] = 1;
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog('order_agent', $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }
    //对代理商申请结账的订单打包
    public function pack() {
        $day = date("d", time());       //判断是否为每月的1号或者15号
        if ($day !=1 && $day !=15){    //如果不是，则不能申请结账
            $reg['code'] = 4;
            echo json_encode($reg);
            exit;
        }
        if (isset($_POST['arr']) && !empty($_POST['arr'])){
            $auserInfo = UserInfo();

            $arr = I('post.arr');
            $str = '';
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
                $auserInfo = UserInfo();
                $log = self::writeLog('order_agent', $msg, '申请结账', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                }
                $reg['code'] = 1;
            }else{
                $reg['code'] = 2;
            }
        }else{
            $reg['code'] = 0;
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
     * @param string $order_state 限制订单状态
     * @param int $order 排序方式（默认0：id倒序;1：取车时间正序;2、还车时间正序）
     * @return Action
     */
    public function q_query($select_time=3,$startDate=0,$stopDate=0,$select=100,$key,$themes='Index',$order_state=0,$order=0){

        $sql = "SELECT a.*,b.carmodelname,c.username,c.phone,c.usertype,d.carno,f.name AS agent_name FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id LEFT JOIN car_agent f ON a.agent_id=f.id WHERE (a.is_del=0) ";
        $countSql = "SELECT count(a.id) FROM `order` a LEFT JOIN car_carmodel b ON a.carmodelid=b.id LEFT JOIN work_member c ON a.uid=c.id LEFT JOIN car_carinfo d ON a.car_id=d.id WHERE (a.is_del=0)";


//        $sql = "FROM (
//                        SELECT a.id,a.car_number,g.carid as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,FLOOR(TIMESTAMPDIFF(HOUR,a.pk_date,a.re_date)/(1/d.costprice)) as all_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name FROM `order` a
//                        LEFT JOIN work_member c ON a.uid=c.id
//                        LEFT JOIN order_car g ON a.id = g.orderid
//                        LEFT JOIN car_carinfo d ON g.carid=d.id
//                        LEFT JOIN car_agent f ON d.agent_id=f.id
//LEFT JOIN car_barand b ON d.brand=b.id
//                        WHERE c.usertype = 1 AND a.is_del = 0
//                        UNION
//                        SELECT a.id,a.car_number,d.id as car_id,a.pk_date,a.re_date,a.check_out,a.order_state,a.agent_check_state,b.brand,FLOOR(TIMESTAMPDIFF(HOUR,a.pk_date,a.re_date)/(1/d.costprice)) as all_cost,d.costprice,d.carno,d.agent_id,f.name as agent_name FROM `order` a
//                        LEFT JOIN work_member c ON a.uid=c.id
//                        LEFT JOIN car_carinfo d ON a.car_id=d.id
//                        LEFT JOIN car_agent f ON d.agent_id=f.id
//LEFT JOIN car_barand b ON d.brand=b.id
//                        WHERE c.usertype = 0 AND a.is_del = 0
//                    ) a WHERE a.id > 0 ";
//
//        $countSql = "SELECT count(a.id) ".$sql;
//        $sql = "SELECT * ".$sql;

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
        $carno_sql = "SELECT id,carno ".$sql;
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
                $where = " AND (a.carno LIKE '%%%s%%')";                             //对车辆号牌进行查询
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
        $sql .= $time.$where." GROUP BY a.id";                                                                //对SQL语句进行拼接
        $countSql .= $time.$where." GROUP BY a.id";
        $carno_sql .= $time.$where;
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
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示

        $carno_ary = M()->query($carno_sql,$ary); //查询该代理使用中的车辆
        $this->assign('carno_ary',$carno_ary);
        $this->display($themes);
    }

}