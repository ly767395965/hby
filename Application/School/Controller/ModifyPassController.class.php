<?php
namespace School\Controller;

use Common\Common\BaseHomeController;



class ModifyPassController extends BaseHomeController
{

    public function index()
    {
        if (!$_POST){
            $this->display();
        }
        if ($_POST){
            $code = I('post.code');
            $pass = md5(I('post.pass'));
            $phone = I('post.phone');
            $m = M();
            $model = new UserRegController();
            $checkCode = $model->check_code($code);
            $res = 0;
            if ($checkCode == false){
                $res = 3;
            }else{
                $sql = "UPDATE sc_user SET password = '%s' WHERE userphone = '%s' ";
                $result = $m->execute($sql,[$pass,$phone]);
                if ($result){
                    $res = 1;
                } else {
                    $res = 2;
                }

            }
            $this->ajaxReturn(array('reset'=>$res));

        }

    }
}