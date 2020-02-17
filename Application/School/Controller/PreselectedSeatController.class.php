<?php
namespace School\Controller;

use Common\Common\BaseHomeController;



class PreselectedSeatController extends BaseHomeController
{

    public function index()
    {
        $m = M();
        $schoolid = I('get.schoolid');
        $sql = "SELECT a.id,a.route_name,b.cartid,c.orderid,COUNT(b.linenumber) as cartnumber FROM sc_route a LEFT JOIN (SELECT cartid,linenumber FROM sc_train GROUP BY cartid) b ON a.id = b.linenumber LEFT JOIN sc_carts c ON b.cartid=c.cartid LEFT JOIN `order` d ON c.orderid = d.id  WHERE a.school_id = '%d' AND  d.order_state = '%d' AND  b.cartid > '%d' GROUP BY b.linenumber";
        $list = $m->query($sql,[$schoolid,3,0]);
        $this->assign('list',$list);
        $this->display();
    }

    //车辆列表
    public function selection(){
        $linenumber = I('get.linenumber');
        $schoolid = I('get.schoolid');
        $m = M();
        $sql = " SELECT a.id,a.route_name,b.cartid,c.orderid,e.carno,f.sitecount FROM sc_route a LEFT JOIN sc_train b ON a.id = b.linenumber LEFT JOIN sc_carts c ON b.cartid=c.cartid LEFT JOIN `order` d ON c.orderid = d.id LEFT JOIN car_carinfo e ON b.cartid=e.id LEFT JOIN car_carmodel f ON e.carmodel=f.id  WHERE a.school_id = '%d' AND  d.order_state = '%d' AND  b.cartid > '%d' AND b.linenumber = '%d' GROUP BY b.cartid";
        $carts = $m->query($sql,[$schoolid,3,0,$linenumber]);
        $this->assign('carts',$carts);

        $this->display();
    }

    //查看车次
    public function train(){
        $car = I('get.car');
        $m = M();
        $sql = "SELECT a.cartid,a.starttime,b.carno,curtime() as nowdate FROM sc_train a LEFT JOIN car_carinfo b ON a.cartid=b.id WHERE a.cartid = '%d' ORDER BY a.starttime ";
        $trainInfo = $m->query($sql,[$car]);
        for ($i = 0;$i < count($trainInfo);$i++){
            if ($trainInfo[$i]['nowdate'] > $trainInfo[$i]['starttime']){
                $trainInfo[$i]['operable'] = 0;
            } else {
                $trainInfo[$i]['operable'] = 1;
            }
        }
        $this->assign('train',$trainInfo);
        $this->display();
    }

    //选座座位
    public function chooseSeat(){
        $carid = I('get.car');
        $carno = I('get.carno');
        $time = I('get.time');
        $carinfo = array('time'=>$time,'carno'=>$carno,'carid'=>$carid);
        $this->assign('carinfo',$carinfo);
        $this->display();
    }
}