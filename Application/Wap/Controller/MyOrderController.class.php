<?php
namespace Wap\Controller;


use Common\Common\BaseHomeController;




class MyOrderController extends BaseHomeController
{

    public function index()
    {
        $this->display('MyOrder');
    }


}