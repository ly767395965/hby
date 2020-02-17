<?php
namespace Wap\Controller;


use Common\Common\BaseHomeController;




class SureOrderController extends BaseHomeController
{

    public function index()
    {
        $userid = $_GET['userid'];
        $active = coupon_active(2,$userid);
        if ($active['activity_num'] != 0){
            $active = json_encode($active['activity']);
        }else{
            $active = 0;
        }
        $this->assign('active',$active);   //具体活动及优惠方式
        $this->display('SureOrder');
    }



}