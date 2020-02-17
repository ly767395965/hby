<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Admin\Controller\UserController;

class CarPositionController extends BaseController {

    //汽车在线接口地址
    static $car_Interface  = 'http://in.gpsoo.net/1/account/monitor?access_type=inner&account=%E8%B4%B5%E9%98%B3%E5%8D%8E%E9%82%A6&map_type=BAIDU&target=%E8%B4%B5%E9%98%B3%E5%8D%8E%E9%82%A6&access_token=20007335774210149076486132c4e281cec6bef0db0e769033cd1761440000010016010&time=1490765117&sign=47fe1b4688f46bdb117016e837c2bce1&n=D714B6E9C26538488A1AEBBECBCBA602&appver=1.9.8&os=android&access_type=inner&lang=zh-CN&source=app1.2&http_seq=35&apptype=goocar&lat=26.556673&lng=106.716043&vercode=99&appid=1001';

    public function index(){
        $this->display();
    }

    public function car_query(){
        $sql = "SELECT * FROM car_carinfo WHERE isdel = 0";
        if (isset($_GET['key'])){
            $key = I('get.key');
            $key_query = I('get.key_query');
            switch ($key){
                case '0':
                    switch ($key_query){
                        case '1':
                            $sql .= " AND usestatus = 1";
                            break;
                        case '0':
                            $sql .= " AND (usestatus = 0 OR usestatus = 2)";
                            break;
                        case '3':

                            break;
                        default:
                            $res['code'] = 0;
                            $res['msg'] = '参数出错';
                    }
                    $sql .= " ";
                    break;
                case '1':
                    $member_sql = "SELECT c.carid AS car_id FROM `order` a LEFT JOIN `work_member` b ON b.id=a.uid LEFT JOIN order_car c ON a.id=c.orderid AND c.is_del=0 
WHERE  b.phone LIKE '%%%s%%' AND a.order_state > 2 AND a.order_state < 5 AND b.usertype = 0 
UNION SELECT a.car_id FROM `order` a LEFT JOIN `work_member` b ON b.id=a.uid 
WHERE b.phone LIKE '%%%s%%' AND a.order_state > 2 AND a.order_state < 5";
                    $member_car = M()->query($member_sql,[$key_query,$key_query]);
                    $member_str = '';
                    foreach ($member_car as $k=>$v){
                        if ($member_str == ''){
                            $member_str .= $v['car_id'];
                        }else{
                            $member_str .= ','.$v['car_id'];
                        }
                    }

                    if ($member_str){
                        $sql .= " AND ( id IN ({$member_str}))";
                    }else{
                        $res['code'] = 0;
                        $res['msg'] = '该客户没有在租车辆';
                    }
                    break;
                case '2':
                    $sql .= " AND ( carno LIKE '%%{$key_query}%%' OR imei LIKE '%%{$key_query}%%')";
                    break;
                default:
                    $res['code'] = 0;
                    $res['msg'] = '参数出错';
            }
        }

        if ($res['code'] === 0){
            exit(json_encode($res));
        }
        $carinfo = M()->query($sql);
        if ($carinfo){
            $res['code'] = 1;
            $allcar = $this->car_position();
            if ($allcar['success'] == true){
                foreach ($carinfo as $key=> &$val){
                    foreach ($allcar['data'] as $k=>$v){
                        if ($val['imei'] == 0){
                            unset($carinfo[$key]);
                            break;
                        }else{
                            if ($val['imei'] == $v['imei']){
                                $val['lng'] = $v['lng'];           //获取经纬度
                                $val['lat'] = $v['lat'];
                                $val['gps_time'] = date("Y-m-d H:i:s",$v['gps_time']);
                                switch ($v['acc']){               //是否启动状态
                                    case 0:
                                        $val['acc'] = '停止';
                                        break;
                                    case 1:
                                        $val['acc'] = '启动';
                                        break;
                                    default:
                                        if ($v['lng'] == 0){
                                            $val['acc'] = '无线';
                                        }else{
                                            $val['acc'] = '异常';
                                        }
                                }
                                break;
                            }else{
                                $val['acc'] = '错误';
                                $val['lng'] = 0;           //获取经纬度
                                $val['lat'] = 0;
                            }
                        }
                    }
                }
                if ($carinfo){
                    $res['content'] = $carinfo;
                }else {
                    $res['content'] = '';
                    $res['msg'] = '已查询到车辆,但车辆无设备号,无法定位';
                }


            }else{
                $res['code'] = 0;
                $res['msg'] = '车辆位置接口出错!请联系管理员';
            }

        }else{
            $res['code'] = 0;
            $res['msg'] = '未查询到符合条件的车辆';
        }


        $this->ajaxReturn($res);
    }

    public function car_position(){
        $url = self::$car_Interface;
        $jsonStr = json_encode(array());
        $list = $this->http_post_json($url, $jsonStr);
        $array=json_decode($list[1],true) ;

//        foreach ($array1['data'] as $key => $value){
//            if ($value['imei'] == '868120144870752'){
//                $arr = $value;
//            }
//        }
        return $array;

    }


    /**
     * PHP发送Json对象数据
     *
     * @param $url 请求url
     * @param $jsonStr 发送的json字符串
     * @return array
     */
    function http_post_json($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return array($httpCode, $response);
    }
}