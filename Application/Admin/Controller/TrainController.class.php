<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *院校名单
 */
class TrainController extends BaseController
{
    protected static $table = 'sc_train';
    public function index()
    {
        $sql = "SELECT a.id,b.carno,c.schoolname,a.starttime,startdate,d.route_name FROM sc_train a LEFT JOIN car_carinfo b ON a.cartid =b.id LEFT JOIN sc_academy c ON a.schoolid=c.id LEFT JOIN sc_route d ON a.linenumber = d.id";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(a.id) FROM sc_train a LEFT JOIN car_carinfo b ON a.cartid =b.id LEFT JOIN sc_academy c ON a.schoolid=c.id LEFT JOIN sc_route d ON a.linenumber = d.id";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(0);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, $countSql, 16, '', 'count(a.id)', 'list', 'page', true);
        $this->display();

    }

    public function addTrain()
    {
        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        //判断输出模板
        if (empty($_POST)) {
            $m = M();
            $sql = "SELECT a.id,a.schoolid,b.schoolname FROM sc_school a LEFT JOIN sc_academy b ON a.schoolid=b.id";
            $school = $m->query($sql);
            $this->assign('school',$school);
            $this->display();
        }
        if ($_POST) {
            $newclass = M(self::$table);
            $data['schoolid'] = I('post.schoolid');
            $data['starttime'] = I('post.Setout');
            $data['cartid'] = I('post.cartid');
            $data['linenumber'] = I('post.route');
            //记录操作日志
            if ($newclass->add($data)) {
                //获取添加成功返回的数据id
                $returnid = M(self::$table)->order('id desc')->find();
                $log = self::writeLog(self::$table, $returnid['id'], 'addTrain', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    $this->success("添加成功!", U('Train/Index',1));
                }
            } else {
                $this->error("添加失败",'',1);
            }
        }

    }

    public function getTrainCarts(){
        $m = M();
        $schoolid = I('post.schoolid');
        //车辆
        $sql = "SELECT a.id,a.cartid,d.carno FROM sc_carts a LEFT JOIN sc_school b ON a.bossid=b.bossid LEFT JOIN `order` c ON a.orderid=c.id LEFT JOIN car_carinfo d ON a.cartid=d.id  WHERE b.schoolid = '%d' AND order_state < '%d'";
        $carts = $m->query($sql,[$schoolid,4]);

        //路线
        $sql = "SELECT id,route_name FROM sc_route WHERE school_id = '%d' AND is_enable = '%d' AND is_delete = '%d'";
        $routeList = $m->query($sql,[$schoolid,0,0]);


        $this->ajaxReturn(array('carts'=>$carts,'route'=>$routeList));

    }


    public function del() {
        $link = M('sc_train');
        $id = $_GET['id'];
        $LinkModlesace=$link->where(array('id'=>$id))->delete();
        //记录操作日志
        if ($LinkModlesace) {
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table, $id, 'delTrain', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
                $this->success('删除成功!', U('Train/Index',1));
            }
        }else{
            $this->error('删除失败','',1);
        }

    }

    //批量删除
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $where['id'] = array('IN', $ids);
            $res = $m->where($where)->delete();
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAllTrain', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }

}
