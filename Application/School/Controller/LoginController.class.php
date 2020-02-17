<?php
namespace School\Controller;

use Common\Common\BaseHomeController;



class LoginController extends BaseHomeController
{

    public function index()
    {
        if (!$_POST){
            $this->display();
        }
        if ($_POST){
            $m =    M();
            $code = I('post.code');
            $loginUser = I('post.loginUser');
            $loginPwd = md5(I('post.loginPwd'));
            $model = new UserRegController();
            $checkCode = $model->check_code($code);
            if ($checkCode == false){
                $userInfo = 4;
            }else{
                $sql = "SELECT a.id,a.username,a.userphone,a.regdate,c.schoolname,c.id as schoolid FROM sc_user a LEFT JOIN sc_school b ON a.schoolid=b.id LEFT JOIN sc_academy c ON b.schoolid=c.id WHERE a.userphone = '%s' AND a.password = '%s'";
                $userInfo = $m->query($sql,[$loginUser,$loginPwd]);
                if ($userInfo[0]['id'] > 0){
                    $userInfo = $userInfo;
                } else {
                    $userInfo = 2;
                }

            }
            $this->ajaxReturn(array('login'=>$userInfo));
        }
    }

    public function login(){

    }
}