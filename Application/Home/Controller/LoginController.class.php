<?php
namespace Home\Controller;
use Home\Common;
use Think\Controller;


class LoginController extends Controller  {

    public function index(){
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
    	 $this->display();

    }
    //登录验证方法
    public function checkLogin(){
        $user = M('work_member');
        $username = I('post.user');
        $password = md5(I('post.pass'));
        $code = I('post.code');

        if(empty($username)){
            exit('<script type="text/javascript">alert("账号必须填写");history.go(-1);</script>');
        }
        if(empty($password)){
            exit('<script type="text/javascript">alert("密码必须填写");history.go(-1);</script>');
        }

//        if(empty($code)){
//            exit('<script type="text/javascript">alert("验证码必须填写");history.go(-1);</script>');
//        }


//        if(!check_code($code)){       //给function.php中定义的函数check_code，然后它返回真假
//            exit('<script type="text/javascript">alert("验证码错误");window.location.href = document.referrer;</script>');
//        }else{

//            $pwd = md5($password);
            $list = "SELECT id,username,phone,userpass,state FROM work_member WHERE (phone='%s') AND (userpass='%s')";
            $arr = [$username,$password];
            $data = $user->query($list,$arr);
            if ($data[0]['state'] == 0){
                if($data){
                    $username = authcode($data[0]['username'],'encode','123456');
                    cookie('username',$username);
                    //账户加密
                    $phone = authcode($data[0]['phone'],'encode','123456');
                    //加密账户写入cookie
                    cookie('tel',$phone);

                    //用户id加密
                    $uid = authcode($data[0]['id'],'encode','123456');
                    cookie('uid',$uid);
                    $url = cookie('url');
                    if ($url) {
                        echo "<script>location.href='".$url."';</script>";
                        cookie('url',null);
                    } else {
                        $this->success("登录成功",U("UserManage/Index"),1);
                    }
                }else{
                    $this->error("用户名密码错误",'',1);
                }
            } else {
                exit('<script type="text/javascript">alert("该账户已被冻结，请联系华邦出行解冻!");history.go(-1);</script>');
            }
//        }
    }




    //退出登录
    public function Loginout(){
        cookie('username',null);
        cookie('tel',null);
        exit('<script type="text/javascript">alert("你已安全退出");location.href="'.U('/').'";</script>');

    }

    public function verify(){
        $config=array(
            'fontSize' =>300,//验证码字体大小
            'length'   =>5,//验证码位数
            'useNoise' =>false,//开启验证码杂点
            'useCurve' =>  false,
            'expire'   =>'60',//验证码的有效期（秒）
        );
        $Verify =new \Think\Verify($config);
        ob_clean();
        $Verify->entry();
    }

    //验证登录用户是否存在
    public function isExist(){
        $table = M();
        $tel = I('post.usertel');
        $sql = "SELECT COUNT(id) FROM work_member WHERE (phone='%s')";
        $arr = [$tel];
        $list = $table->query($sql,$arr);
        if ($list[0]['count(id)'] > 0){
            $this->ajaxReturn(array('info'=>1));
        } else {
            $this->ajaxReturn(array('info'=>0));
        }
    }
    

}