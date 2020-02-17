<?php
namespace App\Controller;

use Common\Common\App;
use Common\Common\BaseHomeController;
use Common\Common\sendAPI;
use  Admin\Controller\SubmittedController;//网约车信息报送类
use Monolog\Handler\MailHandlerTest;
use Org\Util\Date;

use Vendor\Alipay\AlipayNotify;
use Vendor\Alipay\AlipaySubmit;
use Vendor\APPAlipay\APPAlipayNotify;




date_default_timezone_set('PRC');//设置时区


class IndexController extends BaseHomeController
{
    //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');

        vendor('APPAlipay.alipay_rsa.function');
        vendor('APPAlipay.alipay_core.function');


    }

    public function index()
    {	
		$user = M('order_netcar');$uid = M('order_netcar')->field('OrderCode')->where(['id'=>'106'])->find();//获取当前注册用户的id
		echo '<pre>';
		var_dump($uid['ordercode']);
		$sub = new SubmittedController();   //对注册乘客信息进行报送
        $sub->controlSub('baseInfoPassenger',$uid['id']);

    }

	


    //车辆相关接口
    public function car()
    {
        $userid = I('get.userid');
        $cartype = I('get.cartype');
        $carclass = I('get.carclass');
        $carid = I('get.carid');
        $pagenum = I('get.pagenum');
        $sql = '';
        $countsql = '';
        $app = App::getInstance();


        //推荐车辆接口
        if ($carclass == 'tj') {
            $sql = "SELECT a.id,b.brand,a.carmodelname,a.agestyle,a.configstyle,a.sitecount,a.bearboxtype,a.displacement,a.fuelstand,a.skylight,a.tankcapacity,a.chairtype,a.shortdayprice,a.weekdayprice,a.monthdayPrice,a.frontimg,a.leftanterior,a.rightfront,a.behindimg 
FROM car_carmodel a LEFT JOIN car_barand b on a.barandid=b.id where a.state=0 and a.isdel=0 AND a.isrecommend=1 ORDER BY a.sort ASC";
            $countsql = "SELECT COUNT(a.id) FROM car_carmodel a LEFT JOIN car_barand b on a.barandid=b.id where a.state=0 and a.isdel=0 AND a.isrecommend=1";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(a.id)', true);
            $count = count($list);
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
        }

        //车型详情接口
        if ($carid != '') {
            $sql = "SELECT a.id,b.brand,a.carmodelname,a.agestyle,a.configstyle,a.sitecount,a.bearboxtype,a.displacement,a.fuelstand,a.skylight,a.tankcapacity,a.chairtype,a.shortdayprice,a.weekdayprice,a.monthdayPrice,a.frontimg,a.leftanterior,a.rightfront,a.rightimg,a.behindimg 
FROM car_carmodel a LEFT JOIN car_barand b on a.barandid=b.id where a.isdel=0 AND a.id=" . $carid . " ORDER BY ID DESC";
            $countsql = "SELECT COUNT(a.id) FROM car_carmodel a LEFT JOIN car_barand b on a.barandid=b.id where a.isdel=0 AND a.isrecommend=1 AND a.id=" . $carid;
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(a.id)', false);
            $count = count($list);
            $time =  Date('Ymd',time());
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0,'today'=>$time];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
        }

        //可参与优惠活动查询
        $active = coupon_active(2,$userid);
        if ($active['activity_num'] != 0){
            $list['active'] = $active['activity'];
        }

        $json = json_encode($list);
        echo $json;
    }


    //品牌列表接口
    public function brand()
    {
        $sql = '';
        $countsql = '';
        $app = App::getInstance();
        $en = I('get.en');

        if ($en != '') {
            $sql = "select brand,initial from car_barand WHERE isdel=0 AND initial='$en' ORDER BY initial ASC";
            $countsql = "select COUNT(id) from car_barand WHERE isdel=0 AND initial='$en' ORDER BY initial ASC";
        } else {
            $sql = "select brand,initial from car_barand WHERE isdel=0 ORDER BY initial ASC";
            $countsql = "select COUNT(id) from car_barand WHERE isdel=0 ORDER BY initial ASC";
        }

        $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(id)', false);
        $count = count($list);
        if ($count != 0) {
            $list = ['brand' => $list, 'error' => 0];
        } else {
            $list = ['brand' => $list, 'error' => 1];
        }


        $json = json_encode($list);
        echo $json;
    }


    //车型筛选列表接口brand品牌,type车型,auto变速箱,sort排序asc为正序，desc为倒序
    public function carlist()
    {
        $app = App::getInstance();
        //获取get方法提交过来的数据
        $brand = I('get.brand');
        $type = I('get.type');
        $auto = I('get.auto');
        $sort = I('get.sort');
        $pagenum = I('get.pagenum');

        //默认sql，根据下面判断组装成完整sql执行
        $sql = "SELECT a.id,b.brand,a.carmodelname,a.agestyle,a.sitecount,a.bearboxtype,a.displacement,a.shortdayprice,a.weekdayprice,a.monthdayPrice,a.frontimg FROM car_carmodel a LEFT JOIN car_barand b on a.barandid=b.id where (a.isdel=0) AND (a.state=0)";
        $countsql = "SELECT COUNT(a.id) FROM car_carmodel a LEFT JOIN car_barand b on a.barandid=b.id where (a.isdel=0) AND (a.state=0)";

        //品牌筛选
        if ($brand != '') {
            $sql .= " AND (b.brand='".$brand."')";
            $countsql .= " AND (b.brand='" . $brand . "') ";
        }

        if ($type != '') {
            $sql .= " AND (a.carmodeltype='".$type."')";
            $countsql .= " AND (a.carmodeltype='" . $type . "')";
        }

        if ($auto != '') {
            $sql .= " AND (a.bearboxtype='".$auto."')";
            $countsql .= " AND (a.bearboxtype='" . $auto . "')";
        }


        if ($sort == 'asc') {
            $sql .= "ORDER BY a.shortdayprice ASC";
        }

        if ($sort == 'desc') {
            $sql .= " ORDER BY a.shortdayprice DESC";
        }

        if ($sort == '') {
            $sql .= " ORDER BY a.shortdayprice ASC";
        }



        $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(a.id)', true);
        $count = count($list);
        if ($count != 0) {
            $list = ['car' => $list, 'error' => 0];
        } else {
            $list = ['car' => $list, 'error' => 1];
        }

        $list = json_encode($list);
        echo $list;
    }


    //首页顶部广告接口
    public function banner()
    {
        $sql = '';
        $countsql = '';
        $app = App::getInstance();
        $sql = "select * from ad_banner where classid=1 AND isdel=0 ORDER BY ID DESC";
        $countsql = "select COUNT(id) from ad_banner where classid=1 AND isdel=0";
        $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(id)', false);
        $count = count($list);
        if ($count != 0) {
            $list = ['car' => $list, 'error' => 0];
        } else {
            $list = ['car' => $list, 'error' => 1];
        }


        $json = json_encode($list);
        echo $json;
    }

    //首页底部广告接口
    public function bottom()
    {
        $app = App::getInstance();
        $sql = '';
        $countsql = '';
        $app = App::getInstance();
        $sql = "select * from ad_banner where classid=2 AND isdel=0 ORDER BY ID DESC";
        $countsql = "select COUNT(id) from ad_banner where classid=2 AND isdel=0";
        $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(id)', false);
        $count = count($list);
        if ($count != 0) {
            $list = ['car' => $list, 'error' => 0];
        } else {
            $list = ['car' => $list, 'error' => 1];
        }


        $json = json_encode($list);
        echo $json;
    }


    //优惠活动接口，id为空为优惠活动列表，不为空则为详情
    public function activity()
    {
        $pagenum = I('get.pagenum');
        $id = I('get.id');
        $sql = '';
        $countsql = '';
        $app = App::getInstance();
        $m = M();

        //id为空，显示列表
        if ($id == '') {
            $sql = "select id,theme,describetxt,start_time,end_time,cover,create_time from publish_event WHERE is_del=0 ORDER BY ID DESC";
            $countsql = "select COUNT(id) from publish_event WHERE is_del=0 ORDER BY ID DESC";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(id)', true);
            $count = count($list);
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
            $json = json_encode($list);
            echo $json;
        } else {
            $sql = "select id,theme,content,describetxt,start_time,end_time,create_time from publish_event WHERE is_del=0 AND id=%d";
            $list = $m->query($sql, [$id]);
            if (!empty($list)) {
                $this->assign('list', $list);
                $this->display('');
            }

        }
    }

    //关于我们接口
    public function about(){
        $table = M();
        $sql = " SELECT title,content FROM new_aticle WHERE catid=2 AND isdel = 0";
        $list = $table->query($sql);
        $this->assign('list', $list);
        $this->display('');
    }


    //旅游出行接口
    public function trip()
    {
        $pagenum = I('get.pagenum');
        $id = I('get.id');
        $sql = '';
        $countsql = '';
        $app = App::getInstance();
        $m = M();

        //id为空显示列表，不为空显示详情
        if ($id == '') {
            $sql = "SELECT id,title,subtitle,cover,addtime FROM new_aticle WHERE catid=3 AND isdel=0 ORDER BY ID DESC";
            $countsql = "SELECT COUNT(id)FROM new_aticle WHERE catid=3 AND isdel=0 ORDER BY ID DESC";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(id)', true);
            $count = count($list);
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
            $json = json_encode($list);
            echo $json;
        } else {
            $sql = "SELECT title,subtitle,cover,content,addtime FROM new_aticle WHERE isdel=0 AND id=%d";
            $list = $m->query($sql, [$id]);
            if (!empty($list)) {
                $this->assign('list', $list);
                $this->display('trip');
            }

        }
    }

    //新闻接口
    public function news()
    {
        $pagenum = I('get.pagenum');
        $id = I('get.id');
        $sql = '';
        $countsql = '';
        $app = App::getInstance();
        $m = M();

        //id为空显示列表，不为空显示详情
        if ($id == '') {
            $sql = "SELECT id,title,subtitle,cover,addtime FROM new_aticle WHERE catid=1 AND isdel=0 ORDER BY ID DESC";
            $countsql = "SELECT COUNT(id) FROM new_aticle WHERE catid=1 AND isdel=0 ORDER BY ID DESC";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(id)', true);
            $count = count($list);
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
            $json = json_encode($list);
            echo $json;
        } else {
            $sql = "SELECT id,title,subtitle,cover,content,addtime FROM new_aticle WHERE catid=1 AND isdel=0 AND id=%d ORDER BY ID DESC";
            $list = $m->query($sql, [$id]);
            if (!empty($list)) {
                $this->assign('list', $list);
                $this->display('news');
            }

        }
    }


    //租车须知
    public function notice()
    {
        $pagenum = I('get.pagenum');
        $id = I('get.id');
        $sql = '';
        $countsql = '';
        $app = App::getInstance();
        $m = M();

        //id为空显示列表，不为空显示详情
        if ($id == '') {
            $sql = "SELECT id,title,subtitle,cover FROM new_aticle WHERE catid=10 AND isdel=0 ORDER BY ID DESC";
            $countsql = "SELECT COUNT(id) FROM new_aticle WHERE catid=10 AND isdel=0 ORDER BY ID DESC";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(id)', true);
            $count = count($list);
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
            $json = json_encode($list);
            echo $json;
        } else {
            $sql = "SELECT id,title,subtitle,cover,content FROM new_aticle WHERE catid=10 AND isdel=0 AND id=%d ORDER BY ID DESC";
            $list = $m->query($sql, [$id]);
            if (!empty($list)) {
                $this->assign('list', $list);
                $this->display('news');
            }

        }
    }


    //优惠车辆接口
    public function favorable()
    {

        $id = I('get.id');
        $pagenum = I('get.pagenum');
        $sql = '';
        $countsql = '';
        $app = App::getInstance();


        //id为空显示优惠车辆列表，不为空显示详情
        if ($id == '') {
            $sql = "SELECT a.id,b.id AS typeid,c.brand,b.carmodelname,a.goodprice,b.carmodeltype,b.frontimg,b.agestyle,sitecount,b.bearboxtype,b.displacement,b.fuelstand,b.skylight,b.tankcapacity,b.chairtype,b.shortdayprice FROM car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel=b.id LEFT JOIN car_barand c ON a.brand=c.id WHERE (a.usestatus=0) AND (a.isdiscount=1) AND (a.isdel=0) ORDER BY a.id DESC";
            $countsql = "SELECT COUNT(a.id) FROM car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel=b.id LEFT JOIN car_barand c ON a.brand=c.id WHERE a.usestatus=0 AND a.isdiscount=1 AND a.isdel=0";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [], 'count(a.id)', true);
            $count = count($list);
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
        } else {
            $sql = "SELECT a.id,a.carno,c.brand,b.id AS typeid,b.carmodelname,a.color,a.motorno,a.usedmileage, a.maintainmileage,a.goodprice,a.costprice,b.carmodeltype,b.frontimg,b.leftanterior,b.rightimg,b.rightfront,b.behindimg,b.agestyle,b.configstyle,sitecount,b.bearboxtype,b.displacement,b.fuelstand,b.skylight,b.tankcapacity,b.chairtype,b.shortdayprice,b.weekdayprice,b.monthdayPrice FROM car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel=b.id LEFT JOIN car_barand c ON a.brand=c.id WHERE a.usestatus=0 AND a.isdiscount=1 AND a.isdel=0 AND a.id=%d";
            $countsql = "SELECT COUNT(a.id) FROM car_carinfo a LEFT JOIN car_carmodel b ON a.carmodel=b.id LEFT JOIN car_barand c ON a.brand=c.id WHERE a.usestatus=0 AND a.isdiscount=1 AND a.isdel=0 AND a.id=%d";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [$id], 'count(a.id)', false);
            $count = count($list);
            if ($count != 0) {
                $list = ['car' => $list, 'error' => 0,'today'=>''];
            } else {
                $list = ['car' => $list, 'error' => 1];
            }
        }

        $json = json_encode($list);
        echo $json;


    }

    /**
     * APP端用户登录方法
     */
    public function appLogin()
    {

        $app = App::getInstance();
        $name = I('post.loginUser');
        $pwd = md5(I('post.loginPwd'));
        $teltype = I('post.teltype');
        $m = M();
        $sql  = "SELECT id,phone,username,usertype FROM work_member WHERE (phone = '%s') AND (userpass = '%s') AND (usertype=0) AND (state=0)";
        $countsql  = "SELECT COUNT(id) FROM work_member WHERE (phone = '%s') AND (userpass = '%s')  AND (usertype=0) AND (state=0)";
        $arr = [$name,$pwd];
        if ($name != '' and $pwd != '') {
            $list = $app->pageDisplay($sql,$countsql,5,$arr,'count(id)',false);
            $count = count($list);
            if ($count != 0) {
                $updatesql = "UPDATE work_member SET teltype='%s' WHERE phone='%s'";
                $updateary = [$teltype,$name];
                $num = $m->execute($updatesql,$updateary);
                if ($num !== false) {
                    $list = ['login' => $list, 'error' => 0];
                } else {
                    $list = ['login' => $list, 'error' => 1];
                }
            } else {
                $list = ['login' => $list, 'error' => 2];
            }
        }
        $json = json_encode($list);
        echo $json;
    }

    //wap站点登录方法
    public function wapLogin()
    {
        $app = App::getInstance();
        $name = I('post.loginUser');
        $pwd = md5(I('post.loginPwd'));
        $sql  = "SELECT id,phone,username,usertype FROM work_member WHERE (phone = '%s') AND (userpass = '%s') AND (usertype=0) AND (state=0)";
        $countsql  = "SELECT COUNT(id) FROM work_member WHERE (phone = '%s') AND (userpass = '%s') AND (usertype=0) AND (state=0)";
        $arr = [$name,$pwd];
        if ($name != '' and $pwd != '') {
            $list = $app->pageDisplay($sql,$countsql,5,$arr,'count(id)',false);
            $count = count($list);
            if ($count != 0) {
                $list = ['login' => $list, 'error' => 0];
            } else {
                $list = ['login' => $list, 'error' => 1];
            }
        }

        $json = json_encode($list);
        echo $json;


    }

    //过滤字符串方法
    function strFilter($str){
        $str = str_replace('`', '', $str);
        $str = str_replace('·', '', $str);
        $str = str_replace('~', '', $str);
        $str = str_replace('!', '', $str);
        $str = str_replace('！', '', $str);
        $str = str_replace('@', '', $str);
        $str = str_replace('#', '', $str);
        $str = str_replace('$', '', $str);
        $str = str_replace('￥', '', $str);
        $str = str_replace('%', '', $str);
        $str = str_replace('^', '', $str);
        $str = str_replace('……', '', $str);
        $str = str_replace('&', '', $str);
        $str = str_replace('*', '', $str);
        $str = str_replace('(', '', $str);
        $str = str_replace(')', '', $str);
        $str = str_replace('（', '', $str);
        $str = str_replace('）', '', $str);
        $str = str_replace('-', '', $str);
        $str = str_replace('_', '', $str);
        $str = str_replace('——', '', $str);
        $str = str_replace('+', '', $str);
        $str = str_replace('=', '', $str);
        $str = str_replace('|', '', $str);
        $str = str_replace('\\', '', $str);
        $str = str_replace('[', '', $str);
        $str = str_replace(']', '', $str);
        $str = str_replace('【', '', $str);
        $str = str_replace('】', '', $str);
        $str = str_replace('{', '', $str);
        $str = str_replace('}', '', $str);
        $str = str_replace(';', '', $str);
        $str = str_replace('；', '', $str);
        $str = str_replace(':', '', $str);
        $str = str_replace('：', '', $str);
        $str = str_replace('\'', '', $str);
        $str = str_replace('"', '', $str);
        $str = str_replace('“', '', $str);
        $str = str_replace('”', '', $str);
        $str = str_replace(',', '', $str);
        $str = str_replace('，', '', $str);
        $str = str_replace('<', '', $str);
        $str = str_replace('>', '', $str);
        $str = str_replace('《', '', $str);
        $str = str_replace('》', '', $str);
        $str = str_replace('.', '', $str);
        $str = str_replace('。', '', $str);
        $str = str_replace('/', '', $str);
        $str = str_replace('、', '', $str);
        $str = str_replace('?', '', $str);
        $str = str_replace('？', '', $str);
        return trim($str);
    }

    /**
     * APP端用户注册方法
     */
    public function appReg()
    {
        //加载用户表
        $user = M('work_member');$uid = $user->order('id desc')->find();//获取当前注册用户的id
        if (!empty($_POST)) {
            $rules = array(
                array('userName', 'require', '用户名不能为空', 1),
                array('mobile', 'number', '手机号不能为空', 1),
                array('sex', 'require', '性别不能为空', 1),
                array('idCard', 'require', '身份证号不能为空', 1),
                array('pwd1 ', 'pwd2 ', '两个密码不一致', 0, 'confirm'),
            );
            if (!$user->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $this->ajaxReturn(array('error' => 2));//提交数据验证
            } else {
                //接受验证码
                $code = cookie('code');
                //创建当前时间
                $nowTime = date('Y-m-d H:i:s ', $_SERVER['REQUEST_TIME']);
                //接受验证码生成时间
                $oldTime = cookie('codeTime');
                //计算验证码过期时间
                if (floor((strtotime($nowTime) - strtotime($oldTime)) / 86400 / 60) > 5) {
                    cookie('code', null);
                    $this->ajaxReturn(array('reg' => 'CodeOverdue'));//验证码过期
                } else {
                    //判断页面输入的验证码是否正确
                    if ($code == I('post.code')) {
                        //判断用户是否存在
                        $sql = "SELECT COUNT(id) FROM work_member WHERE (phone='%s')";
                        $arr = I('post.mobile');
                        $list = $user->query($sql, $arr);
                        $num = $list[0]['COUNT(id)'];
                        if ($num > 0) {
                            $this->ajaxReturn(array('reg' => 'IsExist'));//表示该用户已存在，请更换手机注册
                        } else {
                            $sex = I('post.sex');
                            $data = array();
                            if ($sex == '男') {
                                $data['sex'] = 1;
                            } else {
                                $data['sex'] = 2;
                            }
                            //过滤身份证
                            $idCard = I('post.idCard');
                            $userCard = $this->strFilter($idCard);
                            $data['username'] = I('post.userName');
                            $data['phone'] = I('post.mobile');
                            $data['identitys'] = $userCard;
                            $data['userpass'] = md5(I('post.pwd1'));
                            $data['addtime'] = Date('Y-m-d H:i:s', time());
                            $userinfo = $user->add($data);
                            $uid = $user->order('id desc')->find();//获取当前注册用户的id
                            if ($userinfo) {
                                
                                //记录注册信息
                                $tab = M();
                                $RegisterIp = I('post.RegisterIp');//注册ip
                                $RegisterPort = I('post.RegisterPort');//注册端口
                                $RegisterMac = I('post.RegisterMac');//登陆MAC/移动终端硬件标识
                                $RegisterImsi = I('post.RegisterImsi');//国际移动用户识别码
                                $RegisterImei = I('post.RegisterImei');//国际移动设备识别码
                                $RegisterCity = I('post.RegisterCity');//注册城市

                                $inser = "INSERT INTO register_info (Uid,UserType,RegisterIp,RegisterPort,RegisterMac,RegisterImsi,RegisterImei,RegisterCity,RegisterTime,UpdateTime) VALUES (%d,%d,'%s','%s','%s','%s','%s','%s','%s','%s')";
                                $arr = [$uid['id'],0,$RegisterIp,$RegisterPort,$RegisterMac,$RegisterImsi,$RegisterImei,$RegisterCity,Date('Y-m-d H:m:s', time()),Date('Y-m-d H:m:s', time())];
                                $tab->execute($inser,$arr);

								$sub = new SubmittedController();   //对注册乘客信息进行报送
                                $sub->controlSub('baseInfoPassenger',$uid['id']);
								
                                //送新用户注册券
//                                $uid = $user->order('id desc')->find();//获取当前用户id
                                /*$sql = "SELECT a.id as aid,b.id as bid,a.status,b.issue_state,b.number,b.sum,b.endtime,b.limit,b.time_limit,b.termofvalidity,b.termofvaliditytian,b.type,b.money,b.discount,b.min_consume FROM coupon_activity a LEFT JOIN coupon_bollar b ON a.coupon_id=b.id WHERE a.act_type=1 AND b.issue_state=1 AND b.state=0";
                                $info = $user->query($sql);
                                //发放优惠券
                                if ($info[0]['status'] == 0 && $info[0]['issue_state'] == 1){
                                    $bollaruser = M('coupon_bollaruser');
                                    if ($info[0]['time_limit'] == 0){
                                        $time = $info[0]['termofvalidity'];
                                    }else if ($info[0]['time_limit'] == 1){
                                        $time = Date('Y-m-d H:i:s',strtotime("+ ".$info[0]['termofvaliditytian']." day"));
                                    } else {
                                        $time ='0000-00-00 00:00:00';
                                    }
                                    $bollaruser_data = array();
                                    $bollaruser_data['uid'] = $uid['id'];
                                    $bollaruser_data['bid'] = $info[0]['bid'];
                                    $bollaruser_data['addtime'] = Date('Y-m-d H:i:s',time());
                                    $bollaruser_data['updatetime'] = Date('Y-m-d H:i:s',time());
                                    $bollaruser_data['coupon_type'] = $info[0]['type'];
                                    $bollaruser_data['money'] = $info[0]['money'];
                                    $bollaruser_data['discount'] = $info[0]['discount'];
                                    $bollaruser_data['min_consume'] = $info[0]['min_consume'];
                                    $bollaruser_data['time_limit'] = $info[0]['time_limit'];
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
                                        $up = "UPDATE coupon_bollar SET `sum` = '".$info[0]['sum']."' +1 WHERE id=%d";
                                        $ary = [$info[0]['bid']];
                                        $updata->execute($up,$ary);
                                    }
                                }*/
                                //通过当前用户id 查询数据以json格式返回前端
//                              //  $udate = $user->where(array('id=' . $uid['id']))->select();
//                              //  $json = json_encode($udate);
									$this->ajaxReturn(array('reg' => 'regOk'));
                                


                            } else {
                                $this->ajaxReturn(array('reg' => 'RegError'));//注册失败
                            }
                        }

                    } else {
                        $this->ajaxReturn(array('reg' => 'CodeError'));//验证码错误
                    }
                }

            }
        } else {
            $list = ['error' => 1];
            $list = json_encode($list);
            echo $list;
        }
    }


    /**
     * 代驾端端用户注册方法
     */
    public function actingSideReg()
    {
        //加载用户表
        $user = M('car_driverinfo');
        if (!empty($_POST)) {
            $rules = array(
                array('mobile', 'number', '手机号不能为空', 1),
                array('idCard', 'require', '身份证号不能为空', 1),
                array('pwd1 ', 'pwd2 ', '两个密码不一致', 0, 'confirm'),
            );
            if (!$user->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $this->ajaxReturn(array('error' => 2));//提交数据验证
            } else {
                //接受验证码
                $code = cookie('code');
                //创建当前时间
                $nowTime = date('Y-m-d H:i:s ', $_SERVER['REQUEST_TIME']);
                //接受验证码生成时间
                $oldTime = cookie('codeTime');
                //计算验证码过期时间
                if (floor((strtotime($nowTime) - strtotime($oldTime)) / 86400 / 60) > 5) {
                    cookie('code', null);
                    $this->ajaxReturn(array('reg' => 'CodeOverdue'));//验证码过期
                } else {
                    //判断页面输入的验证码是否正确
                    if ($code == I('post.code')) {
                        //判断用户是否存在
                        $sql = "SELECT COUNT(id) FROM car_driverinfo WHERE (phone='%s') and isdel = 0";
                        $arr = I('post.mobile');
                        $list = $user->query($sql, $arr);
                        $num = $list[0]['count(id)'];
                        if ($num > 0) {
                            $this->ajaxReturn(array('reg' => 'IsExist'));//表示该用户已存在，请更换手机注册
                        } else {
                            $data = array();
                            //过滤身份证
                            $idCard = I('post.idCard');
                            $userCard = $this->strFilter($idCard);
                            $data['drivername'] = I('post.userName');
                            $data['phone'] = I('post.mobile');
                            $data['idCard'] = $userCard;
                            $data['userpass'] = md5(I('post.pwd1'));
                            $data['state'] = 2;//默认待审核状态
                            $data['addtime'] = Date('Y-m-d H:m:s', time());
                            $userinfo = $user->add($data);
                            $uid = $user->order('id desc')->find();//获取当前注册用户的id
                            if ($userinfo) {

                                //通过当前用户id 查询数据以json格式返回前端
                                $udate = $user->where(array('id=' . $uid['id']))->select();
                                $json = json_encode($udate);
                                $this->ajaxReturn(array('reg' => $json));
                            } else {
                                $this->ajaxReturn(array('reg' => 'RegError'));//注册失败
                            }
                        }
                    } else {
                        $this->ajaxReturn(array('reg' => 'CodeError'));//验证码错误
                    }
                }

            }
        } else {
            $list = ['error' => 1];
            $list = json_encode($list);
            echo $list;
        }
    }









    //修改密码
    public function editPass()
    {
        if (!empty($_POST)) {

            $phone = I('post.mobile');
            $pwd = md5(I('post.newPwd'));
            //接受验证码
            $code = cookie('code');
            //创建当前时间
            $nowTime = date('Y-m-d H:i:s ', $_SERVER['REQUEST_TIME']);
            //接受验证码生成时间
            $oldTime = cookie('codeTime');
            //计算验证码过期时间
            if (floor((strtotime($nowTime) - strtotime($oldTime)) / 86400 / 60) > 5) {
                cookie('code', null);
                $this->ajaxReturn(array('error' => 'overdue'));//超过5分钟验证码过期
            } else {
                if ($code == I('post.code')) {
                    $m = M();
                    $sql = "SELECT COUNT(id) FROM work_member WHERE phone='%s' ";
                    $ary = [$phone];
                    $list = $m->query($sql, $ary);
                    $num = $list[0]['count(id)'];
                    //判断账号是否存在
                    if ($num > 0) {

                        $updateSql = "UPDATE work_member SET userpass='%s',UpdateTime='%s',Flag=%d WHERE phone='%s' ";
                        $updateAry = [$pwd,$nowTime,2,$phone];
                        $updateNum = $m->execute($updateSql, $updateAry);

                        if ($updateNum !== false) {
                            $this->ajaxReturn(array('error' => 'success'));//成功
                        } else {
                            $this->ajaxReturn(array('error' => 'fail'));//表示失败
                        }
                    } else {
                        $this->ajaxReturn(array('error' => 'noUser')); //账号不存在
                    }
                } else {
                    $this->ajaxReturn(array('error' => 'codeError'));  //验证码错误
                }
            }
        } else {
            $this->ajaxReturn(array('error' => 'fail'));
        }
    }


    /**发送短信接口
     * @return integer
     */
    public function setSms()
    {
        $usertel = I('post.mobile');//接受手机号
        $yzm = rand(10000, 99999);//生成随机验证码
        $create = date('Y-m-d H:i:s ', $_SERVER['REQUEST_TIME']);
        $url = "http://www.yzmsms.cn/sendSmsYZM.do";//提/交地址
        $username = 'huabangyzm';//用户名 验证码平台账户
        $password = "p0GgyQ";//原密码
        $sendAPI = new sendAPI($url, $username, $password);
        $data = array(
            'content' => '【华邦出行】验证码为：' . $yzm . ',有效期为5分钟,请尽快使用!',//短信内容必须含有“码”字
            'mobile' => $usertel,//手机号码
            'xh' => ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $resultStr = $sendAPI->sendSMS('POST');//GET or POST
//        $resultStr = '1,22222';
//        $this->ajaxReturn(array('state'=>$resultStr));
        $result = explode(',', $resultStr);
        if ($result[0] == 1) {
            cookie('code', $yzm);
            cookie('codeTime', $create);
            $this->ajaxReturn(array('state' => 1));
        } else {
            $this->ajaxReturn(array('state' => 0));
        }
    }


    /**会员订单接口
     *get提交
     */
    public function order()
    {
        $id = I('get.id');
        $pagenum = I('get.pagenum');
        if (!empty($_GET)) {
            $app = App::getInstance();
            $sql = "SELECT id,order_code,order_date,pk_date,re_date,pre_price,order_state,pay_way FROM `order` WHERE uid = %d ORDER BY id DESC";
            $countsql = "SELECT id,order_code,order_date,pk_date,re_date,pre_price,order_state,pay_way FROM `order` WHERE uid = %d ORDER BY id DESC";
            $list = $app->pageDisplay($sql, $countsql, $pagenum, [$id], 'count(a.id)', true);
            $count = count($list);
            if ($count != 0) {
                $list = ['login' => $list, 'error' => 0];
            } else {
                $list = ['login' => $list, 'error' => 1];
            }
        } else {
            $list = ['error' => 1];
        }

        $json = json_encode($list);
        echo $json;
    }


    //会员信息
    public function member()
    {
        $id = I('get.id');
        if ($id != '') {
            $m = M();
            $sql = "SELECT username,phone,usertype,check_cycle,identitys,sex,score,balance,addtime FROM work_member WHERE phone='%s'";
            $ary = [$id];
            $list = $m->query($sql,$ary);
            $count = count($list);
            if ($count != 0) {
                $list = ['info' => $list,'error' => 0];
            } else {
                $list = ['info' => $list, 'error' => 1];
            }

        } else {
            $list = ['error' => 2];
        }


        $json = json_encode($list);
        echo $json;
    }


    //车型搜索查询
    public function search()
    {
        $pagenum = I('get.pagenum');
        $name = I('get.name');

        if ($name == '') {
            $list = $list = ['error' => 1];
        } else {
            $app = App::getInstance();
            $sql = "SELECT a.id,a.carmodeltype,a.frontimg,b.brand,a.carmodelname,a.agestyle,a.sitecount,a.bearboxtype,a.displacement,a.fuelstand,a.tankcapacity,a.shortdayprice,a.weekdayprice,a.monthdayPrice FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND (a.carmodelname LIKE '%%%s%%') OR (b.brand LIKE '%%%s%%')";
            $countsql = "SELECT COUNT(a.id) FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND (a.carmodelname LIKE '%%%s%%') OR (b.brand LIKE '%%%s%%')";
            $ary = [$name,$name];
            $list = $app->pageDisplay($sql,$countsql,$pagenum,$ary,'count(a.id)',true);
            $count = count($list);
            if ($count != 0) {
                $list = ['carlist' => $list,'error' => 0];
            } else {
                $list = ['carlist' => $list, 'error' => 1];
            }
        }

        $json = json_encode($list);
        echo $json;
    }


    //生成订单方法
    public function orderTrade(){

        $orderNumber = orderCode();//订单号
        $userid = I('post.urserId');//当前用户id
        $carId = I('post.carId');//车辆id
        $typeid = I('post.typeid');//车型id
        $startTime = I('post.startTime');//取车时间
        $endTime = I('post.endTime');//还车时间
        $sendPrize = I('post.sendPrize');//单价
        $allPrize = I('post.allPrize');//交易金额
        $djPrize = I('post.djPrize');//代驾费
        $isdriving = I('post.isdriving');//代驾方式
        $isSendCar = I('post.isSendCar');//取车方式
        $carAD = I('post.carAD');//送车地址
        $couponInfoId = I('post.couponid');//优惠券id
        $table = M('order');
        $costid = '';//明细订单号
        $res = '';//订单提交返回值

        //代驾方式
        if ($isdriving == 1) {
            $isdriving = 1;
        } else {
            $isdriving = 0;
        }
        //取车方式
        if ($isSendCar == 0){
            $isSendCar = 1;
        } else {
            $isSendCar = 2;
        }

        if (empty($couponInfoId)){
            $couponInfoId = 0;
        }

        //计算租车租时长
        $num = strtotime($endTime) - strtotime($startTime);
        $d = floor($num / 3600 / 24);
        $h = floatval($num / 3600);  //%取余
        $h = ceil($h);
        $h = $h % 24;
        $data = $d . "天" . $h . "小时<br>";
        $d += $this->sumData($h);

        /*
         * $limit 租车天数
         * $coupon_act 匹配活动id
         * $member_id 用户id
         *
         * */
        $today =  Date('Ymd',time());
        if ($today == '20170428' || $today == '20170429' || $today == '20170430' || $today == '20170501'){
            $limit = [3,4];
            $coupon_act = [11,12];
            $msg = coupon($limit,$d,$coupon_act,$userid);
        }

        //查询优惠券信息
        if ($couponInfoId !=0){
            $tab = M();
            $sql = "SELECT use_limit,use_condition,discount,coupon_type,money FROM coupon_bollaruser WHERE id=%d";
            $aty = [$couponInfoId];
            $info = $tab->query($sql,$aty);
            if ($info[0]['use_limit'] !=2){             //判断优惠劵是否有使用限制
                switch ($info[0]['use_limit']){
                    case 0:
                        if ($allPrize >= $info[0]['use_condition']){
                            $reg = 1;                   //$reg=1表示该订单符合使用优惠劵的条件
                        }
                        break;
                    case 1:
                        if ($d >= $info[0]['use_condition']){
                            $reg = 1;
                        }
                        break;
                }

            }

            if ($reg){
                switch ($info[0]['coupon_type']){               //判断优惠劵的类型,并计算出优惠价格
                    case 0:
                        $allPrize -= $info[0]['money'];
                        break;
                    case 1:
                        $allPrize *= $info[0]['discount'];
                        break;
                }
            }
        }else{
            $active = coupon_active(2,$userid);        //查询可参与的活动
            if ($active['activity_num'] != 0){          //如果有活动可参与则传入下单时间,用车时间进行判定
                $active_re = couponClass($active['activity'],Date('Y-m-d H:i:s'),$allPrize,$startTime,$endTime);
                if ($active_re[0]['coupon_pass']){
                    if ($active_re[0]['coupon_pass'][2]){   //如果该活动有直接减免的优惠,并且该订单符合该优惠条件,则改变订单总价
                        $allPrize -= $active_re[0]['coupon_pass'][2]['specific'];
                        $grant_coupon = reliefCoupon($userid,$active_re[0]['coupon'.$active_re[0]['coupon_pass'][2]['coupon_num']]);
                        $activity_id = $active_re[0]['id'];
                    }

                    foreach ($active_re[0]['coupon_pass'] as $key => $val){ //对符合条件的优惠方式进行循环
                        $grant[$key] = $active_re[0]['coupon'.$val['coupon_num']];
                    }

                }

            }
        }




        //组装订单数据
        $order = [];
        $order['uid'] = $userid;//用户id
        $order['carmodelid'] = $typeid;//车型id
        $order['car_id'] = $carId;//车辆id
        $order['order_code'] = $orderNumber;//订单号
        $order['order_date'] = Date('Y-m-d H:i:s');//下单时间
        $order['pk_date'] = $startTime;//取车时间
        $order['re_date'] = $endTime;//还车时间
        $order['u_price'] = $sendPrize;//单价
        $order['price_rec'] = $allPrize;//应收金额
        $order['pre_price'] = $allPrize;//预付金额
        $order['driver_price'] = $djPrize;//代驾费
        $order['drive_state'] = $isdriving;//代驾方式
        $order['pk_way'] = $isSendCar;//取车方式
        $order['order_state'] = 0;//订单状态：未支付
        $order['order_type'] = 2;//订单类型 2 表示 APP端订单
        $order['send_location'] = $carAD;//送车地址
        $order['coupon_id'] = $couponInfoId;//是否使用优惠券，0为未使用，1为使用

        $num = $this->impedeOrderSubmit($orderNumber);//阻止订单重复提交
        if (!$num) {

            if ($couponInfoId !=0 && $reg == 1){
                //修改已被使用的优惠券
                $table = M();
                $bollar = "UPDATE coupon_bollaruser SET use_type = 1 WHERE id = %d";
                $bollarId = [$couponInfoId];
                $table->execute($bollar,$bollarId);
            }else if ($active_re[0]['coupon_pass']){
                //发放优惠劵
                $coupun_gent = grantCoupon($userid,$grant);
                if ($coupun_gent['add']){
                    if ($grant_coupon){
                        $grant_coupon .= ','.$coupun_gent['add'];
                    }else{
                        $grant_coupon = $coupun_gent['add'];
                    }

                    $activity_id = $active_re[0]['id'];
                }
            }

            if ($activity_id){      //如果该订单参与了活动则记录参与的活动id
                $order['activity'] = $activity_id;
            }

            if ($grant_coupon){     //如果该订单获得了优惠,则记录获得的id
                $order['grant_coupon'] = $grant_coupon;
            }


            //生成订单
            $table->add($order);
            //修改已被使用的优惠券
            $tables = M();
            $bollar = "UPDATE coupon_bollaruser SET use_type = 1 WHERE id = %d";
            $bollarId = [$couponInfoId];
            $tables->execute($bollar,$bollarId);

            //修改优惠车辆状态
            //修改车辆状态
            $car = M('car_carinfo');
            $info = [];
            $info['usestatus'] = 2;
            $car->where(array('id'=>$carId))->save($info);
            //获取当前订单id
            $orderid = $table->order('id desc')->find();
            //写入明细表
            $cost = M('order_cost');
            //生成明细订单
            $costid = orderCostOrderCode();
            $trade_code = $this->orderCost($costid);
            if (!$trade_code){
                $ordercost = array();
                $ordercost['order_id'] = $orderid['id'];//订单id
                $ordercost['trade_code'] = $costid;//明细订单号
                $ordercost['charge_sum'] = $allPrize;//收费金额
                $ordercost['charge_road'] = 1;//收费途径；预付
                $ordercost['charge_time'] = Date('Y-m-d H:i:s');//收费时间
                $res = $cost->add($ordercost);//生成交易订单
                $data = array(
                    'tradeId'   => $orderid['id'],
                    'ordernum'   => $costid,
                    'tradeMoney' => $allPrize,//$allPrize
                );
                if ($res){
                    $list = array('msg'=>$data,'error'=>0);//error 表示订单提交成功
                }else{
                    $list = array('msg'=>$data,'error'=>1);//error 表示订单提交失败
                }
            }
        }

        $json = json_encode($list);
        echo $json;

    }


    //判断时间周期
    protected function sumData($h)
    {
        $d = 0;
        if ($h < 2) {
            $d = $d;
        } else {
            if ($h > 2 && $h <= 6) {
                $d += 0.5;
            } else {
                if ($h > 6) {
                    $d += 1;
                }
            }
        }
        return $d;
    }

    //阻止重复提交订单
    public function impedeOrderSubmit($ordernum)
    {
        $orderdata = M();
        $sql = "SELECT COUNT(id) FROM `order`  WHERE order_code = '%s'";
        $ary = [$ordernum];
        $list = $orderdata->query($sql, $ary);
        if ($list[0]['count(id)'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    //阻止重复提交订单
    public function orderCost($costid)
    {
        $cost = M();
        $sql = "SELECT COUNT(id) FROM order_cost WHERE (trade_code='%s')";
        $arr = [$costid];
        $trade_code = $cost->query($sql,$arr);
        if ($trade_code[0]['count(id)'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    //订单列表支付
    public function orderPay() {
        $ordernum = I('post.orderid');
        $paytype = I('post.paytype');
        if (!empty($ordernum)){
            $order = M();
            if ($paytype == 'winxin'){
                //生成明细订单
                $costid = orderCostOrderCode();
                $trade_code = $this->orderCost($costid);
                if (!$trade_code){
                    $up = "UPDATE order_cost SET trade_code = '%s' WHERE order_id = %d ";
                    $order->execute($up,[$costid,$ordernum]);
                }
            }

            $sql = "SELECT a.trade_code,a.charge_sum FROM `order_cost` a LEFT JOIN `order` b ON a.order_id = b.id WHERE (b.id = %d) AND (a.pay_way=0)";
            $arr = [$ordernum];
            $total = $order->query($sql,$arr);



            if ($total){
                $list = ['pay'=>$total,'error'=>0];
            } else {
                $list = ['pay'=>$total,'error'=>1];
            }
        } else {
            $list = ['error'=>2];
        }

        $json = json_encode($list);
        echo $json;

    }


    //订单记录
    public function carOrder() {

        $orderClass = I('get.orderclass');
        $pagenum = I('get.pagenum');
        $uid = I('get.urserId');
        $usertype = I('get.usertype');
        $app = App::getInstance();
        $sql = "SELECT a.id,a.order_code,a.order_date,a.pre_price,a.price_rec,a.collections_rec,a.order_state,c.carmodelname,c.agestyle,c.sitecount,c.bearboxtype,c.displacement,c.frontimg,d.brand
FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid WHERE (a.uid = %d)";
        $countSql = "SELECT COUNT(a.id) FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid WHERE (a.uid = %d)";
        if ($usertype == 1){
            //大客户订单记录
            if($orderClass == 0){
                $sql = "SELECT a.id,a.order_state,a.price_rec,a.order_code,a.order_date,a.pre_price,a.collections_rec,a.order_state,c.carmodelname,c.agestyle,c.sitecount,c.bearboxtype,c.displacement,c.frontimg,d.brand,e.pay_way
FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid LEFT JOIN order_cost e ON a.id = e.order_id WHERE (a.uid = %d)";
                $countSql = "SELECT COUNT(a.id) FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid LEFT JOIN order_cost e ON a.id = e.order_id WHERE (a.uid = %d)";
            }  elseif ($orderClass == 2) {
                $sql = "SELECT a.id,a.order_state,a.price_rec,a.order_code,a.order_date,a.pre_price,a.collections_rec,a.order_state,c.carmodelname,c.agestyle,c.sitecount,c.bearboxtype,c.displacement,c.frontimg,d.brand,a.re_date,date_add(a.re_date, interval b.check_cycle MONTH) AS aa,e.pay_way
FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid LEFT JOIN work_member b ON b.id = a.uid LEFT JOIN order_cost e ON a.id = e.order_id
WHERE (a.uid = %d) AND (e.pay_way = 0)  AND (NOW()>date_add(a.re_date, interval b.check_cycle MONTH)) ORDER BY a.id DESC";
                $countSql = "SELECT COUNT(a.id) FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid LEFT JOIN work_member b ON b.id = a.uid LEFT JOIN order_cost e ON a.id = e.order_id
WHERE (a.uid = %d) AND (e.pay_way = 0)  AND (NOW()>date_add(a.re_date, interval b.check_cycle MONTH))";
            } elseif ($orderClass == 3) {
                $sql = "SELECT a.id,a.price_rec,a.order_state,a.order_code,a.order_date,a.pre_price,a.collections_rec,a.order_state,c.carmodelname,c.agestyle,c.sitecount,c.bearboxtype,c.displacement,c.frontimg,d.brand,e.pay_way
FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid LEFT JOIN order_cost e ON a.id = e.order_id WHERE (a.uid = %d) AND (a.order_state >4) ORDER BY a.id DESC";
                $countSql = "SELECT COUNT(a.id) FROM `order` a  LEFT JOIN car_carmodel c ON a.carmodelid = c.id LEFT JOIN car_barand d ON d.id = c.barandid LEFT JOIN order_cost e ON a.id = e.order_id WHERE (a.uid = %d) AND (a.order_state >4) AND (a.order_state >4)";
            }
        } else {
            //普通客户订单记录
            if($orderClass == 0){
                $sql = $sql." ORDER BY a.id DESC";
                $countSql = $countSql;
            } elseif ($orderClass == 1) {
                $sql = $sql." AND (a.order_state =0) ORDER BY a.id DESC";
                $countSql = $countSql." AND (a.order_state =0)";
            } elseif ($orderClass == 2) {
                $sql = $sql." AND ((a.order_state =3) OR (a.order_state =1)) ORDER BY a.id DESC";
                $countSql = $countSql." AND (a.order_state =3) OR (a.order_state =1)";
            } elseif ($orderClass == 3) {
                $sql = $sql." AND (a.order_state >4) ORDER BY a.id DESC";
                $countSql = $countSql." AND (a.order_state >4)";
            }
        }
        $arr = [$uid];
        $list = $app->pageDisplay($sql,$countSql,$pagenum,$arr,'count(a.id)',true);

        $count = count($list);
        if ($count != 0) {
            $list = ['orderlist' => $list,'error' => 0];
        } else {
            $list = ['orderlist' => $list, 'error' => 1];
        }
        $json = json_encode($list);
        echo $json;
    }

    //订单详情
    public function orderPage() {
        $orderid = I('get.id');
        $order = M();
        $sql = "SELECT a.car_id,a.pre_price as ordersum,a.check_out,a.dp_price,a.in_cost,a.oil_price,a.in_price,a.in_dep,a.tolls,a.in_code,a.wash_price,a.re_price,a.collections_rec,a.u_price,
a.order_code,a.price_rec,a.pk_date,a.re_date,a.check_out,a.driver_price,a.order_state,a.drive_state,a.pk_way,a.send_location,b.username,c.carmodeltype,c.frontimg,c.carmodelname,c.displacement,c.agestyle,c.sitecount,c.bearboxtype,d.carno,e.brand 
FROM `order` a  LEFT JOIN work_member b ON a.uid = b.id 
LEFT JOIN car_carmodel c ON a.carmodelid = c.id 
LEFT JOIN car_carinfo d ON a.car_id = d.id LEFT JOIN car_barand e ON c.barandid = e.id 
WHERE (a.id = %d)";
        $arr = [$orderid];
        $list = $order->query($sql,$arr);
        $strAry = $this->carADHandle($list[0]['send_location']);
        $list[0]['send_location'] = $strAry[0][0];
        //查询订单交易信息
        $orderInfo = "SELECT a.trade_code,a.charge_time,a.charge_sum,a.charge_road,a.pay_way FROM order_cost a LEFT JOIN `order` b ON a.order_id = b.id WHERE (b.id=%d)";
        $ary = [$orderid];
        $tradeInfo = $order->query($orderInfo,$ary);

        foreach ($tradeInfo as $key => $value){

            if ($value['charge_road'] == 1 )
            {
                $charge_road = '预付';
            } elseif ($value['charge_road'] == 2){
                $charge_road = '结账';
            } elseif ($value['charge_road'] == 3){
                $charge_road = '补交';
            } elseif ($value['charge_road'] == 4){
                $charge_road = '退款';
            } elseif ($value['charge_road'] == 5){
                $charge_road = '交违章押金';
            } elseif ($value['charge_road'] == 6){
                $charge_road = '退违章押金';
            } elseif ($value['charge_road'] == 7){
                $charge_road = '补交违章押金';
            }
            //判断支付状态
            if ($value['pay_way'] != 0 ){
                $pay_way = '已支付';
            } else {
                $pay_way = '未支付';
            }
            $value['charge_road'] = $charge_road;
            $value['pay_way'] = $pay_way;
            $arr['trade'][] = $value;

        }

        //计算租车租时长
        $num = strtotime($list[0]['re_date']) - strtotime($list[0]['pk_date']);
        $d = floor($num / 3600 / 24);
        $h = floatval($num / 3600);  //%取余
        $h = ceil($h);
        $h = $h % 24;
        $data = $d . "天" . $h . "小时";
        //取车时间
        $pk_date = strtotime($list[0]['pk_date']);
        $pk_date = date("Y年m月d日 H时i分", $pk_date);
        //还车时间
        $re_date = strtotime($list[0]['re_date']);
        $re_date = date("Y年m月d日 H时i分", $re_date);
        //判断驾驶方式
        if ( $list[0]['pk_way'] == 1) {
            $pk_way = '否';
        } else {
            $pk_way = '是';
        }

        //判断取车方式
        if ( $list[0]['drive_state'] == 0) {
            $drive_state = '否';
        } else {
            $drive_state = '是';
        }
        $list[0]['pk_way'] = $pk_way;
        $list[0]['drive_state'] = $drive_state;
        $list[0]['time'] = $data;
        $list[0]['pk_date'] = $pk_date;
        $list[0]['re_date'] = $re_date;
        $list[0]['trade'] = $arr['trade'];
        $json = json_encode(array('order'=>$list));
        echo $json;


    }

    //取消订单 offOrder
    public function offOrder() {
        $id = I('get.id');
        //更改订单状态
        $order = M('order');
        $sql = "SELECT car_id FROM `order` WHERE (id = %d)";
        $arr = [$id];
        $carid = $order->query($sql,$arr);

        //判断订单是否是优惠车辆类型
        if ($carid[0]['car_id'] == 0){
            $data = [];
            $data['order_state'] = 10;
            if ($order->where(array('id=' . $id))->save($data)) {
                $list = ['error' => 0];
                cancelCoupon($id);  //修改订单获取的优惠信息
            } else {
                $list = ['error' => 1];
            }
        } else {
            $data = [];
            $data['order_state'] = 10;
            if ($order->where(array('id=' . $id))->save($data)) {
                $carinfo = M('car_carinfo');
                //查询订单对应的车辆信息
                $sql = "SELECT car_id FROM `order` WHERE (id = %d)";
                $arr = [$id];
                $carid = $order->query($sql,$arr);
                //更改优惠车辆状态
                $car = array();
                $car['usestatus'] = 0;
                if ( $carinfo->where(array('id='.$carid[0]['car_id']))->save($car)){
                    $list = ['error' => 0];
                    cancelCoupon($id);  //修改订单获取的优惠信息
                } else {
                    $list = ['error' => 1];
                }

            } else {
                $list = ['error' => 1];
            }
        }
        $json = json_encode($list);
        echo $json;
    }

    //退款申请方法 refundApply
    public function refundApply() {
        $id = I('get.id');
        $order = M('order');
        $data = [];
        $data['order_state'] = 11;
        if ($order->where(array('id=' . $id))->save($data)) {
            $list = ['error' => 0];
        } else {
            $list = ['error' => 1];
        }
        $json = json_encode($list);
        echo $json;
    }

    //获取服务器时间接口
    public function getTime(){
        $time = date("Y-m-d H:i:s", time());
        $json = ['date' => $time];
        echo json_encode($json);
    }

    //移动网站支付宝支付
    public function wapPay(){

        $orderId = I('get.tradeId');
        $tab = M();
        $sql = "SELECT a.trade_code,a.charge_sum FROM `order_cost` a LEFT JOIN `order` b ON a.order_id = b.id WHERE b.id = %d";
        $arr = [$orderId];
        $list = $tab->query($sql,$arr);
        //调用支付宝配置
        $alipay_config=C('WapPayConfig');
        /**************************请求参数**************************/

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $list[0]['trade_code'];

        //订单名称，必填
        $subject = '华邦租车';

        //付款金额，必填
        $total_fee = $list[0]['charge_sum'];

        //收银台页面上，商品展示的超链接，必填
        $show_url = "#";//$_POST['WIDshow_url'];

        //商品描述，可空
        $body = $_POST['WIDbody'];



        /************************************************************/

//构造要请求的参数数组，无需改动
        $parameter = array(
            "service"       => $alipay_config['service'],
            "partner"       => $alipay_config['partner'],
            "seller_id"  => $alipay_config['seller_id'],
            "payment_type"	=> $alipay_config['payment_type'],
            "notify_url"	=> $alipay_config['notify_url'],
            "return_url"	=> $alipay_config['return_url'],
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
            "out_trade_no"	=> $out_trade_no,
            "subject"	=> $subject,
            "total_fee"	=> $total_fee,
            "show_url"	=> $show_url,
            "app_pay"	=> "Y",//启用此参数能唤起钱包APP支付宝
            "body"	=> $body,
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.2Z6TSk&treeId=60&articleId=103693&docType=1
            //如"参数名"	=> "参数值"   注：上一个参数末尾需要“,”逗号。

        );

//建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"post", "确认");
        echo $html_text;

    }



    //优惠券列表接口
    public function coupon(){
        $uid = I('get.uid');
        $pagenum = I('get.pagenum');
        $type = I('get.type');
        $app = App::getInstance();
        //账户解密
//        $sql = "SELECT b.id,a.coupon_name,a.info,a.imgurl,b.money,b.discount,b.use_limit,b.use_condition,b.termofvalidity,b.addtime,b.use_type,b.coupon_type,current_timestamp() as nowtime
//FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid LEFT JOIN work_member c ON b.uid=c.id WHERE (c.id=%d)";
//        $countSql = "SELECT COUNT(b.id) FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid LEFT JOIN work_member c ON b.uid=c.id WHERE (c.id=%d)";
        $sql = "SELECT b.id,a.coupon_name,a.info,b.money,b.discount,b.use_limit,b.use_condition,b.termofvalidity,b.addtime,b.use_type,b.coupon_type,current_timestamp() as nowtime 
FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid LEFT JOIN work_member c ON b.uid=c.id WHERE c.id='%s' AND b.coupon_type!=2 AND b.is_del=0  ";
        $countSql = "SELECT COUNT(b.id) FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid LEFT JOIN work_member c ON b.uid=c.id WHERE c.id='%s' AND b.coupon_type!=2 AND b.use_type=0 AND b.is_del=0";

        if ($type == 0){
            $sql = $sql ."ORDER BY b.id DESC ";
            $countSql = $countSql;
        } elseif ($type == 1){
            $sql = $sql ."AND (b.use_type = 0) AND (current_timestamp() < b.termofvalidity) ORDER BY b.id DESC ";
            $countSql = $countSql . "AND (b.use_type = 0) AND (current_timestamp() < b.termofvalidity) ";
        } elseif ($type == 2){
            $sql = $sql ."AND (b.use_type = 1) ORDER BY b.id DESC ";;
            $countSql = $countSql . "AND (b.use_type = 1)";;
        } elseif ($type == 3){
            $sql = $sql ."AND (current_timestamp() > b.termofvalidity ) AND (b.use_type=0) ORDER BY b.id DESC ";
            $countSql = $countSql . "AND (current_timestamp() > b.termofvalidity ) AND (b.use_type=0)";
        }
        $arr = [$uid];
        $list = $app->pageDisplay($sql,$countSql,$pagenum,$arr,'count(a.id)',true);
        echo json_encode($list);

    }
    //查询优惠券介绍信息
    public function couponInfo(){
        $tab = M();
        $id = I('get.id');
        $sql = "SELECT a.info FROM coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid WHERE b.id=%d";
        $arr = [$id];
        $list = $tab->query($sql,$arr);
        echo json_encode($list);

    }

    //优惠券可使用列表
    public function useCoupon(){
        $uid = I('get.uid');
        $tab = M();
        $sql = "SELECT b.id,a.coupon_name,b.money,b.use_limit,b.use_condition,b.coupon_type,b.discount FROM 
coupon_bollar a LEFT JOIN coupon_bollaruser b ON a.id=b.bid  LEFT JOIN work_member c ON b.uid=c.id 
WHERE (c.id=%d) AND (b.use_type=0) AND (current_timestamp() < b.termofvalidity || b.termofvalidity ='0000-00-00 00:00:00') AND coupon_type != 2";
        $where = [$uid];
        $couponInfo = $tab->query($sql,$where);
        $time =  Date('Ymd',time());
        $cou = $this->test($time);
        if (($cou[$time] == 0 || $cou[$time] == 1)){
            echo json_encode($couponInfo);
        }

    }

    //获取当前移动端版本号
    public function version(){
        $tab =  M();
        $sql =  "SELECT version FROM system_site";
        $info = $tab->query($sql);
        echo json_encode(array('webversion'=>$info));
    }
    public function test($data){
        $url = "http://www.easybots.cn/api/holiday.php?d=$data";
        $jsonStr = json_encode(array());
        $list = $this->http_post_json($url, $jsonStr);
        $array1=json_decode($list[1],true) ;
        return $array1;
    }

    /**
     * PHP发送Json对象数据
     *
     * @param $url 请求url
     * @param $jsonStr 发送的json字符串
     * @return array
     */
    function http_post_json($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return array($httpCode, $response);
    }


    //预约成功发送短信提示内容
    /**发送短信接口
     * @return integer
     */
    public function bespokeOrderSuccess()
    {
        $orderCode = I('get.id');//订单号
        $res = $this->carSide($orderCode);
        $create = date('Y-m-d H:i',$_SERVER['REQUEST_TIME']);//创建当前时间
        $url 		= "http://www.api.zthysms.com/sendSms.do";//提交地址
        $username 	= 'huabanghy';//用户名
        $password 	= 'Ujwidz';//原密码
        $sendAPI = new sendAPI($url, $username, $password);

        if (empty($res[0]['usephone'])){
            $usertel = $res[0]['orderphone'];
            $content = '尊敬的用户:您在'.$create.'成功预约华邦专车服务，订单正在受理中，请保持电话畅通。【华邦出行】';//57字
            $data = array(
                'content' 	=> $content,//短信内容
                'mobile' 	=> $usertel,//手机号码
                'xh'		=> ''//小号
            );
            $sendAPI->data = $data;//初始化数据包
            $return = $sendAPI->sendSMS('POST');//GET or POST
            $result = explode(',', $return);
            if ($result[0] == 1) {
                $this->ajaxReturn(array('state' => 1));
            } else {
                $this->ajaxReturn(array('state' => 0));
            }

        } else {
            $usertel = $res[0]['usephone'];
            $content = '尊敬的用户:您的朋友'.$res[0]['orderphone'].'在'.$create.'成功为您预订华邦专车服务，订单正在受理中，请保持电话畅通。【华邦出行】';//73字
            $data = array(
                'content' 	=> $content,//短信内容
                'mobile' 	=> $usertel,//手机号码
                'xh'		=> ''//小号
            );

            $sendAPI->data = $data;//初始化数据包
            $return = $sendAPI->sendSMS('POST');//GET or POST
            $result1 = explode(',', $return);
            $data = array(
                'content' 	=> '尊敬的用户:您在'.$create.'已经成功为你的朋友'.$res[0]['usephone'].'预定华邦专车服务，感觉您的支持。【华邦出行】',//短信内容 64字
                'mobile' 	=> $res[0]['orderphone'],//手机号码
                'xh'		=> ''//小号
            );
            $sendAPI->data = $data;//初始化数据包

            $return = $sendAPI->sendSMS('POST');//GET or POST
            $result = explode(',', $return);
            if ($result[0] == 1 && $result1[0] == 1) {
                $this->ajaxReturn(array('state' => 1));
            } else {
                $this->ajaxReturn(array('state' => 0));
            }
        }

    }

    //判断用车方
    public function carSide($id){
        $tab = M();
        $sql = "SELECT UsePhone,OrderPhone FROM order_netcar WHERE OrderCode = '%s' ";
        $list = $tab->query($sql,[$id]);
        return $list;
    }


    //网约车支付宝回调
    public function netCarPay(){
        $orderCode = I('get.ordercode');
        $paySum = I('get.paysum');
        $payType = I('get.paytype');//1为支付宝；2为微信
        if ($payType == 2){
            $paySum = $paySum / 100;
        }
        $tab = M();
        $order = "UPDATE order_netcar SET OrderState = %d WHERE OrderCode='%s'";
        $orderArr = [5,$orderCode];
        $res = $tab->execute($order,$orderArr);

        $sql = "UPDATE order_settlement SET ActualPay='%s',State=%d,PayType=%d WHERE OrderCode='%s'";
        $arr = [$paySum,1,$payType,$orderCode];
        $re = $tab->execute($sql,$arr);

        if ($res > 0 && $re > 0){
            $sms = $this->returnSMS($orderCode,$paySum);
            if ($sms){
                $list = array('Callback'=>0);//回调执行成功
            }
        } else {
            $list = array('Callback'=>1);//回调执行失败
        }
        $json = json_encode($list);
        echo $json;
    }

    //网约车订单支付完成短信回馈
    public function returnSMS($orderCode,$paySum){
        $tab = M();
        $sql = "SELECT OrderPhone,UsePhone FROM order_netcar WHERE OrderCode ='%s' ";
        $arr = [$orderCode];
        $res = $tab->query($sql,$arr);
        if (empty($res[0]['usephone'])){
            $usertel = $res[0]['orderphone'];
        } else {
            $usertel = $res[0]['usephone'];
        }

        $url 		= "http://www.api.zthysms.com/sendSms.do";//提交地址
        $username 	= 'huabanghy';//用户名
        $password 	= 'Ujwidz';//原密码
        $sendAPI = new sendAPI($url, $username, $password);
        $data = array(
            'content' 	=> '尊敬的用户:您当前订单消费金额为:'.$paySum.'元，感谢您支持【华邦出行】。',//短信内容 32-36字
            'mobile' 	=> $usertel,//手机号码
            'xh'		=> ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $return = $sendAPI->sendSMS('POST');//GET or POST
        $result = explode(',', $return);
        if ($result[0] == 1) {
            return true;
        } else {
            return false;
        }
    }

    //接收车辆或驾驶员信息并和数据库中的数据进行比对,将不一致的信息进行记录
    public function netCarTest($data,$type,$condition){
        if ($type == 1){
            $sql = "SELECT * FROM car_net a LEFT JOIN car_info b ON a.id=b.CarId WHERE a.VehicleNo = '%s'";
            $list = M()->query($sql,[$condition])[0];
            foreach ($data as $key => $val){
                if ($val != $list[$key]){
                    $check[$key] = $val;
                }
            }

            if ($check){
                $check['CarId'] = $list['id'];
                $check['VehicleNo'] = $list['vehicleno'];
                $check['addtime'] =  Date('Y-m-d H:i:s',time());
                M('report_car_error')->add($check);
            }
        }else{
            $sql = "SELECT * FROM car_driverinfo a LEFT JOIN car_drivermore b ON a.id=b.DriverId WHERE b.LicenseId = '%s'";
            $list = M()->query($sql,[$condition])[0];
            foreach ($data as $key => $val){
                if ($val != $list[$key]){
                    $check[$key] = $val;
                }
            }

            if ($check){
                $check['DriverId'] = $list['id'];
                $check['drivername'] = $list['drivername'];
                $check['LicenseId'] = $list['licenseid'];
                $check['addtime'] =  Date('Y-m-d H:i:s',time());
                M('report_car_error')->add($check);
            }
        }
    }

    public function MonitorServerState(){
        $url 		= "http://www.api.zthysms.com/sendSms.do";//提交地址
        $username 	= 'huabanghy';//用户名
        $password 	= 'Ujwidz';//原密码
        $sendAPI = new sendAPI($url, $username, $password);
        $usertel = $_POST['phone'];//接受者电话
        $content = $_POST['content'].'【华邦出行】';//57字
        $data = array(
            'content' 	=> $content,//短信内容
            'mobile' 	=> $usertel,//手机号码
            'xh'		=> ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $return = $sendAPI->sendSMS('GET');//GET or POST
        $result = explode(',', $return);

        if ($result[0] == 1) {
            $this->ajaxReturn(array('phone'=>$usertel));
        } else {
            $this->ajaxReturn(array('state' => 0));
        }
    }

    public function testSub()
    {
        $this->infoSub('1708031040150474');
    }

    //车辆基本信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('operatePay',$id);
    }
}