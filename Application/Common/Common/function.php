<?php
/**
+----------------------------------------------------------
 * 生成随机字符串
+----------------------------------------------------------
 * @param int       $length  要生成的随机字符串长度
 * @param string    $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母;-1，数字+大小写字母+特殊字符
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
/**
+----------------------------------------------------------
 * 生成随机字符串
+----------------------------------------------------------
 * @param int       $length  要生成的随机字符串长度
 * @param string    $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母;-1，数字+大小写字母+特殊字符
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
//订单编号
function orderCode() {
    $orderRand = rand(0,999999);
    $orderRand = str_pad($orderRand,6,'0',STR_PAD_LEFT);

    $code = date("YmdHisms").$orderRand;
    return $code;
}


//收费订单编号
function orderCostOrderCode() {
    $orderRand = rand(0,999999);
    $orderRand = str_pad($orderRand,6,'0',STR_PAD_LEFT);

    $code = 'm'.date("YmdHisms").$orderRand;
    return $code;
}

/**
 * $string     加密或解密字符串
 * $operation  字符串值为DECODE时为解密，除此以外均视为加密方法
 * $key        加密密匙，注意加密和解密时，key值必须相同，否则不能解密
 * 加密调用      $this->authcode('加密字符串','encode','123456')
 * 解密调用      $this->authcode('解密字符串','DECODE','123456')
 **/

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;

    // 密匙
    $key = md5($key ? $key : $GLOBALS['discuz_auth_key']);

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length):
        substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
//解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :
        sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if($operation == 'DECODE') {
        // 验证数据有效性，请看未加密明文的格式
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
            substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}

/**对数组进行整体加解密
 *Array $ary 需要加解密的数组
 * param $operation 加解密操作判定
 * param $key 加解密密匙
**/
function aryAuthcode($ary,$operation = 'DECODE',$key){
    if (count($ary) > 0){
        foreach ($ary as $k=> &$v){
            $v = authcode($v,$operation,$key);
        }
        return $ary;
    }else{
        return false;
    }
}


function strSub($content,$num){
    $str = mb_substr($content,0,$num,'utf-8');
    return $str;
}

//筛选可参与的优惠活动
function coupon_active($act_type,$user_id=null){
    $act_type = explode(',',$act_type);
    $type_sql = '';
    foreach ($act_type as $key => $val){
        if ($type_sql){
            $type_sql .= " OR act_type = {$val}";
        }else{
            $type_sql = " AND (act_type = {$val}";
        }

    }

    $sql = "SELECT * FROM coupon_activity WHERE status=0 AND end_time > NOW() AND is_del=0".$type_sql.")";
    $activity = M()->query($sql);

    foreach ($activity as $key => &$val){
        if ($val['limit'] != -1 && $user_id != null){
            $sql = "SELECT COUNT(id) as `sum` FROM `order` WHERE uid = %d AND order_state > 0 AND order_state < 10 AND activity = %d";
            $act_order = M()->query($sql,[$user_id,$val['id']]);       //判定该用户参与该活动的次数,如果已经超过限定次数则推出本次循环
            if ($act_order[0]['sum'] >= $val['limit']){
                continue;
            }
        }
        $coupon_ary = explode(',',$val['coupon_id']);
        $val['coupon_num'] = count($coupon_ary);
        foreach ($coupon_ary as $key_two => $val_two){
            $sql = "SELECT * FROM coupon_bollar WHERE id = {$val_two} AND (`number` = -1 OR `sum` < `number`)";
            $coupun_bollar = M()->query($sql);

            $val['coupon'.$key_two] = $coupun_bollar[0];
        }

    }

    $reg['activity_num'] = count($activity);
    $reg['activity'] = $activity;
    return $reg;

}


