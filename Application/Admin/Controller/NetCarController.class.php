<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Common\Common\sendAPI;
use Think\Controller;

class NetCarController extends BaseController {

    public function index(){

        $sql = "SELECT a.id,a.DriverId,a.PassengerId,b.username,a.DepPrice,a.OrderCode,a.OrderTime,a.OrderPhone,a.BookDepTime,a.Departure,a.Destination,a.OrderState,d.drivername,d.phone,e.VehicleNo FROM order_netcar a LEFT JOIN work_member b ON a.PassengerId=b.id LEFT JOIN order_settlement c ON a.OrderCode=c.OrderCode LEFT JOIN car_driverinfo d ON c.DriverId=d.id LEFT JOIN car_net e ON c.CarId=e.id WHERE a.OrderState < %d";
        $countSql = "SELECT COUNT(a.id) FROM order_netcar a LEFT JOIN work_member b ON a.PassengerId=b.id LEFT JOIN order_settlement c ON a.OrderCode=c.OrderCode LEFT JOIN car_driverinfo d ON a.id=d.id LEFT JOIN car_net e ON d.CarId=e.id WHERE a.OrderState < %d";
        $ary = [5];
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示
        $this->display();

    }



    //指派订单
    public function appointService(){

        if ($_GET){
            $tab = M();
            $id = I('get.id');//订单id
            $uid = I('get.uid');//用户id
            $OrderState = I('get.OrderState');//订单号
            $sql = "SELECT a.id,a.drivername,b.id as carid,b.VehicleNo,b.BizStatus FROM car_driverinfo a LEFT JOIN car_net b ON a.CarId = b.id WHERE a.state = 0 ";
            $list = $tab->query($sql);
            $this->assign('list',$list);
            $this->assign('orderId',$id);
            $this->assign('uid',$uid);
            $this->assign('orderState',$OrderState);
            $doCar = $this->queryCar();
            $this->assign('car',$doCar);
            $this->display();
        }

        if ($_POST){
            $tab = M();
            $driverId = I('post.driverId');
            $carId = I('post.carId');
            $orderid = I('post.orderid');
            $ordercode = I('post.ordercode');
            $uid = I('post.uid');
            $nowdate = Date('Y-m-d H:i:sA');
            //修改订单数据
            $sql = "UPDATE order_netcar SET DriverId = %d,OrderState=%d WHERE id = %d";
            $arr = [$driverId,2,$orderid];
            $change_order_netcar = $tab->execute($sql,$arr);

            //修改订单结算表数据
            $upsettlement = "INSERT INTO order_settlement (DriverId,CarId,OrderCode,PassengerId,DistributeTime) VALUES (%d,%d,'%s',%d,'%s')";
            $ary = [$driverId,$carId,$ordercode,$uid,$nowdate];
            $re = $tab->execute($upsettlement,$ary);

            //修改代驾状态
            $car_driverinfo = "UPDATE car_driverinfo SET state = %d,CarId=%d WHERE id = %d";
            $driver = [1,$carId,$driverId];
            $driverres = $tab->execute($car_driverinfo,$driver);

            //修改车辆状态
            $car_net  ="UPDATE car_net SET BizStatus = %d WHERE id = %d";
            $car = [2,$carId];
            $carres = $tab->execute($car_net,$car);

            if ($change_order_netcar >0 && $re > 0 && $driverres > 0 && $carres > 0){
                //$send = $this->sendMessage($orderid); 发送短信
				//接收当前管理员登陆名
				$auserInfo = UserInfo();
				self::writeLog('order_netcar', $orderid, 'dispatch', Date('Y-m-d H:i:sA'), $auserInfo['name']);
				$this->infoSub($ordercode);
				$this->ajaxReturn(array('flag'=>1));

            }else {
                $this->ajaxReturn(array('flag'=>0));
            }
        }

    }


    //派单成功发送短信
    public function sendMessage($orderid){
        $tab = M();
        $sql = "SELECT a.OrderPhone,a.UsePhone,b.drivername,b.phone FROM order_netcar a LEFT JOIN car_driverinfo b ON a.DriverId = b.id WHERE a.id =%d ";
        $arr = [$orderid];
        $res = $tab->query($sql,$arr);

        if (empty($res[0]['usephone'])){
            $usertel = $res[0]['orderphone'];
        } else {
            $usertel = $res[0]['usephone'];
        }
        $url 		= "http://www.api.zthysms.com/sendSms.do";//提交地址
        $username 	= 'huabanghy';//用户名
        $password 	= 'Ujwidz';//原密码
        $sendAPI = new sendAPI($url, $username, $password);
        $data = array(
            'content' 	=> '尊敬的用户:您的订单已派单成功，司机电话为：'.$res[0]['phone'].'，请保持电话畅通【华邦出行】。',//短信内容 48字
            'mobile' 	=> $usertel,//手机号码
            'xh'		=> ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $return = $sendAPI->sendSMS('POST');//GET or POST
        $result = explode(',', $return);
        if ($result[0] == 1) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 取消网约车订单
     */
     public function cancelNetCarOrder()
    {
        $tab =M();
        $id = I('get.id');
        $tab->startTrans();
        $orderCode = M('order_netcar')->field('OrderCode,PassengerId')->where(['id'=>$id])->find();
        $sql = "UPDATE order_netcar SET OrderState = %d WHERE id = %d ";
        $arr = [10,$id];
        $re = $tab->execute($sql,$arr);

        $sql = "INSERT INTO order_revoke (Uid,OrderCode,RevokeTime,Responsible,CancelTypeCode) VALUES (%d,'%s','%s',%d,%d)";
        $now_time = date('Y-m-d H:i:s',time());
		$arr = [$orderCode['passengerid'],$orderCode['ordercode'],$now_time,3,3];
        $order_re = $tab->execute($sql,$arr);

        if ($re && $order_re){
            $tab->commit();
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog('order_netcar', $id, 'cancelNetCarOrder', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            $this->success("订单取消成功!", U('NetCar/Index'));
            $this->infoSubCancel($orderCode['ordercode']);

        } else {
            $tab->rollback();
            $this->error("订单取消失败");
        }

    }


    //查询空闲和空驶车辆
    public function queryCar(){
        $tab = M();
        $sql = "SELECT id,VehicleNo FROM car_net WHERE (CarState = %d) AND (BizStatus = %d)";
        $arr = [1,0];
        $list = $tab->query($sql,$arr);
        return $list;

    }

	 //订单匹配成功信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('orderMatch',$id);//公司经营许可信息报送
    }

	 //订单取消信息报送
    function infoSubCancel($id){
        $sub = new SubmittedController();
        $sub->controlSub('orderCancel',$id);//公司经营许可信息报送
    }



}