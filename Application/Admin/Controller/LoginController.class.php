<?php
namespace Admin\Controller;
use Org\Util\Date;
use Think\Controller;



class LoginController extends Controller {
    public function index(){
        $this->display();
    }
    //登录验证方法
    public function verification(){
        $username = I('post.username');
        $password = I('post.password');
        $code = I('post.yzcode');

        if(empty($username)){
            exit('<script type="text/javascript">alert("账号必须填写");history.go(-1);</script>');
        }
        if(empty($password)){
            exit('<script type="text/javascript">alert("密码必须填写");history.go(-1);</script>');
        }

        if(empty($code)){
            exit('<script type="text/javascript">alert("验证码必须填写");history.go(-1);</script>');
        }


        if(!check_code($code)){       //给function.php中定义的函数check_code，然后它返回真假
            exit('<script type="text/javascript">alert("验证码错误");window.location.href = document.referrer;</script>');
        }else{
            $pwd = md5($password);
            $table = M();
            $logintime = time();


            $list =$table->query("SELECT * FROM admin_user where username='%s'",[$username]);

            if ($list){
                if (($logintime - $list[0]['logintime']) < 900){
                    if ($list[0]['loginfail'] >= 3){
                        $wait = (900 - $logintime + $list[0]['logintime'])/60;
                        $wait = ceil($wait);
                        $this->error("登录失败次数超限，账户暂被冻结，请{$wait}分钟后再试",U("Login/Index"),10);
                    }else{
                        $times = $list[0]['loginfail'];
                    }
                }else{
                    $times = 0;
                }

                if ($list[0]['password'] == $pwd){
                    //通过代理商id判断该代理是否被禁用
                    $agent = M('car_agent')->where(array('id'=>$list[0]['agent_id']))->find();
                    if ($agent['agent_state']){
                        $this->error("该代理商已被禁用",U("Login/Index"));
                    }

                    //清除错误登录次数,并记录最后登录时间
                    $clean = "UPDATE admin_user SET logintime='%s',loginfail=%d WHERE id=%d";
                    $table->execute($clean,[$logintime,0,$list[0]['id']]);

                    $list[0]['logintime'] = $logintime;
                    $list[0] = aryAuthcode($list[0],'decode','hbykj');//对cookie信息进行加密
                    cookie('auserInfo',$list[0],1200);
                    $this->success("登录成功",U("Index/Index"));
                }else{
                    ++$times;
                    $up = "UPDATE admin_user SET logintime='%s',loginfail=%d WHERE id=%d";
                    $table->execute($up,[$logintime,$times,$list[0]['id']]);
                    if ($times < 3){
                        $times = 3-$times;
                        $this->error("密码错误;还有{$times}次登录机会。",U("Login/Index"));
                    }else{
                        $this->error("登录失败次数超限，账户暂被冻结，请15分钟后再试",U("Login/Index"),10);
                    }
                }

            }else{
                $this->error("该账户不存在",U("Login/Index"));
            }

        }

    }

    public function logout(){
        cookie('auserInfo',null);
        exit('<script type="text/javascript">alert("你已安全退出");location.href="'.U('Login/Index').'";</script>');
    }

    public function verify(){
        $config=array(
            'fontSize' =>200,//验证码字体大小
            'length'   =>4,//验证码位数
            'useNoise' =>false,//开启验证码杂点
            'useCurve' =>  false,
            'expire'   =>'60',//验证码的有效期（秒）
        );
        $Verify =new \Think\Verify($config);
        ob_clean();
        $Verify->entry();
    }



}