<?php
namespace App\Controller;
use Org\Util\Date;
use Think\Controller;


/**
 * Class BackrunController
 * @package App\Controller
 * 任务计划管理控制器
 */
class BackrunController extends Controller {

    public function index()
    {
        $m = M();
        $sql = "SELECT id,car_id,carmodelid,order_type,order_state,order_date,TIMESTAMPDIFF(MINUTE,order_date,NOW()) AS date FROM `order` WHERE (TIMESTAMPDIFF(day,order_date,NOW())<2) AND (TIMESTAMPDIFF(MINUTE,order_date,NOW())>30) AND (order_type!=1) AND (order_state=0)";
        $list = $m->query($sql);
        $ids = '';//装执行成功的id
        $ide = '';//装执行失败的id
        $file = Date('Ymd', time());
        $run = $this->log();
        echo $run;
//        $list = [];
//        $list = [array('id'=>3),array('id'=>5),array('id'=>6),array('id'=>8)];
        $i = 0;//记录每次执行的数据量
        foreach ($list as $temp) {
//            echo $temp['id'];

            if (empty($temp)) {
                exit();
            } else {
                $i = $i+1;
                if ($temp['car_id'] == 0) {
                    $sql = "UPDATE `order` a,order_cost b SET a.order_state = 10,b.is_del = 1 WHERE (a.id = '" . $temp['id'] . "') AND (b.order_id = '" . $temp['id'] . "')";

                } else {
                    $sql = "UPDATE `order` a,order_cost b,car_carinfo c SET a.order_state = 10,b.is_del = 1,c.usestatus = 0 WHERE (a.id = '" . $temp['id'] . "') AND (b.order_id = '" . $temp['id'] . "') AND (c.id = '" . $temp['car_id'] . "')";

                }
                $res = $m->execute($sql);

                if ($res >0) {
                    cancelCoupon($temp['id']);  //修改订单获取的优惠信息
                    if ($ids == '') {
                        $ids = $temp['id'];
                    } else {
                        $ids .= ',' . $temp['id'];
                    }
                } else {
                    if ($ide == '') {
                        $ide = $temp['id'];
                    } else {
                        $ide .= ',' . $temp['id'];
                    }
                }
            }

        }

        if (!empty($ids)) {
            $myfile = fopen('logs/' . $file . '.log', "a+") or die("Unable to open file!");
            $txt = '['.$ids . '|'.'OK'.'|' . Date('Y-m-d H:i:s', time()).'|'.'number:'.$i.'|'.'Run:'.$run.']'."\r\n";
            fwrite($myfile, $txt);
            fclose($myfile);
            var_dump($ids);
        } else {
            if (!empty($ide)) {
                $myfile = fopen('logs/' . $file . '.log', "a+") or die("Unable to open file!");
                $txt = '['.$ide . '|'.'ERROR' .'|'. Date('Y-m-d H:i:s', time()).'|'.'number:'.$i.'|'.'Run:'.$run.']'."\r\n";
                fwrite($myfile, $txt);
                fclose($myfile);
                var_dump($ide);
            } else {
                $myfile = fopen('logs/' . $file . '.log', "a+") or die("Unable to open file!");
                $txt = '['.'EMPTY' .'|'. Date('Y-m-d H:i:s', time()).'|'.'number:'.$i.'|'.'Run:'.$run.']'."\r\n";
                fwrite($myfile, $txt);
                fclose($myfile);
                var_dump($ide);
            }
        }

    }
    //记录日志执行的次数
    public function log(){
        $handle = fopen('./logs/11.log', 'r');
        $i = fgets($handle, 1024);
        fclose($handle);
        $i = $i+1;
        $myfile = fopen('logs/' . "11" . '.log', "w") or die("Unable to open file!");
        $txt = $i;
        fwrite($myfile, $txt);
        fclose($myfile);
        return $i;

    }

    //提示业务
    public function prompt(){
        $m = M();
        $sql = "SELECT COUNT(id) FROM `order` WHERE order_state < 2";
        $list = $m->query($sql);
        if ($list[0]['count(id)'] >0 ){
            echo 'ok';
        } else {
            echo 'no';
        }
    }

}