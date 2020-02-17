<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;
use Common\Common\sendAPI;

date_default_timezone_set('PRC');//设置时区


/**
 * Class NewsController
 * @package Home\Controller
 * 注册信息控制器
 */

class RegController extends BaseHomeController{
    protected static $table = 'work_member';
    public function index(){
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->display();

    }

    //添加用户注册信息 addUser()
    public function addUser() {
        //加载用户表
        $user = M(self::$table);
        if (empty($_POST)){
            $this->display();
        }
        if (!empty($_POST)){
            $rules = array(
                array('username', 'require', '<script>alert("用户名不能为空！");history.back(-1);</script>', 1),
                array('tel', 'number', '<script>alert("该手机用户已存在！");history.back(-1);</script>', 1),
                array('sex', 'require', '<script>alert("性别不能为空！");history.back(-1);</script>', 1),
                array('identity', 'require', '<script>alert("身份证不能为空！");history.back(-1);</script>', 1),
                array('pass','pass1','两个密码不一致',0,'confirm'),


            );
            if (!$user->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($user->getError());
            } else {
                //接受验证码
                $code = cookie('code');
                //创建当前时间
                $nowTime = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
                //接受验证码生成时间
                $oldTime = cookie('codeTime');
                //计算验证码过期时间
                if (floor((strtotime($nowTime)-strtotime($oldTime))/86400/60)>5) {
                    cookie('code',null);
                    echo '<script>alert("验证码过期");history.back(-1);</script>';
                } else {
                    //判断页面输入的验证码是否正确
                    if ($code == I('post.code')) {
                        //判断用户是否存在
                        $sql = "SELECT COUNT(id) FROM ".self::$table." WHERE (phone='%s')";
                        $arr = I('post.tel');
                        $list = $user->query($sql,$arr);
                        $num = $list[0]['count(id)'];
                        if ($num>0){
                            echo '<script>history.go(-1);</script>';
                        } else {
                            $sex = I('post.sex');
                            $data = array();
                            if ($sex == '男'){
                                $data['sex'] = 1;
                            } else {
                                $data['sex'] = 2;
                            }
                            $data['username'] = I('post.username');
                            $data['phone'] = I('post.tel');
                            $data['identitys'] = I('post.identity');
                            $data['userpass'] = md5(I('post.pass'));
                            $data['addtime'] = Date('Y-m-d H:i:s',time());
                            $uid=$user->add($data);
                            if ($uid){
                                //送新用户注册券
//                                $uid = $user->order('id desc')->find();//获取当前用户id
                                $sql = "SELECT a.id as aid,b.id as bid,a.status,b.issue_state,b.number,b.sum,b.time_limit,b.termofvalidity,b.termofvaliditytian,b.type,b.money,b.discount,b.use_limit,b.use_condition FROM coupon_activity a LEFT JOIN coupon_bollar b ON a.coupon_id=b.id WHERE a.act_type=1 AND a.status=0 AND b.issue_state=0 AND b.state=0 ORDER BY a.id DESC LIMIT 1";
                                $info = $user->query($sql);
                                //发放优惠券
                                if ($info[0]['status'] == 0 && $info[0]['issue_state'] == 0){
                                    $bollaruser = M('coupon_bollaruser');
                                    if ($info[0]['time_limit'] == 0){
                                        $time = $info[0]['termofvalidity'];
                                    }else if ($info[0]['time_limit'] == 1){
                                        $time = Date('Y-m-d H:i:s',strtotime("+ ".$info[0]['termofvaliditytian']." day"));
                                    } else {
                                        $time ='0000-00-00 00:00:00';
                                    }
                                    $bollaruser_data = array();
                                    $bollaruser_data['uid'] = $uid;
                                    $bollaruser_data['bid'] = $info[0]['bid'];
                                    $bollaruser_data['addtime'] = Date('Y-m-d H:i:s',time());
                                    $bollaruser_data['updatetime'] = Date('Y-m-d H:i:s',time());
                                    $bollaruser_data['coupon_type'] = $info[0]['type'];
                                    $bollaruser_data['money'] = $info[0]['money'];
                                    $bollaruser_data['discount'] = $info[0]['discount'];
                                    $bollaruser_data['use_limit'] = $info[0]['use_limit'];
                                    $bollaruser_data['use_condition'] = $info[0]['use_condition'];
                                    $bollaruser_data['termofvalidity'] = $time;
                                    //判断该优惠券是否为无限领取
                                    if ($info[0]['number'] != -1){
                                        //非无限领取优惠券时，判断优惠券领取数量不能大于发行数量。
                                        if ($info[0]['sum'] < $info[0]['number']){
                                            $row = $bollaruser->add($bollaruser_data);
                                        }
                                    } else {
                                        $row = $bollaruser->add($bollaruser_data);
                                    }
                                    if ($row){
                                        $updata = M();
                                        $up = "UPDATE coupon_bollar SET `sum` = '".$info[0]['sum'] +1 ."'WHERE id=%d";
                                        $ary = [$info[0]['bid']];
                                        $updata->execute($up,$ary);
                                    }
                                }
                                cookie('code',null);
                                $this->success('注册成功',U('Login/Index'),1);
                            } else {
                                $this->error('注册失败','',1);
                            }
                        }

                    } else {
                        echo '<script>alert("输入的验证码有误!");history.back(-1);</script>';
                    }
                }

            }
        }


    }

    //验证手机用户是否已存在
    public function isExist() {
        $m = M();
        $tel = I('post.usertel');
        $sql = "SELECT COUNT(id) FROM ".self::$table." WHERE (phone='%s')";
        $arr = [$tel];
        $list = $m->query($sql,$arr);
        if ($list[0]['count(id)'] > 0){
            $this->ajaxReturn(array('info'=>1));
        } else {
            $this->ajaxReturn(array('info'=>0));
        }

    }

    /**
     * @return string
     */
    public function setSms()
    {
        $usertel = I('post.usertel');//接受手机号
        $yzm = rand(10000,99999);//生成随机验证码
        $create = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
        $url 		= "http://www.yzmsms.cn/sendSmsYZM.do";//提交地址
        $username 	= 'huabangyzm';//用户名 验证码平台账户
        $password 	= "p0GgyQ";//原密码
        $sendAPI = new sendAPI($url,$username,$password);
        $data = array(
            'content' 	=> '【华邦出行】验证码为：'. $yzm .',有效期为5分钟,请尽快使用!',//短信内容必须含有“码”字
            'mobile' 	=> $usertel,//手机号码
            'xh'		=> ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $resultStr = $sendAPI->sendSMS('POST');//GET or POST
//        $resultStr = '1,22222';
//        $this->ajaxReturn(array('state'=>$resultStr));
        $result = explode(',',$resultStr);
        if ($result[0]==1) {
            cookie('code',$yzm);
            cookie('codeTime',$create);
            $this->ajaxReturn(array('state'=>1));
        } else {
            $this->ajaxReturn(array('state'=>0));
        }
    }




}