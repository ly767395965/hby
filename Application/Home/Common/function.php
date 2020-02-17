<?php

//验证码判断
function check_code($code, $id = "")
{
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 在线交易订单支付处理函数
 * 函数功能：根据支付接口传回的数据判断该订单是否已经支付成功；
 * 返回值：如果订单已经成功支付，返回true，否则返回false；
 * @param $orderCode
 * @return bool
 */
//function checkOrderStatus($orderCode)
//{
//    $Ord=M('order');
//    $ordstatus=$Ord->where('order_code='.$orderCode)->getField('order_state');
//    if($ordstatus==1){
//        return true;
//    }else{
//        return false;
//    }
//}

/**
 * 处理订单函数
 * 更新订单状态，写入订单支付后返回的数据
 * @param $parameter
 */
//function orderHandle($parameter)
//{
//    $Ord=M('order');
//    $ordid=$parameter['out_trade_no'];//支付宝返回的订单号
//    //查询应收金额
//    $sql = "SELECT price_rec FROM `order` WHERE (order_code = '%s')";
//    $arr = [$ordid];
//    $list = $Ord->query($sql,$arr);
//    $data['collections_rec'] = $list[0]['price_rec'];//实收金额
//    $data['order_state'] = 1;
//    $data['pay_way'] = 1;
//    $Ord->where('order_code='.$ordid)->save($data);
//    //计算消费额所得积分
//    //查询用户消费金额
//    $consume = "SELECT collections_rec,uid FROM `order` WHERE (order_code = '%s')";
//    $ary = [$ordid];
//    $lists = $Ord->query($consume,$ary);
//    //查询用户原有积分
//    $user = M('work_member');
//    $score = "SELECT score FROM work_member WHERE (id = %d)";
//    $id = [$lists[0]['uid']];
//    $uid = $user->query($score,$id);
//    //计算积分
//    $integral['score'] = $lists[0]['collections_rec'] * 100 + $uid[0]['score'];
//    $user->where(array('id='.$lists[0]['uid']))->save($integral);
//
//
//}


/**
 * 在线充值交易订单支付处理函数
 * 函数功能：根据支付接口传回的数据判断该订单是否已经支付成功；
 * 返回值：如果订单已经成功支付，返回true，否则返回false；
 * @param $orderCode
 * @return bool
 */
function checkRechargeStatus($orderCode)
{
    $Ord=M('recharge');
    $ordstatus=$Ord->where('credit_order='.'".$orderCode."')->getField('state');
    if($ordstatus==1){
        return true;
    }else{
        return false;
    }
}

/**
 * 处理在线充值订单函数
 * 更新订单状态，写入订单支付后返回的数据
 * @param $parameter
 */
function rechargeHandle($parameter)
{
    $Ord=M();
    $ordid=$parameter['out_trade_no'];//支付宝返回的订单号
    $sum = $parameter['total_fee'];

    //修改订单状态
    $sql = "UPDATE recharge SET state = 1 WHERE (credit_order = '%s')";
    $arr  = [$ordid];
    $Ord->execute($sql,$arr);

    //查询充值金额 和用户原有余额
    $recharge = "SELECT uid FROM recharge WHERE (credit_order = '%s')";
    $ary = [$ordid];
    $amount = $Ord->query($recharge,$ary);

    //将充值金额写入用户表
    $user = M();
//    $sum = $amount[0]['balance'] + $sum;
    $sqls = "UPDATE work_member SET balance = balance+'".$sum."' WHERE (id = '%d')";
    $arrs  = [$amount[0]['uid']];
    $user->execute($sqls,$arrs);



}