//判断是否可参加某个优惠活动以及该活动能带来的优惠
function couponClass($activity,$order_date,$price,$pk_date,$re_date){
    foreach ($activity as $key => &$val){
        //判断该订单是否可参与该活动
        switch ($val['partake_type']){
            case 0:
                if ($pk_date < $val['start_time'] || $pk_date >= $val['end_time']){
                    continue;
                }
                break;
            case 1:
                if ($order_date < $val['start_time'] || $order_date >= $val['end_time']){
                    continue;
                }
                break;
            case 2:
                if (!(($order_date >= $val['start_time'] && $order_date < $val['end_time']) && ($pk_date >= $val['start_time'] && $pk_date < $val['end_time']))){
                    continue;
                }
                break;
            case 3:
                if (!(($order_date >= $val['start_time'] && $order_date < $val['end_time']) || ($pk_date >= $val['start_time'] && $pk_date < $val['end_time']))){
                    continue;
                }
                break;
        }

        $rent_time = timeDifference($pk_date,$re_date,$val['start_time'],$val['end_time']);

        for ($i=0; $i<$val['coupon_num']; $i++){
            $coupon = $val['coupon'.$i];

            $reg = counponPrice($coupon,$price,$rent_time);
            switch ($coupon['type']){
                case 0:
                    if ($reg){
                        if ($coupon_pass[0]['specific']){
                            if ($coupon_pass[0]['specific'] < $coupon['money']){
                                $coupon_pass[0]['specific'] = $coupon['money'];
                                $coupon_pass[0]['coupon_num'] = $i;
                            }
                        }else{
                            $coupon_pass[0]['specific'] = $coupon['money'];
                            $coupon_pass[0]['coupon_num'] = $i;
                        }
                    }
                    break;
                case 1:
                    if ($reg){
                        if ($coupon_pass[1]['specific']){
                            if ($coupon_pass[1]['specific'] < $coupon['discount']){
                                $coupon_pass[1]['specific'] = $coupon['discount'];
                                $coupon_pass[1]['coupon_num'] = $i;
                            }
                        }else{
                            $coupon_pass[1]['specific'] = $coupon['discount'];
                            $coupon_pass[1]['coupon_num'] = $i;
                        }
                    }
                    break;
                case 2:
                    if ($reg){
                        if ($coupon_pass[2]['specific']){
                            if ($coupon_pass[2]['specific'] < $coupon['money']){
                                $coupon_pass[2]['specific'] = $coupon['money'];
                                $coupon_pass[2]['coupon_num'] = $i;
                            }
                        }else{
                            $coupon_pass[2]['specific'] = $coupon['money'];
                            $coupon_pass[2]['coupon_num'] = $i;
                        }
                    }
                    break;
            }
        }

        $val['coupon_pass'] = $coupon_pass;
    }
    return $activity;
}

//发放优惠劵
function grantCoupon($member_id,$coupons){
    $reg = null;
    if ($coupons[0]){
        $reg = 0;
    }else if ($coupons[1]){
        if ($reg !== 0){
            $reg = 1;
        }
    }
    if ($reg !== null){
        $data['uid'] = $member_id;
        $data['bid'] = $coupons[$reg]['id'];
        $data['addtime'] = Date('Y-m-d H:i:s');
        $data['updatetime'] = $data['addtime'];
        $data['use_type'] = 2;
        $data['coupon_type'] = $reg;
        $data['money'] = $coupons[$reg]['money'];
        $data['discount'] = $coupons[$reg]['discount'];
        $data['use_limit'] = $coupons[$reg]['use_limit'];
        $data['use_condition'] = $coupons[$reg]['use_condition'];
        switch ($coupons[$reg]['time_limit']){
            case '0':
                $data['termofvalidity'] = $coupons[$reg]['termofvalidity'];
                break;
            case '1':
                $data['termofvalidity'] = Date('Y-m-d H:i:s',strtotime("+ ".$coupons[$reg]['termofvaliditytian']." day"));
                break;
            case '2':
                $data['termofvalidity'] = '0000-00-00 00:00:00';
                break;
        }
        $add = M('coupon_bollaruser')->add($data);
        if ($add){
            couponSum($data['bid'],0);
        }

    }
    $msg['reg'] = $reg;
    $msg['add'] = $add;
    return $msg;
}

//发放直接减免
function reliefCoupon($member_id,$coupons){
    if ($coupons){
        $data['uid'] = $member_id;
        $data['bid'] = $coupons['id'];
        $data['addtime'] = Date('Y-m-d H:i:s');
        $data['updatetime'] = $data['addtime'];
        $data['use_type'] = 1;
        $data['coupon_type'] = 2;
        $data['money'] = $coupons['money'];
        $data['discount'] = $coupons['discount'];
        $data['use_limit'] = $coupons['use_limit'];
        $data['use_condition'] = $coupons['use_condition'];
        switch ($coupons['time_limit']){
            case '0':
                $data['termofvalidity'] = $coupons['termofvalidity'];
                break;
            case '1':
                $data['termofvalidity'] = Date('Y-m-d H:i:s',strtotime("+ ".$coupons['termofvaliditytian']." day"));
                break;
            case '2':
                $data['termofvalidity'] = '0000-00-00 00:00:00';
                break;
        }
        $add = M('coupon_bollaruser')->add($data);
        if ($add){
            couponSum($data['bid'],0);
        }

    }
    return $add;
}

