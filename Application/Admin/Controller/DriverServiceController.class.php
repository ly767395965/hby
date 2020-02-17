<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

class DriverServiceController extends BaseController
{

    public function index()
    {

        $sql = "SELECT a.id,a.drivername,c.evaluateNo,d.ComplaintNo,e.IllegalNo,f.punishNo,g.ServiceGrade 
 FROM car_driverinfo a LEFT JOIN order_netcar b ON a.id=b.DriverId LEFT JOIN (SELECT COUNT(a.id) as evaluateNo,b.DriverId FROM evaluate a LEFT JOIN order_netcar b ON a.OrderCode=b.OrderCode 
 WHERE Type = 0 GROUP BY b.DriverId) c ON a.id=c.DriverId LEFT JOIN (SELECT COUNT(a.id) as ComplaintNo,b.DriverId FROM evaluate a LEFT JOIN order_netcar b ON a.OrderCode=b.OrderCode 
WHERE Type = 1 GROUP BY b.DriverId) d ON a.id=d.DriverId LEFT JOIN (SELECT a.id as IllegalNo,a.DriverId FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId=b.id WHERE a.PeccancyType=0 GROUP BY a.DriverId) e ON a.id=e.DriverId LEFT JOIN (SELECT a.id as punishNo,a.DriverId FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId=b.id WHERE a.PeccancyType=1 GROUP BY a.DriverId) f ON a.id=f.DriverId LEFT JOIN (SELECT a.ServiceGrade,a.DriverId FROM driver_server a LEFT JOIN car_driverinfo b ON a.DriverId=b.id GROUP BY a.DriverId) g ON g.DriverId=a.id GROUP BY a.drivername ORDER BY a.id ASC";

        $countSql = "SELECT COUNT(a.id) FROM  (SELECT a.id
 FROM car_driverinfo a LEFT JOIN order_netcar b ON a.id=b.DriverId LEFT JOIN (SELECT COUNT(a.id) as evaluateNo,b.DriverId FROM evaluate a LEFT JOIN order_netcar b ON a.OrderCode=b.OrderCode 
 WHERE Type = 0 GROUP BY b.DriverId) c ON a.id=c.DriverId LEFT JOIN (SELECT COUNT(a.id) as ComplaintNo,b.DriverId FROM evaluate a LEFT JOIN order_netcar b ON a.OrderCode=b.OrderCode 
WHERE Type = 1 GROUP BY b.DriverId) d ON a.id=d.DriverId LEFT JOIN (SELECT a.id as IllegalNo,a.DriverId FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId=b.id WHERE a.PeccancyType=0 GROUP BY a.DriverId
) e ON a.id=e.DriverId LEFT JOIN (SELECT a.id as punishNo,a.DriverId FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId=b.id WHERE a.PeccancyType=1 GROUP BY a.DriverId) f ON a.id=f.DriverId LEFT JOIN (SELECT a.ServiceGrade,a.DriverId FROM driver_server a LEFT JOIN car_driverinfo b ON a.DriverId=b.id GROUP BY a.DriverId) g ON g.DriverId=a.id 
 GROUP BY a.drivername ) a";

