<?php

namespace Home\Controller;

use Common\Common\BaseHomeController;
/**
 * Class NewsController
 * @package Home\Controller
 * 任务执行管理控制器
 */

class TaskController extends BaseHomeController
{
    public function order(){
        $id = I('get.id');
        $token = I('get.token');
        $ymd = Date('ymd',time());
        $d = Date('d',time());
        if ($token == $ymd*$d){
            $order = M()->query('SELECT OrderCode FROM order_netcar WHERE id=%d',[$id]);
            $ordercode = $order[0]['ordercode'];
            $new_code = Date('ymdHis',time()).'8564';
            $sql = "UPDATE order_netcar a,order_settlement b SET a.OrderCode='%s',a.OrderState=4,b.OrderCode='%s' WHERE a.OrderCode='%s' AND b.OrderCode='%s'";
            $res = M()->execute($sql,[$new_code,$new_code,$ordercode,$ordercode]);
            echo  $res;
        }
    }
//    public function Index(){
//        echo '任务执行';
//        $m = M();
//        while(true){
//            $sql = "";
//            $list = $m->query($sql);
//            $datalist = arrary();
//            while($res = mysql_ftech_assoc($list)){
//                $datalist = $res ;
//            }
//
//            if(empty($datalist )){
//                break;
//            } else {
//                foreach(($datalist as $key => $value){
//                    执行业务逻辑
//	if（）{
//                        成功
//	} else {
//                        失败
//	}
//
//	sleep(间隔时间);
//}
//            }
//
//
//        }
//    }
}