//优惠领取次数计算
function couponSum($coupon_id,$calculation){
    $sql = "SELECT `sum` FROM coupon_bollar WHERE id = {$coupon_id}";
    $sum = M()->query($sql);
    $sum = $sum[0]['sum'];
    if ($calculation == 1 || $calculation == 0){
        switch ($calculation){
            case 0:
                $sum += 1;
                break;
            case 1:
                $sum -= 1;
                if ($sum < 0){
                    $sum = 0;
                }
                break;
        }

        $sql = "UPDATE coupon_bollar SET `sum` = {$sum} WHERE id = {$coupon_id}";
        $res = M()->execute($sql);
    }else{
        $res = -1;
    }
    return $res;
}

//将客户得到的优惠变为可用状态
function couponUserState($order_id,$user_type){
    $sql = "SELECT id, grant_coupon FROM `order` WHERE id=%d";
    $order_info = M()->query($sql,[$order_id]);
    $order_info_ary = explode(',',$order_info[0]['grant_coupon']);
    foreach ($order_info_ary as $key => $val){
        $sql = "SELECT use_type FROM coupon_bollaruser WHERE id = %d AND use_type = 2 AND is_del = 0";
        $coupon_info = M()->query($sql,[$val]);
        if ($coupon_info){
            $sql = "UPDATE coupon_bollaruser SET use_type='%s' WHERE id = %d";
            $ary = [$user_type,$val];
            $res = M()->execute($sql,$ary);
        }else{
            $res = -1;
        }
    }

    return $res;
}

//当取消订单时查询订单享受的优惠,并改变用户优惠获取的记录以及优惠领取次数
function cancelCoupon($order_id){
    $m = M();
    $sql = "SELECT grant_coupon FROM `order` WHERE id = %d";
    $grant_coupond = $m->query($sql,[$order_id]);
    $grant_coupond_ary = explode(',',$grant_coupond[0]['grant_coupon']);
    foreach ($grant_coupond_ary as $key => $val){
        $sql = "SELECT b.id,b.sum FROM coupon_bollaruser a LEFT JOIN coupon_bollar b ON a.bid = b.id WHERE a.id = {$val}";
        $up_sql = "UPDATE coupon_bollaruser SET use_type=2,is_del=1 WHERE id = {$val}";
        $coupon = $m->query($sql);
        $m->execute($up_sql);
        if ($coupon){
            $sum = $coupon[0]['sum'] - 1;
            if ($sum < 0){
                $sum = 0;
            }
            $up_sql = "UPDATE coupon_bollar SET `sum`={$sum} WHERE id = {$coupon[0]['id']}";
            $m->execute($up_sql);
        }

    }
}

//根据优惠的限制条件,判断该订单是否可享受该优惠
function counponPrice ($coupon,$price,$rent_time){
    $reg = 0;
    switch ($coupon['condition_limit']){
        case '0':
            if ($price > $coupon['coupon_condition']){
                $reg = 1;
            }
            break;
        case '1':
            if ($rent_time > $coupon['coupon_condition']){
                $reg = 1;
            }
            break;
        case '2':
            $reg = 1;
            break;
    }

    return $reg;
}

//计算租车时长 (返回相差的天数)
function timeDifference($pk_date,$re_date,$act_start,$act_end){
    $pk_date = strtotime($pk_date);
    $re_date = strtotime($re_date);
    $act_start = strtotime($act_start);
    $act_end = strtotime($act_end);

    if ($pk_date < $act_start){
        if ($re_date >= $act_end){
            $reg = $act_end - $act_start;
        }else{
            $reg = $re_date - $act_start;
        }
    }else{
        if ($re_date >= $act_end){
            $reg = $act_end - $pk_date;
        }else{
            $reg = $re_date - $pk_date;
        }
    }
    return $reg/86400;
}






