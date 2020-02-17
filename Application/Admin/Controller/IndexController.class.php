<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

class IndexController extends BaseController {
    public function index(){

        $this->display();

    }

    public function top() {
        $auserInfo = UserInfo();
        $name=$auserInfo['name'];
        $this->assign('name',$name);
        $this->display();

    }

    public function right() {

        $this->display();
        
    }
    public function left() {

        $this->display();

    }




}