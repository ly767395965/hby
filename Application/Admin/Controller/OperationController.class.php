<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 * 网约车运营统计控制器
 */
class OperationController extends BaseController{
    public function index() {
        if (isset($_GET['group'])){
           $data['group'] = I('get.group');
           $data['start'] = I('get.start');
           $data['stop'] = I('get.stop');
        }else{
            $data['group'] = 0;
            $data['start'] = date('Y-m-d H:i:s',time()-2592000);
            $data['stop'] = date('Y-m-d H:i:s',time());
        }
        $list = $this->count($data);
        $this->assign('list',$list);
        $this->assign('data',$data);
        $this->display();
    }

    //数据统计方法
    public function count($data = ''){
        if ($data['group'] == 0){
            $data['group_info'][0]['start'] = '00:00:00';
            $data['group_info'][0]['stop'] = '08:30:00';
            $data['group_info'][1]['start'] = '08:30:00';
            $data['group_info'][1]['stop'] = '12:30:00';
            $data['group_info'][2]['start'] = '12:30:00';
            $data['group_info'][2]['stop'] = '16:30:00';
            $data['group_info'][3]['start'] = '16:30:00';
            $data['group_info'][3]['stop'] = '00:00:00';
        }else{
            $sql = "SELECT region_name,address FROM address_num WHERE `leave` =2";
            $address = M()->query($sql);
            foreach ($address as $key => $val){
                $data['group_info'][$key]['address'] = $val['address'];
                $data['group_info'][$key]['region_name'] = $val['region_name'];
            };
        }



        $driver_sql = "SELECT COUNT(id) as driver_num FROM car_driverinfo WHERE state != 2 AND isdel=0 AND Flag != 3 ";
        $car_sql = "SELECT COUNT(id) as car_num FROM car_net WHERE CarState = 1 AND Flag != 3";
        $order_sql = "SELECT a.OrderState,UNIX_TIMESTAMP(a.OrderTime)as OrderTime,a.Address,UNIX_TIMESTAMP(b.AboardTime)as AboardTime,b.AboardPolitics,UNIX_TIMESTAMP(b.DebusTime)as DebusTime,b.ActualMileage,b.ActualPay FROM order_netcar a LEFT JOIN order_settlement b ON a.OrderCode=b.OrderCode WHERE (a.OrderTime BETWEEN '%s' AND '%s')";
//        $settlement_sql = "SELECT AboardTime,AboardPolitics,ActualMileage,ActualPay FROM order_settlement";
        $peccancy_sql = "SELECT UNIX_TIMESTAMP(PeccancyDate)as PeccancyDate,PeccancyAddress FROM driver_peccancy WHERE PeccancyType=0 AND (PeccancyDate BETWEEN '%s' AND '%s')";
        $evaluate_sql = "SELECT a.Type,a.ServerFeel,UNIX_TIMESTAMP(a.EvaluateTime)as EvaluateTime,b.Address FROM evaluate a LEFT JOIN order_netcar b ON a.OrderCode=b.OrderCode WHERE (a.EvaluateTime BETWEEN '%s' AND '%s')";
        $driver_num =  (M()->query($driver_sql))[0]['driver_num'];  //驾驶员统计
        $car_num =  (M()->query($car_sql))[0]['car_num'];//车辆统计
        $order_info =  M()->query($order_sql,[$data['start'],$data['stop']]);//订单信息
        $peccancy_info = M()->query($peccancy_sql,[$data['start'],$data['stop']]);//违章信息
        $evaluate_info = M()->query($evaluate_sql,[$data['start'],$data['stop']]);//投诉及评价信息

        $ary_info = $this->countAry();  //获取记录统计信息的数组(分组)
        $all_info = $this->countAry();  //获取记录统计信息的数组(总计)
        $all_info['driver_num'] = $driver_num;
        $all_info['car_num'] = $car_num;

        foreach ($data['group_info'] as $k=>$v){
            if ($data['group'] == 0){
                $list[$k+1]['group_title'] = $v['start'].'至'.$v['stop'];
            }else{
                $list[$k+1]['group_title'] = $v['region_name'];
            }

            foreach ($order_info as $key=> &$val){        //对订单信息进行统计

                if ($data['group'] == 0){
                    if (!$this->timeGroup($v,$val['ordertime'])){
                        continue;
                    };
                }else{
                    if ($v['address'] != $val['address']){
                        continue;
                    };
                }
                $ary_info['order_num']++;
                if ($val['orderstate'] == 5){
                    $ary_info['end_num']++;
                    $ary_info['carry_mile'] += $val['actualmileage'];
                    $ary_info['carry_time'] += ($val['debustime'] - $val['aboardtime'])/60;
                    $ary_info['profit'] += $val['actualpay'];
                }

                unset($val);    //清楚已计算过的值
            }

            foreach ($peccancy_info as $key => &$val){//对违章信息进行统计
                if ($data['group'] == 0){
                    if (!$this->timeGroup($v,$val['peccancydate'])){
                        continue;
                    };
                }else{
                    if ($v['address'] != $val['peccancyaddress']){
                        continue;
                    };
                }
                $ary_info['peccancy_num']++;
                unset($val);    //清楚已计算过的值
            }

            foreach ($evaluate_info as $key => &$val){//投诉及评价信息进行统计
                if ($data['group'] == 0){
                    if (!$this->timeGroup($v,$val['evaluatetime'])){
                        continue;
                    };
                }else{
                    if ($v['address'] != $val['address']){
                        continue;
                    };
                }


                if ($val['type'] == 1){
                    $ary_info['complaint_num']++;
                }else{
                    $ary_info['server_num']++;
                    $ary_info['server_all'] += floatval($val['serverfeel']);
                }

                unset($val);    //清楚已计算过的值
            }

            $all_info['order_num'] += $ary_info['order_num'];
            $all_info['end_num'] += $ary_info['end_num'];
            $all_info['carry_time'] += $ary_info['carry_time'];
            $all_info['carry_mile'] += $ary_info['carry_mile'];
            $all_info['profit'] += $ary_info['profit'];
            $all_info['peccancy_num'] += $ary_info['peccancy_num'];
            $all_info['complaint_num'] += $ary_info['complaint_num'];
            $all_info['server_all'] += $ary_info['server_all'];

            if ($ary_info['end_num'] == 0){
                $ary_info['order_time'] = 0;
                $ary_info['order_mile'] = 0;
            }else{
                $ary_info['order_time'] = round($ary_info['carry_time']/$ary_info['end_num'],2);
                $ary_info['order_mile'] = round($ary_info['carry_mile']/$ary_info['end_num'],2);
            }
            if ($ary_info['server_num'] == 0){
                $ary_info['server_quality'] = 0;
            }else{
                $ary_info['server_quality'] = round($ary_info['server_all']/$ary_info['server_num'],2);
            }


            $list[$k+1]['content'] = $ary_info;
            $ary_info = $this->countAry();
        }

        if ($all_info['end_num'] == 0){
            $all_info['order_time'] = 0;
            $all_info['order_mile'] = 0;
        }else{
            $all_info['order_time'] = round($all_info['carry_time']/$all_info['end_num'],2);
            $all_info['order_mile'] = round($all_info['carry_mile']/$all_info['end_num'],2);
        }
        if ($all_info['server_num'] == 0){
            $all_info['server_quality'] = 0;
        }else{
            $all_info['server_quality'] = round($all_info['server_all']/$all_info['server_num'],2);
        }
        $list[0]['group_title'] = '总计';
        $list[0]['content'] = $all_info;
        ksort($list);
        return $list;

    }

