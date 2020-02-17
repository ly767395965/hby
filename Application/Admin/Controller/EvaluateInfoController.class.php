<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Org\Util\Date;
use Think\Controller;

class EvaluateInfoController extends BaseController {

    public function index(){
        $startDate = $_GET['start'];//开始时间
        $stopDate = $_GET['stop'];//结束时间
        $key = I('get.key');//接受模糊查询的条件
        $selectId = I('get.select');
        $sql = "SELECT a.id,d.username,c.drivername,a.OrderCode,a.EvaluateTime,a.Type,a.Remark,a.ServerFeel,a.IsComplaint,a.Result from evaluate a LEFT JOIN order_netcar b ON 
 a.ordercode=b.ordercode LEFT JOIN car_driverinfo c ON b.DriverId = c.id LEFT JOIN work_member d ON a.uid=d.id";
        $countSql = " SELECT COUNT(a.id) from evaluate a LEFT JOIN order_netcar b ON 
 a.ordercode=b.ordercode LEFT JOIN car_driverinfo c ON b.DriverId = c.id LEFT JOIN work_member d ON a.uid=d.id";
        switch ($selectId) {
            case '0':
                $state = I('get.state');
                $arr = [$state,$key];
                $sql = $sql." WHERE  (a.type = %d) AND (c.drivername = '%s') ";
                $countSql = $countSql." WHERE (a.type = %d) AND (c.drivername = '%s')";
                break;
            case '1':
                $arr = [$key];
                $sql = $sql." WHERE  (a.OrderCode = '%s') ";
                $countSql = $countSql." WHERE (a.OrderCode = '%s') ";
                break;
            case '2':
                $arr = [$startDate,$stopDate];
                $sql = $sql." WHERE  (a.EvaluateTime BETWEEN '%s' AND '%s') ";
                $countSql = $countSql." WHERE (a.EvaluateTime BETWEEN '%s' AND '%s') ";
                break;
            default :
                $arr = [];
                $sql = $sql;
                $countSql = $countSql;
                break;
        }

        $sql = $sql . " ORDER BY a.id DESC";
        $this->pageDisplay($sql, $countSql, 16, $arr, 'count(a.id)', 'list', 'page', true); //分页显示

        $this->display();

    }


    /**
     * @return 评价/投诉详细信息
     */
    public function evaluateInfo()
    {
        $tab = M();
        $id = I('get.id');
        $sql = "SELECT a.id,b.username,a.OrderCode,d.drivername,a.EvaluateTime,a.Type,a.Remark,a.ServerFeel,a.IsComplaint,a.Result FROM evaluate a LEFT JOIN work_member b ON a.Uid=b.id LEFT JOIN order_netcar c ON a.OrderCode=c.OrderCode LEFT JOIN car_driverinfo d ON c.DriverId=d.id WHERE a.id = %d ";
        $arr = [$id];
        $list = $tab->query($sql,$arr);
        $this->assign('list',$list[0]);
        $this->display();

    }

    /**
     * @return 投诉信息
     */
    public function evaluateHandle()
    {
        $tab = M();
        if(empty($_POST)){

            $id = I('get.id');
            $sql = "SELECT a.id,b.username,a.OrderCode,d.drivername,a.EvaluateTime,a.Type,a.Remark,a.ServerFeel,a.IsComplaint,a.Result FROM evaluate a LEFT JOIN work_member b ON a.Uid=b.id LEFT JOIN order_netcar c ON a.OrderCode=c.OrderCode LEFT JOIN car_driverinfo d ON c.DriverId=d.id WHERE a.id = %d ";
            $arr = [$id];
            $list = $tab->query($sql,$arr);
            $this->assign('list',$list[0]);
            $this->display();
        }

        if ($_POST){
            $id = I('post.id');
            $result = I('post.result');
            if (empty($result)){
                $this->error('处理结果不能为空!','',1);
            } else {
                $sqlUp = "UPDATE evaluate SET result = '%s',IsComplaint = %d WHERE id = %d ";
                $ary = [$result,1,$id];
                $re = $tab->execute($sqlUp,$ary);
                if ($re){
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    self::writeLog('evaluate', $id, 'evaluateHandle', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    $this->success('提交成功',U('EvaluateInfo/Index'),1);
                    $this->infoSub($id);
                } else {
                    $this->error('提交失败','',1);
                }
            }

        }
    }

//乘客投诉信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('ratedPassengerComplaint',$id);
    }


}