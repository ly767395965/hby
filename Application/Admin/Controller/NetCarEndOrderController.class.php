<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

class NetCarEndOrderController extends BaseController {

    public function index(){

        $sql = "SELECT a.id,b.username,b.phone as userphone,c.ActualPay,c.State,a.OrderCode,a.OrderTime,a.OrderPhone,c.AboardTime,c.DebusTime,c.AboardAddress,c.DebusAddress,a.OrderState,d.drivername,d.phone,e.VehicleNo FROM order_netcar a LEFT JOIN work_member b ON a.PassengerId=b.id LEFT JOIN order_settlement c ON a.OrderCode=c.OrderCode LEFT JOIN car_driverinfo d ON a.id=d.id LEFT JOIN car_net e ON d.CarId=e.id WHERE a.OrderState >= 5 AND a.OrderState != 10 ";
        $countSql = "SELECT COUNT(a.id) FROM order_netcar a LEFT JOIN work_member b ON a.PassengerId=b.id LEFT JOIN order_settlement c ON a.OrderCode=c.OrderCode LEFT JOIN car_driverinfo d ON a.id=d.id LEFT JOIN car_net e ON d.CarId=e.id WHERE a.OrderState >= 5 AND a.OrderState != 10 ";


        if (!empty($_GET)){
            $msg['selectId'] = $selectId = I('get.select');//查询分类
            $msg['select_time'] = $select_time = I('get.select_time');//时间段分类
            $msg['start'] = $startDate = $_GET['start'];//开始时间
            $msg['stop'] = $stopDate = $_GET['stop'];//结束时间
            $msg['key'] = $key = I('get.key');//查询的条件
        }else{
            $msg['selectId'] = $selectId = 0;//查询分类
            $msg['select_time'] = $select_time = 1;//时间段分类
            $msg['start'] = $startDate = date('Y-m-d H:i:s ',time() - 864000);//开始时间
            $msg['stop'] = $stopDate = date('Y-m-d H:i:s ',time());//结束时间
        }

        switch ($selectId) {
            case '1':
                $where = " AND (b.username LIKE '%%%s%%') ";
                break;
            case '2':
                $where = " AND (b.phone LIKE '%%%s%%')";
                break;
            case '3':
                $where = " AND (e.VehicleNo LIKE '%%%s%%')";
                break;
            case '4':
                $where = " AND (c.AboardAddress LIKE '%%%s%%')";
                break;
            case '5':
                $where = " AND (c.DebusAddress LIKE '%%%s%%')";
                break;
        }

        switch ($select_time){
            case '0':
                $time_where = " AND (a.OrderTime BETWEEN '%s' AND '%s') ";
                break;
            case '1':
                $time_where = " AND (c.AboardTime BETWEEN '%s' AND '%s') ";
                break;
            case '2':
                $time_where = " AND (c.DebusTime BETWEEN '%s' AND '%s') ";
                break;
        }
        if ($selectId){
            $ary = [$key];
        }else{
            $ary = [];
        }
        array_push($ary,$startDate,$stopDate);

        $sql .= $where.$time_where;
        $countSql .= $where.$time_where;
        $this->assign('msg',$msg);

        $sql = $sql . "ORDER BY a.id DESC";

        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true); //分页显示


        $this->display();

    }





}