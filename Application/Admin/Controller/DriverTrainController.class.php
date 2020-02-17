<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *驾驶员培训信息控制器
 */
class DriverTrainController extends BaseController
{

    public function index()
    {

        $sql = "SELECT a.id,b.drivername,a.CourseDate,a.StartTime,a.StopTime,a.Duration,c.topicname,a.AddTime FROM driver_train a LEFT JOIN car_driverinfo b ON a.DriverId=b.id LEFT JOIN driver_topic c ON a.CourseName=c.id WHERE a.Flag != %d ";
        $countSql = "SELECT COUNT(a.ID) FROM driver_train a LEFT JOIN car_driverinfo b ON a.DriverId=b.id LEFT JOIN driver_topic c ON a.CourseName=c.id WHERE a.Flag != %d ";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(3);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true);
        $this->display();

    }

    /**
     * 添加驾驶员培训记录
     */
    public function addDriverTrain()
    {
        $tab = M();
        if (empty($_POST)){
            $sql = "SELECT id,topicname FROM driver_topic WHERE state=%d AND isdel = %d ";
            $list = $tab->query($sql,[0,0]);

            $sqlDriver = "SELECT id,drivername,phone FROM car_driverinfo WHERE isdel=%d ";
            $driver = $tab->query($sqlDriver,[0]);
            $this->assign('list',$list);
            $this->assign('driver',$driver);
            $this->display();
        }

        if($_POST){
            $topicId = I('post.topicId');
            $be_driver = I('post.be_driver');
            $CourseDate = I('post.CourseDate');
            $StartTime = I('post.StartTime');
            $StopTime = I('post.StopTime');
            $pieces = explode(",", $be_driver);
            $count  =  count($pieces);

            $date = Date('Y-m-d H:i:sA');

            $startdate=strtotime($StartTime);
            $enddate=strtotime($StopTime);
            $days=round(($enddate-$startdate)/3600/24) ;

            $bgin = substr($StartTime,0,10);
            $end  = substr($StopTime,0,10);
            if ($bgin == $end){
                $days = 1;
            } else {
                $days = $days;
            }

            for ($i = 0;$i<$count;$i++){
                echo $pieces[$i];
                $add = "INSERT INTO driver_train (DriverId,CourseDate,StartTime,StopTime,Duration,UpdateTime,CourseName,AddTime,Flag) VALUES (%d,'%s','%s','%s',%d,'%s',%d,'%s',%d)";
                $arr = [$pieces[$i],$CourseDate,$StartTime,$StopTime,$days,$date,$topicId,$date,1];
                $re = $tab->execute($add,$arr);
                if ($re){
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    //获取添加成功返回的数据id
                    $returnid = M('driver_train')->order('id desc')->find();
                    $log = self::writeLog('driver_train', $returnid['id'], 'addDriverTrain', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    $this->infoSub($returnid['id']);
                    if($log){
                        $this->success("添加成功!", U('DriverTrain/Index',1));
                    }
                } else {
                    $this->error("添加失败",'',1);
                }
            }

        }
    }

    /**
     * 驾驶员培训记录
     */
    public function delTrain()
    {
        $adInfo = M('driver_train');
        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        $id = I('get.id');
        $data['Flag'] = 3;
        //如果该分类下没有数据则可删除该分类
        $LinkModlesace = $adInfo->where(array('id' => $id))->save($data);
        if ($LinkModlesace) {
            //记录操作日志
            $log = self::writeLog('driver_train', $id, 'delTrain', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            $this->infoSub($id);
            if ($log) {
                $this->success('删除成功!', U('DriverTrain/Index',1));
            }
        } else {
            $this->error('删除失败','',1);
        }


    }

//批量删除
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M('driver_train');
            $where['id'] = array('IN', $ids);
            $data['Flag'] = 3;
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog('driver_train', $id, 'delAllDriverTrain', date('Y-m-d H:i:sA'), self::cookieName());

                }
                $this->infoSub($id);
                $this->ajaxReturn(array('state' => 1));
                $this->success('删除成功!', U('DriverTrain/Index',1));
            }
        }


    }

    //车辆基本信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoDriverEducate',$id);
    }



}
