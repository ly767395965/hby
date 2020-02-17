<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/26
 * Time: 9:52
 */

namespace Home\Controller;


use Common\Common\BaseHomeController;

class dbtestController extends BaseHomeController
{
    public function index(){
        $tab = M();
        $list = $this->sqlorder();
        for ($i=0;$i<count($list);$i++){
            //操作order表
            $sql = "INSERT INTO `order` (id,uid,car_number,drive_number,carmodelid,car_id,coupon_id,order_code,order_date,pk_date,re_date,u_price,cost_price,pre_price,price_rec,collections_rec,price_paided,check_cycle,be_driver,driver_price,drive_state,send_price,pk_way,deposit,get_deposit,dp_price,oil_price,tolls,wash_price,re_price,is_invoice,in_code,in_price,in_cost,in_dep,after_site,order_type,check_out,order_state,send_location,agent_id,remarks,is_del,agent_check_state) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')";

            if ($list[$i]['usertype'] == 1){
                if ($list[$i]['drive_state'] == 1){
                    $drive_number = 1;
                    $be_driver = $list[$i]['be_driver'];
                } else {
                    $be_driver = null;
                    $drive_number = 0;
                }

                if ($list[$i]['car_id'] > 0){
                    $car_number = 1;
                } else {
                    $car_number = 0;
                }
                $arr = [$list[$i]['id'],$list[$i]['uid'],$car_number,$drive_number,$list[$i]['carmodelid'],0,$list[$i]['coupon_id'],$list[$i]['order_code'],$list[$i]['order_date'],$list[$i]['pk_date'],$list[$i]['re_date'],$list[$i]['u_price'],$list[$i]['cost_price'],$list[$i]['pre_price'],$list[$i]['price_rec'],$list[$i]['collections_rec'],$list[$i]['price_paided'],$list[$i]['check_cycle'],$be_driver,$list[$i]['driver_price'],$list[$i]['drive_state'],$list[$i]['send_price'],$list[$i]['pk_way'],$list[$i]['deposit'],$list[$i]['get_deposit'],$list[$i]['dp_price'],$list[$i]['oil_price'],$list[$i]['tolls'],$list[$i]['wash_price'],$list[$i]['re_price'],$list[$i]['is_invoice'],$list[$i]['in_code'],$list[$i]['in_price'],$list[$i]['in_cost'],$list[$i]['in_dep'],$list[$i]['after_site'],$list[$i]['order_type'],$list[$i]['check_out'],$list[$i]['order_state'],$list[$i]['send_location'],$list[$i]['agent_id'],$list[$i]['remarks'],$list[$i]['is_del'],$list[$i]['agent_check_state']];
            } else {
                $be_driver = $list[$i]['be_driver'];
                $arr = [$list[$i]['id'],$list[$i]['uid'],0,0,$list[$i]['carmodelid'],$list[$i]['car_id'],$list[$i]['coupon_id'],$list[$i]['order_code'],$list[$i]['order_date'],$list[$i]['pk_date'],$list[$i]['re_date'],$list[$i]['u_price'],$list[$i]['cost_price'],$list[$i]['pre_price'],$list[$i]['price_rec'],$list[$i]['collections_rec'],$list[$i]['price_paided'],$list[$i]['check_cycle'],$be_driver,$list[$i]['driver_price'],$list[$i]['drive_state'],$list[$i]['send_price'],$list[$i]['pk_way'],$list[$i]['deposit'],$list[$i]['get_deposit'],$list[$i]['dp_price'],$list[$i]['oil_price'],$list[$i]['tolls'],$list[$i]['wash_price'],$list[$i]['re_price'],$list[$i]['is_invoice'],$list[$i]['in_code'],$list[$i]['in_price'],$list[$i]['in_cost'],$list[$i]['in_dep'],$list[$i]['after_site'],$list[$i]['order_type'],$list[$i]['check_out'],$list[$i]['order_state'],$list[$i]['send_location'],$list[$i]['agent_id'],$list[$i]['remarks'],$list[$i]['is_del'],$list[$i]['agent_check_state']];
            }



            $res = $tab->execute($sql,$arr);
            $data = $this->getId();
            if ($res > 0){
                //操作交易表 order_cost
                $cost = $this->sqlcost($list[$i]['id']);
                if (count($cost) > 0){
                    for ($j=0;$j<count($cost);$j++){
                        $costsql = "INSERT INTO order_cost (order_id,order_class,trade_code,charge_sum,charge_road,charge_time,pay_way,is_del) VALUES (%d,%d,'%s','%s',%d,'%s',%d,%d)";
                        $costarr = [$data[0]['id'],$cost[$j]['order_class'],$cost[$j]['trade_code'],$cost[$j]['charge_sum'],$cost[$j]['charge_road'],$cost[$j]['charge_time'],$cost[$j]['pay_way'],$cost[$j]['is_del'],];

                        $tab->execute($costsql,$costarr);
                    }
                }

                if ($list[$i]['usertype'] == 1){
                    //操作 order_car 订单用车表
                    if ($list[$i]['car_id'] > 0){

                        if ($list[$i]['order_state'] == 4){
                            $del = 3;
                        } elseif ($list[$i]['order_state'] == 10){
                            $del = 5;
                        }

                        $order_car = "INSERT INTO order_car (orderid,carid,driveid,begintime,endtime,illegalcost,remarks,is_del) VALUES (%d,%d,%d,'%s','%s','%s','%s',%d)";
                        $order_car_arr = [$data[0]['id'],$list[$i]['car_id'],$list[$i]['be_driver'],$list[$i]['pk_date'],$list[$i]['re_date'],$list[$i]['dp_price'],$list[$i]['remarks'],$del];

                        $tab->execute($order_car,$order_car_arr);
                    }

                    //操作代驾表 order_drive 逻辑删除标记;1为修改，2为还车，5为订单取消,6为取消代驾
                    if ($list[$i]['drive_state'] == 1){
                        if ($list[$i]['order_state'] == 4){
                            $isdel = 2;
                        }elseif ($list[$i]['order_state'] == 10){
                            $isdel = 5;
                        }
                        $order_drive = "INSERT INTO order_drive (orderid,driveid,distribution,is_del) VALUES (%d,%d,%d,%d)";
                        $drivearr = [$data[0]['id'],$list[$i]['be_driver'],1,$isdel];
                        $tab->execute($order_drive,$drivearr);
                    }

                }

            }

        }
        echo '执行完成';

    }

    //获取订单包
    public function sqlorder (){
        $tab = M();
        $sql = "SELECT b.usertype,a.* FROM `order1` a LEFT JOIN work_member b ON a.uid=b.id";
        $list = $tab->query($sql);
        return $list;
//        var_dump($list);
    }


    //获取订单交易明细
    public function sqlcost($orderid){
        $tab = M();
        $sql = "SELECT * FROM order_cost1  WHERE order_id=%d";
        $list = $tab->query($sql,[$orderid]);
        return $list;
    }

    //获取当前执行数据的id
    public function getId(){
        $tab = M();
        $sql = "SELECT id FROM `order` ORDER BY id DESC LIMIT 0,1";
        $id = $tab->query($sql);
        return $id;
    }


}