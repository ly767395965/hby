<?php
namespace School\Controller;

use Common\Common\BaseHomeController;
use Think\Verify;


class UserRegController extends BaseHomeController
{

    public function index()
    {
        if (!$_POST){
            $xuexiao = M();
            $getSchool = "SELECT a.id,a.schoolid,b.schoolname FROM sc_school a LEFT JOIN sc_academy b ON a.schoolid=b.id";
            $schoolList = $xuexiao->query($getSchool);
            $this->assign('school',$schoolList);
            $this->display();
        }
        if ($_POST){
            $m = M();
            $school = I('post.school');
            $userName = I('post.userName');
            $mobile = I('post.mobile');
            $pwd1 = md5(I('post.pwd1'));
            $code = I('post.code');
            $time = date('Y-m-d H:i:s',time());
            $checkCode = $this->check_code($code);
            if ($checkCode == false){
                $feedback = 4;
            } else {
                $userNumber = $this->getUserNumber($m,$mobile);
                if ($userNumber[0]['count(id)'] > 0 ){
                    $feedback = 2;
                }else{
                    $sql = "INSERT INTO  sc_user (username,password,userphone,schoolid,regdate) VALUES ('%s','%s','%s','%d','%s')";
                    $result = $m->execute($sql,[$userName,$pwd1,$mobile,$school,$time]);
                    if ($result){
                        $feedback = $result;
                    } else {
                        $feedback = 3;
                    }
                }
            }

            $this->ajaxReturn($feedback);
        }
    }

    public function getUserNumber($m,$mobile){
        $sql = "SELECT COUNT(id) FROM sc_user WHERE userphone = '%s' ";
        $res = $m->query($sql,[$mobile]);
        return $res;
    }


    //生成验证码
    public function verify(){
        $config=array(
            'fontSize' =>200,//验证码字体大小
            'length'   =>4,//验证码位数
            'useNoise' =>true,//开启验证码杂点
            'useCurve' =>  false,
            'expire'   =>'60',//验证码的有效期（秒）
        );
        $Verify = new Verify($config);
        ob_clean();
        $Verify->entry();
    }
    //验证码判断
    function check_code($code, $id = ""){
        $verify = new Verify();
        return $verify->check($code, $id);
    }

}