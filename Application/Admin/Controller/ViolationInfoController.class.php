<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Org\Util\Date;
use Think\Controller;

class ViolationInfoController extends BaseController {

    public function index(){

        $sql = "SELECT a.id,a.addtime,b.drivername,a.PeccancyType,a.PeccancyDate,a.PeccancyContent,a.IsHandle FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId = b.id ORDER BY a.id DESC";
        $countSql = "SELECT COUNT(a.id) FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId = b.id";
        $this->pageDisplay($sql, $countSql, 16, [], 'count(a.id)', 'list', 'page', true); //分页显示
        $this->display();

    }


    //处理信息
    public function driverHandle(){
        $id = I('get.id');
        $tab = M();
        $sql = "UPDATE driver_peccancy SET IsHandle = %d WHERE id=%d ";
        $arr = [1,$id];
        $res = $tab->execute($sql,$arr);
        if ($res){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog('driver_peccancy', $id, 'driverHandle', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            echo '<script>alert("信息处理成功");self.location=document.referrer;</script>';
            $this->infoSub($id);
        } else {
            echo '<script>alert("信息处理失败");self.location=document.referrer;</script>';
        }
    }

    //添加违章/处罚信息
    public function addViolation(){
        $tab = M();
        if (empty($_POST)) {
            $sql = "SELECT id,drivername FROM car_driverinfo WHERE isdel = 0";
            $list = $tab->query($sql);
            $this->assign('list',$list);
            $this->display();
        }


        if($_POST){
            $driverid = I('post.driverid');
            $infoType = I('post.type');
            $date = I('post.date');
            $textinfo = I('post.content');
            $PeccancyAddress = I('post.PeccancyAddress');
            $newtiem = date('Y-m-d H:i',$_SERVER['REQUEST_TIME']);//创建当前时间

            if ($driverid >0){
                $insert  = "INSERT INTO driver_peccancy (DriverId,PeccancyType,PeccancyDate,PeccancyContent,IsHandle,addtime,PeccancyAddress) VALUES (%d,%d,'%s','%s',%d,'%s','%s')";
                $arr  =[$driverid,$infoType,$date,$textinfo,0,$newtiem,$PeccancyAddress];
                $re = $tab->execute($insert,$arr);
                if ($re){
                    $returnid = M('driver_peccancy')->order('id desc')->find();
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    self::writeLog('driver_peccancy', $returnid['id'], 'addViolation', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    $this->success("添加成功!", U('ViolationInfo/Index',1));
                    $this->infoSub($returnid['id']);
                } else {
                    $this->error("添加失败",'',1);
                }
            } else {
                echo '<script>alert("司机不能为空");history.go(-1);</script>';
            }
        }

    }

//驾驶员处罚信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('ratedDriverPunish',$id);
    }

}