        $this->pageDisplay($sql, $countSql, 16, [], 'count(a.id)', 'list', 'page', true);
        $this->display();

    }


    //驾驶员信誉定级
    public function driverGrade()
    {

        if ($_GET) {
            $tab = M();
            $id = I('get.id');
            $illegalno = I('get.illegalno');//违章
            $punishno = I('get.punishno');//处罚
            $sql = "SELECT id,ServiceGrade,ServiceCheckCompany,FullTimeDriver,InDriverBlacklist,ServiceCheckDate FROM driver_server WHERE DriverId=%d ";
            $list = $tab->query($sql, [$id]);
            $this->assign('list', $list);
            $this->assign('id', $id);
            $this->assign('illegalno', $illegalno);
            $this->assign('punishno', $punishno);
            $this->display();
        }
        if ($_POST) {
            $server['DriverId'] = I('post.driverid');//驾驶员id
            $server['PeccancySum'] = I('post.illegalno');//交通违章次数
            $server['PunishSum'] = I('post.punishno');//处罚次数
            $server['ServiceGrade'] = I('post.grade');//服务质量信誉等级
            $server['ServiceCheckDate'] = I('post.ServiceCheckDate');//服务质量信誉考核日期
            $server['ServiceCheckCompany'] = I('post.name');;//服务质量信誉考核日期
            $server['FullTimeDriver'] = I('post.ismajor');;//是否专职驾驶员
            $server['InDriverBlacklist'] = I('post.blacklist');;//是否在驾驶员黑名单
            $server['UpdateTime'] = Date('Y-m-d H:i:s');//是否在驾驶员黑名单
            $ordercount = $this->orderCount($server['DriverId']);
            $server['OrderSum'] = $ordercount[0]['count(id)'];//完成订单数
            $tabs = M('driver_server');
            if (is_numeric($server['ServiceGrade'])) {
                $sql = "SELECT id FROM driver_server WHERE DriverId = %d";
                if (M()->query($sql,[$server['DriverId']])){    //判断该驾驶员是否为第一次评级
                    $re = $tabs->where(array('DriverId'=>$server['DriverId']))->save($server);
                }else{
                    $re = $tabs->add($server);
                }
                if ($re) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    self::writeLog('driver_server',$server['DriverId'], 'driverGrade', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    $this->success("评级成功!", U('DriverService/Index', 1));
                    $this->infoSub($server['DriverId']);
                } else {
                    $this->error("评级失败");
                }
            } else {
                $this->error("输入的参数不是数字");
            }

        }

    }




    //统计当前驾驶员完成订单次数
    public function orderCount($id){
        $tab = M();
        $sql = "SELECT COUNT(id) FROM order_netcar WHERE DriverId=%d ";
        $count = $tab->query($sql,[$id]);
        return $count;
    }

//查询评价投诉信息
    public function detailedCount1(){
        $id = I('get.id');
        $sql1 = "SELECT a.id,c.username,a.OrderCode,a.EvaluateTime,a.Type,a.Remark,a.ServerFeel,a.IsComplaint FROM evaluate a LEFT JOIN order_netcar b ON a.OrderCode=b.OrderCode LEFT JOIN work_member c ON b.PassengerId = c.id WHERE b.DriverId = %d ";
        $countSql1 = "SELECT COUNT(a.id) FROM evaluate a LEFT JOIN order_netcar b ON a.OrderCode=b.OrderCode LEFT JOIN work_member c ON b.PassengerId = c.id WHERE b.DriverId = %d ";
        $arr = [$id];
        $this->pageDisplay($sql1, $countSql1, 15, $arr, 'count(a.id)', 'list', 'page', true);
        $this->display();
    }


    //查询违章处罚信息
    public function detailedCount2(){
        $id = I('get.id');
        $sql2 = "SELECT a.id,b.drivername,a.PeccancyType,a.PeccancyDate,a.PeccancyContent,a.Remark,a.IsHandle FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId=b.id WHERE a.DriverId = %d";
        $countSql2 = "SELECT COUNT(a.id) FROM driver_peccancy a LEFT JOIN car_driverinfo b ON a.DriverId=b.id WHERE a.DriverId = %d";
        $ary = [$id];
        $this->pageDisplay($sql2, $countSql2, 15, $ary, 'count(a.id)', 'list1', 'page1', true);
        $this->display();
    }

    /**
     * 查询评价/投诉信息
     */
    public function showdetailed()
    {
        $id = I('post.id');
        $tab = M();
        $sql = "SELECT Remark FROM evaluate WHERE id = %d ";
        $list = $tab->query($sql,[$id]);
        $this->ajaxReturn(array('info'=>$list[0]['remark']));
    }

    /**
     * 查询违章/处罚信息
     */
    public function showdetailed1()
    {
        $id = I('post.id');
        $tab = M();
        $sql = "SELECT PeccancyContent FROM driver_peccancy WHERE id = %d ";
        $list = $tab->query($sql,[$id]);
        $this->ajaxReturn(array('info'=>$list[0]['peccancycontent']));
    }

    //驾驶员信誉信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('ratedDriver',$id);
    }

}