/**  优惠活动发放优惠劵 (已弃用)
 * $limit        限制条件数组,例:[3,4]
 * $judge        判断条件
 * $coupon_act   活动数组,满足条件后参加的活动,需和限制条件对应,例:[11,12]
 * $member_id    客户id
 * $on_off       开关(1,开,0关闭)
 **/
function coupon($limit,$judge,$coupon_act,$member_id,$on_off=1){
    if ($on_off == 1){
        $res = condition($limit,$judge);
        if ($res['code']){
            if (count($limit) != count($coupon_act)){
                $msg = '限制条件数组和对应活动数组长度要对应';
                return $msg;
            }
            $sql = "SELECT a.id as aid,b.id as bid,a.status,b.issue_state,b.number,b.sum,b.andtime,b.endtime,b.limit,b.time_limit,b.termofvalidity,b.termofvaliditytian,b.type,b.money,b.discount,b.min_consume FROM coupon_activity a LEFT JOIN coupon_bollar b ON a.coupon_id=b.id WHERE a.id=%d AND b.issue_state=1 AND b.state=0";
            $ary = array($coupon_act[$res['num']]);
            $info = M()->query($sql,$ary);

            if ($info[0]['status'] == 0 && $info[0]['issue_state'] == 1){
                $bollaruser = M('coupon_bollaruser');
                if ($info[0]['time_limit'] == 0){
                    $time = $info[0]['termofvalidity'];
                }else if ($info[0]['time_limit'] == 1){
                    $time = Date('Y-m-d H:i:s',strtotime("+ ".$info[0]['termofvaliditytian']." day"));
                } else {
                    $time ='0000-00-00 00:00:00';
                }
                $bollaruser_data = array();
                $bollaruser_data['uid'] = $member_id;
                $bollaruser_data['bid'] = $info[0]['bid'];
                $bollaruser_data['addtime'] = Date('Y-m-d H:i:s',time());
                $bollaruser_data['updatetime'] = Date('Y-m-d H:i:s',time());
                $bollaruser_data['coupon_type'] = $info[0]['type'];
                $bollaruser_data['money'] = $info[0]['money'];
                $bollaruser_data['discount'] = $info[0]['discount'];
                $bollaruser_data['min_consume'] = $info[0]['min_consume'];
                $bollaruser_data['time_limit'] = $info[0]['time_limit'];
                $bollaruser_data['termofvalidity'] = $time;
                //判断该优惠券是否为无限领取
                if ($info[0]['number'] != -1){
                    //非无限领取优惠券时，判断优惠券领取数量不能大于发行数量。
                    if ($info[0]['sum'] < $info[0]['number']){
                        $row = $bollaruser->add($bollaruser_data);
                    }
                } else {
                    $row = $bollaruser->add($bollaruser_data);
                }
                if ($row){
                    $updata = M();
                    $up = "UPDATE coupon_bollar SET `sum` = '".$info[0]['sum']."' +1 WHERE id=%d";
                    $ary = [$info[0]['bid']];
                    $cou_bollar = $updata->execute($up,$ary);
                    if ($cou_bollar){
                        $msg = 1;
                    }else{
                        $msg = 5;
                    }
                }else{
                    $msg = 4;
                }


            }else{
                $msg = 3;
            }
        }else{
            $msg = 0;
        }
    }else{
        $msg = 2;
    }

    return $msg;
}
//拆分条件,并判断
function condition($limit,$judge){
    $con_sum = count($limit);
    switch ($con_sum){
        case 0:
            $res['code'] = true;
            $res['num'] = 0;
            break;
        case 1:
            if ($judge >= $limit[0]){
                $res['num'] = 0;
                $res['code'] = true;
            }else{
                $res = false;
            }
            break;
        default:
            foreach ($limit as $key => $val){
                if ($key == 0){
                    if ($judge < $val){
                        $res['code'] = false;
                        break;
                    }
                }else{
                    if ($judge >= $limit[$key-1] && $judge < $limit[$key]){
                        $res['code'] = true;
                        $res['num'] = $key-1;
                        break;
                    }else{
                        $res['code'] = false;
                    }
                }
            }

            if (!$res['code']){
                if ($judge >= $limit[$con_sum-1]){
                    $res['code'] = true;
                    $res['num'] = $con_sum-1;
                }
            }

    }
    return $res;

}




