<?php
namespace Common\Common;
use Think\Controller;

class WapBaseController extends Controller {

    protected function _initialize()
    {
        echo cookie('login');
        if (cookie('login') != 'true') {
            $this->success("非法访问,请登录后访问!",U('./Wap/login'),1);
        }
    }




}