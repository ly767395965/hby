<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class NewsController
 * @package Home\Controller
 * 订单控制器
 */

class IndentController extends BaseHomeController{
    protected static $table = 'car_carmodel';
    public function index(){
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone,'DECODE','123456');
        $user_id = authcode(cookie('uid'), 'DECODE', '123456');
        if ($tel != null){
            $id = I('get.id');
            $start = I('post.start');
            $stime = I('post.stime');
            $end = I('post.end');
            $etime = I('post.etime');
            $days = I('post.days');
            $jiage = I('post.jiage');
            $datas = I('post.datas');
            $data = array(
                'start' =>$start,
                'stime' =>$stime,
                'end' =>$end,
                'etime' =>$etime,
                'days' =>$days,
                'datas' =>$datas,
            );
            $carmodel = M();
            $sql = " SELECT a.id,a.frontimg,a.carmodelname,a.bearboxtype,a.shortdayprice,a.weekdayprice,a.monthdayPrice,a.carmodeltype,a.agestyle,a.sitecount,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND (a.id=%d)";
            $arr = [$id];
            //生成订单号
            cookie('order',null);
            if (cookie('order') == null) {
                cookie('order',orderCode());
//                查询订单号是否预存在
                $order = cookie('order');
                $table = M();
                $sql_order = "SELECT COUNT(ID) FROM `order` WHERE order_code = '%s'";
                $ary = [$order];
                $orderid = $table->query($sql_order,$ary);
//                如果订单号已存在则返回重新生成
                if ($orderid[0]['count(id)'] > 0){
                    return false;
                }
            } else {
                cookie('order',null);
            }
            $list = $carmodel->query($sql,$arr);
            //判断图片文件是否存在
            if (file_exists("./Public".$list[0]['frontimg'])){
//            检测图片文件是否可读
                if (is_readable("./Public".$list[0]['frontimg'])){
                    $frontimg = $list[0]['frontimg'];
                } else {
                    $frontimg = '';
                }

            } else {
                $frontimg = '';
            }

            $list[0]['frontimg'] = $frontimg;

            //查询用户的优惠券信息
            $tab = M();
            $sql = "SELECT b.id,a.coupon_name,b.use_limit,b.use_condition,b.money,b.coupon_type,b.discount FROM 
coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid  LEFT JOIN work_member c ON b.uid=c.id 
WHERE (c.phone='%s') AND (b.use_type=0) AND (current_timestamp() < b.termofvalidity || b.termofvalidity ='0000-00-00 00:00:00')";
            $where = [$tel];
            $couponInfo = $tab->query($sql,$where);
            $time =  Date('Ymd',time());
            $cou = $this->vacationJudge($time);
            if (($cou == 0 || $cou == 1)){
                $this->assign('couponInfo',$couponInfo);
            }

            //可参与优惠活动查询
            $active = coupon_active(2,$user_id);
            if ($active['activity_num'] != 0){
                $act['num'] = $active['activity_num'];
                foreach ($active['activity'] as $key => $val){
                    $act['act'][$key]['id'] = $val['id'];
                    $act['act'][$key]['name'] = $val['name'];
                }
                $this->assign('act',$act);  //活动id及活动名

                $active = json_encode($active['activity']);

            }else{
                $active = 0;
            }
            $this->assign('active',$active);   //具体活动及优惠方式

            $this->assign('cou',$cou[$time]);
            $this->assign('list',$list);
            $this->assign('data',$data);
            $this->assign('jiage',$jiage);
            //站点信息
            $sitetitle = new IndexController();
            $sitetitle = $sitetitle->webInfo();
            $this->assign('sys_title',$sitetitle[0]['title']);
            $this->display();
        } else {
            cookie('url',$_SERVER['HTTP_REFERER']);
            echo "<script>alert('请登录');location.href='/home/Login';</script>";
        }

    }

    //特惠车辆
    public function carinfo() {
        $phone = cookie('tel');
        //账户解密
        $tel = authcode($phone,'DECODE','123456');
        if ($tel != null){
            //判断用户类型
            $res = $this->sql($tel);
            if ($res == 0){
                $id = I('get.id');
                $start = I('post.start');
                $stime = I('post.stime');
                $end = I('post.end');
                $etime = I('post.etime');
                $days = I('post.days');
                $jiage = I('post.jiage');
                $datas = I('post.datas');
                $data = array(
                    'start' =>$start,
                    'stime' =>$stime,
                    'end' =>$end,
                    'etime' =>$etime,
                    'days' =>$days,
                    'datas' =>$datas,
                );
                $carinfo = M();
                $sql = "SELECT a.id,a.goodprice,b.frontimg,b.carmodeltype,b.agestyle,b.carmodelname,b.bearboxtype,b.sitecount,b.shortdayprice,c.brand FROM car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel = b.id LEFT JOIN car_barand c ON a.brand = c.id WHERE (a.id = %d)";
                $arr = [$id];
                //生成订单号
                cookie('order',null);
                if (cookie('order') == null) {
                    cookie('order',orderCode());
//                查询订单号是否预存在
                    $order = cookie('order');
                    $table = M();
                    $sql_order = "SELECT COUNT(ID) FROM `order` WHERE order_code = '%s'";
                    $ary = [$order];
                    $orderid = $table->query($sql_order,$ary);
//                如果订单号已存在则返回重新生成
                    if ($orderid[0]['count(id)'] > 0){
                        return false;
                    }
                } else {
                    cookie('order',null);
                }
                $list = $carinfo->query($sql,$arr);
                //判断图片文件是否存在
                if (file_exists("./Public".$list[0]['frontimg'])){
//            检测图片文件是否可读
                    if (is_readable("./Public".$list[0]['frontimg'])){
                        $frontimg = $list[0]['frontimg'];
                    } else {
                        $frontimg = '';
                    }

                } else {
                    $frontimg = '';
                }
                $list[0]['frontimg'] = $frontimg;
                $this->assign('list',$list);
                $this->assign('data',$data);
                $this->assign('jiage',$jiage);
                //站点信息
                $sitetitle = new IndexController();
                $sitetitle = $sitetitle->webInfo();
                $this->assign('sys_title',$sitetitle[0]['title']);
                $this->display();
            } else {
                echo '<script>alert("该优惠活动仅对线上普通会员有效!");history.back(-1);</script>';
            }

        } else {
            cookie('url',$_SERVER['HTTP_REFERER']);
            echo "<script>alert('请登录');location.href='/home/Login';</script>";
        }
    }

    //判断用户类型
    public function sql($tel){
        $m = M();
        $sql = "SELECT usertype FROM work_member WHERE (phone = '%s')";
        $arr = [$tel];
        $list = $m->query($sql,$arr);

        return $list[0]['usertype'];

    }

    public function vacationJudge($data){
//        $url = "http://www.easybots.cn/api/holiday.php?d=$data";
        $url = "http://tool.bitefu.net/jiari/?d=$data";
        $jsonStr = json_encode(array());
        $list = $this->http_post_json($url, $jsonStr);
        $array1=json_decode($list[1],true) ;
        return $array1;
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

    public function coupon($id){
        $tab = M();
        $sql = "SELECT a.id as aid,b.id as bid,a.status,b.issue_state,b.number,b.sum,b.andtime,b.endtime,b.limit,b.time_limit,b.termofvalidity,b.termofvaliditytian,b.type,b.money,b.discount,b.min_consume 
FROM coupon_activity a LEFT JOIN coupon_bollar b ON a.coupon_id=b.id WHERE a.act_type=0 AND b.issue_state=1 AND b.state=0 AND a.id=%d";
        $arr = [$id];
        $info = $tab->query($sql,$arr);
        return $info;
    }

}