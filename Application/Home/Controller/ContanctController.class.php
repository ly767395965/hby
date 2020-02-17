<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class ContanctController
 * @package Home\Controller
 * 联系我们控制器
 */

class ContanctController extends BaseHomeController{

    public function index(){
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
    	 $this->display();

    }



}