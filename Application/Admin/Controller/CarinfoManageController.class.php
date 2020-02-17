<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Org\Util\Date;
use Think\Controller;
use Think\Session\Driver\Memcache;

/**
 * Class CarController
 * @package Admin\Controller
 *车辆信息控制器
 */
class CarinfoManageController extends BaseController
{
    protected static $table = 'car_carinfo';
//    车辆信息查询方法index（）
    public function index()
    {
        //接受模糊查询的条件
        $key = I('get.key');
        //将条件传给数组
        $ary = [];
        $null = null;
        $selectid = I('get.select');
        $sql = "SELECT a.id as id,a.carno,a.color,a.isdiscount,a.goodprice,a.costprice,a.motorno,a.usedmileage,a.buydate,a.checkdate,a.usestatus,";
        $sql = $sql . "a.maintainmileage,a.carproperty,a.agent_id,a.auditing_state,b.brand,c.carmodelname,a.addtime,d.name,d.agent_state ";
        $sql = $sql . "FROM car_carinfo a  LEFT JOIN  car_barand b  ON a.brand = b.id  LEFT JOIN car_carmodel c ON a.carmodel = c.id  LEFT JOIN car_agent d ON a.agent_id = d.id  WHERE (a.isdel=0) AND (b.isdel=0) AND (c.isdel=0) AND ((d.agent_state != 1) OR (d.agent_state is null)) ";
        $countSql = "SELECT COUNT(a.id) FROM car_carinfo a LEFT JOIN  car_barand b  ON a.brand = b.id  LEFT JOIN car_carmodel c ON a.carmodel = c.id LEFT JOIN car_agent d ON a.agent_id = d.id WHERE (a.isdel=0) AND ((d.agent_state != 1) OR (d.agent_state is null)) AND ";
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
            default:
                $sql = $sql."ORDER BY a.id DESC";
                $countSql = "SELECT COUNT(a.id) FROM car_carinfo a LEFT JOIN  car_barand b  ON a.brand = b.id  LEFT JOIN car_carmodel c ON a.carmodel = c.id LEFT JOIN car_agent d ON a.agent_id = d.id WHERE (a.isdel=0) AND ((d.agent_state != 1) OR (d.agent_state is null))";
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

    //车辆添加方法加载车型ajax方法
    public function ajax() {
        $brandid = I('post.id');//获取品牌id
        $where['isdel'] = 0;
        $where['barandid'] = $brandid;
        //查询车型
        $sqlcarmodel = M('car_carmodel');
        //根据品牌查询相对应的车型
        $model = $sqlcarmodel->where($where)->field('id,barandid,carmodelname')->select();
        $this->ajaxReturn($model);
    }

//    车辆信息添加方法 addCarinfoManage()
    public function addCarinfoManage()
    {
        $auserInfo = UserInfo();

        $car = M(self::$table);
        //判断输出模板
        if (empty($_POST)){
            //查询品牌
            $sqlbarand = M('car_barand');
            $brand = $sqlbarand->where(array('isdel=0'))->select();
            $this->assign('brand',$brand);
            $this->assign('agent_id',$auserInfo['agent_id']);
            $this->display('CarinfoManage/addCarinfoManage');
        }

        //检查页面传值
        if(!empty($_POST)) {
//            验证表单提交的信息
            $rules = array(
                array('brand','require','<script>alert("品牌不能为空");history.back(-1);</script>',0),
                array('carmodel', 'require', '<script>alert("车辆类型不能为空！");history.back(-1);</script>',0),
                array('carno', 'require', '<script>alert("车牌号不能为空！");history.back(-1);</script>',0),
                array('carproperty', 'require', '<script>alert("车辆性质不能为空！");history.back(-1);</script>', 0),
                array('usestatus', 'require', '<script>alert("使用状态不能为空！");history.back(-1);</script>', 0),
                array('isdiscount', 'require', '<script>alert("是否优惠不能为空！");history.back(-1);</script>', 0),
                //array('goodprice', 'require', '<script>alert("优惠价格不能为空！");history.back(-1);</script>', 0),
                //array('goodprice', 'number', '<script>alert("优惠价格必须为整数！");history.back(-1);</script>', 0),
                array('costprice', 'require', '<script>alert("成本价格不能为空！");history.back(-1);</script>', 0),
                array('imei', 'require', '<script>alert("设备号不能为空！");history.back(-1);</script>', 0),
                array('costprice','number','<script>alert("成本价格必须为整数！");history.back(-1);</script>',0)
            );

            //判断并接受页面传递的值
            if (!$car->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($car->getError());
            } else {
                if (!empty(I('post.costprice'))){
                    $costprice = I('post.costprice');
                } else {
                    $costprice = 0;
                }
                $data = array();
                $data['brand'] = I('post.brand');
                $data['carmodel'] = I('post.carmodel');
                $data['imei'] = I('post.imei');
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
                if ($auserInfo['agent_id'] != 0){
                    $data['auditing_state'] = 0;
                    $data['agent_id'] = $auserInfo['agent_id'];
                }
                //记录操作日志
                if ($car->add($data)) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    $auserInfo = UserInfo();//接收当前管理员登陆名
                    $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        if ($auserInfo['agent_id'] != 0){
                            $this->success("添加成功!", U('CarAgent/carinfo',1));
                        }else{
                            $this->success("添加成功!", U('CarinfoManage/Index',1));
                        }
                    }
                } else {
                    $this->error("添加失败",'',1);
                }

            }
        }
    }