    //时间区间判断
    public function timeGroup($time,$list){
        $list = date('H:i:s', $list);

        if ($list >= $time['start'] && $list < $time['stop']){
            return true;
        }else{
            return false;
        }
    }

    //行政区号判断
    public function addressGroup($address,$list){
        if ($address){

        }
    }

    //统计数组初始化
    public function countAry(){
        $all_info['driver_num'] = '/';//订单数量
        $all_info['car_num'] = '/';//订单数量
        $all_info['order_num'] = 0;//订单数量
        $all_info['end_num'] = 0;//完结订单数量
        $all_info['order_time'] = 0;//订单平均耗时
        $all_info['order_mile'] = 0;//计算平均里程
        $all_info['carry_time'] = 0;//载客时间
        $all_info['carry_mile'] = 0;//载客里程
        $all_info['profit'] = 0;//收益金额
        $all_info['peccancy_num'] = 0;//违章次数
        $all_info['complaint_num'] = 0;//投诉次数
        $all_info['server_quality'] = 0;//服务质量(平均分)
        $all_info['server_num'] = 0;//评价次数
        $all_info['server_all'] = 0;//服务总分
        return $all_info;
    }

    public function test(){
        echo round(2,2);
    }

    function fun($a,$b){
        echo "454";
        echo $a+$b;
    }
}