// //车辆修改方法加载车型ajax方法
    public function ajax1 () {
        $where['isdel'] = 0;
        $where['barandid'] = I('post.mid');//获取品牌id
        //查询车型
        $sqlcarmodel = M('car_carmodel');
        //根据品牌查询相对应的车型
        $model = $sqlcarmodel->where($where)->field('id,barandid,carmodelname')->select();
        $this->ajaxReturn($model);

    }

    //车辆信息修改方法 editCarinfoManage（）
    public function editCarinfoManage()
    {
        $car = M(self::$table);
        if (empty($_POST)) {
            $id = I('get.id');
            cookie('id', $id);
            $cid = cookie('id');
            $sql = "SELECT a.id as id,a.carno,a.imei,a.color,a.costprice,a.goodprice,a.motorno,a.usedmileage,a.buydate,a.checkdate,a.usestatus,";
            $sql = $sql . "a.maintainmileage,a.carproperty,a.isdiscount,b.brand,c.carmodelname,b.id as bid,c.id as cid ";
            $sql = $sql . "FROM car_carinfo a  LEFT JOIN  car_barand b  ON a.brand = b.id  LEFT JOIN car_carmodel c ON a.carmodel = c.id  WHERE a.isdel=0 AND b.isdel=0 AND c.isdel=0 AND a.id=%d  ORDER BY ID DESC";
            $ary = [$cid];
            $list = $car->query($sql, $ary);
            cookie('barandid',$list[0]['bid']);
            //查询品牌
            $sqlbarand = M('car_barand');
            $brand = $sqlbarand->where(array('isdel=0'))->select();
            $this->assign('brand',$brand);
            //查询车型
            $sqlcarmodel = M('car_carmodel');
            $selmodel = "SELECT id,barandid,carmodelname FROM car_carmodel WHERE isdel=0 AND barandid=%d";
            $where = [cookie('barandid')];
            $model = $sqlcarmodel->query($selmodel,$where);
            $this->assign('model',$model);
            $this->assign("list",$list);

            $this->Display();
        }
        if(!empty($_POST)) {
            $cid = cookie('id');
//            验证表单提交的信息
            $rules = array(
                array('brand','require','<script>alert("品牌不能为空");window.location.href = document.referrer;</script>',0),
                array('carno', 'require', '<script>alert("车牌号不能为空！");window.location.href = document.referrer;</script>',0),
                array('carmodel', 'require', '<script>alert("车辆类型不能为空！");window.location.href = document.referrer;</script>',0),
                array('carproperty', 'require', '<script>alert("车辆性质不能为空！");window.location.href = document.referrer;</script>',0),
                array('usestatus', 'require', '<script>alert("使用状态不能为空！");window.location.href = document.referrer;</script>', 0),
                array('isdiscount', 'require', '<script>alert("是否优惠不能为空！");window.location.href = document.referrer;</script>', 0),
                array('goodprice', 'require', '<script>alert("优惠价格不能为空！");history.back(-1);</script>', 0),
                array('goodprice', 'number', '<script>alert("优惠价格必须为整数！");history.back(-1);</script>', 0),
                array('costprice', 'require', '<script>alert("成本价格不能为空！");history.back(-1);</script>', 0),
                array('imei', 'require', '<script>alert("设备不能为空！");history.back(-1);</script>', 0),
                array('costprice','number','<script>alert("成本价格必须为整数！");history.back(-1);</script>',0)
            );

            if (!$car->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($car->getError());
            } else {
                $data['brand'] = I('post.brand');
                $data['carmodel'] = I('post.carmodel');
                $data['carno'] = I('post.carno');
                $data['imei'] = I('post.imei');
                $data['color'] = I('post.color');
                $data['motorno'] = I('post.motorno');
                $data['usedmileage'] = I('post.usedmileage');
                $data['maintainmileage'] = I('post.maintainmileage');
                $data['buydate'] = I('post.buydate');
                $data['checkdate'] = I('post.checkdate');
                $data['carproperty'] = I('post.carproperty');
                $data['usestatus'] = I('post.usestatus');
                $data['isdiscount'] = I('post.isdiscount');
                $data['agestyle'] = I('post.agestyle');
                $data['goodprice'] = I('post.goodprice');
                $data['costprice'] = I('post.costprice');
                //记录操作日志
                if ($car->where(array('id=' . $cid))->save($data)) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id',null);
                        $this->success("修改成功!", U('CarinfoManage/Index',1));
                    }
                } else {
                    $this->error("修改失败",'',1);
                }
            }
        }
    }

    //车辆信息逻辑删除方法 del（）
    public function del()
    {
        $delete = M(self::$table);
        //获取需要操作的数据id
        $id = $_GET['id'];
        $data = 1;
        $arr = array();
        $arr['isdel'] = $data;
        $result = $delete->where(array('id='.$id))->save($arr);
        //记录操作日志
        if ($result){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('car_crinfo', $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                $this->success("删除成功!", U('CarModel/ShowCarmodel'));
            }
        } else {
            $delete->getError();
        }
    }


    //批量删除
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $where['id'] = array('IN', $ids);
            $data['isdel'] = 1;
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }
    //审核通过
    public function auditing(){
        $where['id'] = I('get.id');
        $data['auditing_state'] = 1;
        $res = M('car_carinfo')->where($where)->save($data);
        if ($res){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('car_crinfo', $where['id'], '审核通过', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                $this->success("审核成功!", U('CarinfoManage/Index'));
            }
        } else {
            $this->error("审核修改失败",'',1);
        }
    }
//取消审核
    public function auditing_no(){
        $where['id'] = I('get.id');
        $data['auditing_state'] = 0;
        $res = M('car_carinfo')->where($where)->save($data);
        if ($res){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('car_crinfo', $where['id'], '取消审核', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                $this->success("取消审核成功!", U('CarinfoManage/Index'));
            }
        } else {
            $this->error("取消审核修改失败",'',1);
        }
    }


}