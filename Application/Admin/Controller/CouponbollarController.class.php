<?php

namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
class CouponbollarController extends BaseController {
    //访问数据库    定义为静态方法
    static function CouponbollarModel(){
        return $couponModel = M('CouponBollar');
    }
    static $CouponBollar_table = 'CouponBollar';

    static  function relse(){
        return $rules = array(
            array('type','require','优惠类型不能为空！',0), //默认情况下用正则进行验证
            array('coupon_name','require','优惠名称不能为空！',0), //默认情况下用正则进行验证
            array('number','require','优惠可领次数不能为空！',0), //默认情况下用正则进行验证
            array('limit','require','规定客户领取数量不能为空！',0), //默认情况下用正则进行验证
            array('limit','isNumber','每位客户限领数需为纯数字！',0,'function'),
            array('number','isNumber','优惠可领次数需填写整数！',0,'function'),

            /*array('name','require','真实姓名不能为空！',0), //默认情况下用正则进行验证
            array('Password','require','登录密码不能为空！',0), //默认情况下用正则进行验证
            array('Password ','6,16','密码必须为5~16位之间！',0,'length'), // 验证标题长度
            array('departmentid','require','所属部门不能为空！',0), //默认情况下用正则进行验证
            array('type','require','用户类型不能为空！',0), //默认情况下用正则进行验证
            array('sex','require','性别必填',0), //默认情况下用正则进行验证
            array('identity','require','身份证号不能为空',0), //默认情况下用正则进行验证
            array('phonenumber','require','联系方式不能为空',0), //默认情况下用正则进行验证
            array('phonenumber','/^(0|86|17951)?(13[0-9]|15[012356789]|18[0-9]|14[57])[0-9]{8}$/','非法手机号码',0),
            array('identity', '/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/', '身份证号不合法！', 0),
            array('identity','','身份证号已经存在！',0,'unique',0), // 在新增的时候验证name字段是否唯一
            //array('username','','此账户已存在！',0,'unique',0), // 在新增的时候验证name字段是否唯一*/
        );
    }

    //查看优惠方式列表
    public function index(){
//        $ary = [0,1,2,3];
//        $test = array_key_exists(4,$ary);
//        var_dump($test);
        $sql = "SELECT * FROM coupon_bollar WHERE is_del=0 ";
        $countSql = "SELECT count(id) FROM coupon_bollar WHERE is_del=0";
        $ary = array();
        if(!empty($_POST)){
            $msg['select'] = I('post.select');
            $msg['key'] = I('post.key');
            $this->assign('msg',$msg);
            $ary=[$msg['key']];
            switch ($msg['select']){
                case 0:
                    $sql_con = " AND coupon_name LIKE '%%%s%%'";
                    break;
                case 1:
                    $sql_con = " AND coding LIKE '%%%s%%'";
                    break;
                case 2:
                    $sql_con = " AND id='%d'";
                    break;
            }

        }
        $sql .= $sql_con . " ORDER BY id DESC";
        $countSql .= $sql_con;
        $pageNum = 16;              //每页显示数量
        $this->pageDisplay($sql,$countSql,$pageNum,$ary,'count(id)','list','page',true);
        $this->assign('sex',$this->sexArray);
        $this->display();
    }
    //添加优惠劵类型
    public function add(){
        $Db=self::CouponbollarModel();
        $rules=self::relse();
        if(!empty($_POST)){

            if (!$Db->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$Db->getError().'.");history.go(-1);</script>');
            }else {

                $data['coupon_name'] = I('post.coupon_name');
                $data['info'] = I('post.info');
                $data['addtime'] = date('Y-m-d');//添加时间
                $data['number'] = I('post.number');
                $data['type'] = I('post.type');
                $data['money'] = I('post.money');
                $data['discount'] = I('post.discount');
                $data['use_limit'] = I('post.use_limit');
                $data['use_condition'] = I('post.use_condition');
                $data['condition_limit'] = I('post.condition_limit');
                $data['coupon_condition'] = I('post.coupon_condition');
//                $data['limit'] = I('post.limit');
                $data['time_limit'] = I('post.time_limit');
                $data['termofvalidity'] = I('post.termofvalidity');
                $data['termofvaliditytian'] = I('post.termofvaliditytian');

                $res = $this->coupon_rules($data);

                $add = $Db->add($res);
                if ($add) {
                    exit('<script type="text/javascript">alert("添加成功");location.href="' . U('Couponbollar/Index') . '";</script>');
                } else {
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }
        $this->display();
    }
    //查看领取人
    public function user(){
        if (isset($_GET['id'])){
            $m = M();
            $id = I('get.id');
            $sql = "SELECT a.id,a.addtime as receive_time,a.termofvalidity,a.use_type,b.username,b.phone,c.condition_limit,c.coupon_condition,c.type,c.issue_state,c.money,c.discount  FROM coupon_bollaruser a LEFT JOIN work_member b ON a.uid=b.id LEFT JOIN coupon_bollar c ON a.bid=c.id WHERE c.id = '%s' AND a.use_type != 2";
            $ary = [$id];
            $countSql = "SELECT count(a.id) FROM coupon_bollaruser a LEFT JOIN work_member b ON a.uid=b.id LEFT JOIN coupon_bollar c ON a.bid=c.id WHERE c.id = '%d'AND a.use_type != 2";
            if (isset($_POST['select'])){
//                var_dump($_POST);
//                exit;
                $msg['select'] = I('post.select');
                switch ($msg['select']){
                    case '0':
                        $sql_con = " AND b.username LIKE '%%%s%%'";
                        $msg['key'] = $ary[] = I('post.key');
                        break;
                    case '1':
                        $sql_con = " AND a.addtime BETWEEN '%s' AND '%s'";
                        $ary[] = $msg['start'] = I('post.start');
                        $ary[] = $msg['stop'] = I('post.stop');
                        break;
                    case '2':
                        $sql_con = " AND a.type = '%d'";
                        $ary[] = $msg['use_key'] = I('post.use_key');
                        break;
                }
                $this->assign('msg',$msg);
            }
            $sql .= $sql_con." ORDER BY id DESC";
            $countSql .= $sql_con;
            $this->pageDisplay($sql,$countSql,16,$ary,'count(a.id)','list','page',true);
            $this->display();
        }
    }
    //发行优惠劵或禁发优惠劵
    public function issue(){
        if (isset($_GET['id'])){
            $Db=self::CouponbollarModel();
            $id = I('get.id');
            $issue = I('get.issue');
            //接收当前管理员登陆名
            $auserInfo = UserInfo();

            if ($issue){
                $coupon_bollar = $Db->where(array('id'=>$id))->field('state')->find();
                if ($coupon_bollar['state'] == 1){
                    exit('<script type="text/javascript">alert("该优惠劵已被禁用无法发行！");history.go(-1);</script>');
                }
                $reg = $Db->where(array('id'=>$id))->save(array('issue_state'=>$issue));
                if ($reg){
                    $log = self::writeLog('coupon_bollar', $id, '发行优惠劵', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        exit('<script type="text/javascript">alert("发行成功！");location.href="' . U('Index') . '";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("发行失败，请重试！");history.go(-1);</script>');
                }
            }else{
                $reg = $Db->where(array('id'=>$id))->save(array('issue_state'=>$issue));
                if ($reg){
                    $log = self::writeLog('coupon_bollar', $id, '禁发优惠劵', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        exit('<script type="text/javascript">alert("禁发成功！");location.href="' . U('Index') . '";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("禁发失败，请重试！");history.go(-1);</script>');
                }
            }
        }
    }
    //发行优惠劵或禁发优惠劵
    public function state(){
        if (isset($_GET['id'])){
            $Db=self::CouponbollarModel();
            $id = I('get.id');
            $state = I('get.state');
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            if ($state){
                $coupon_bollar = $Db->where(array('id'=>$id))->field('issue_state')->find();
                if ($coupon_bollar['issue_state'] == 1){
                    exit('<script type="text/javascript">alert("该优惠劵正在发行中无法禁用！");history.go(-1);</script>');
                }
                $sql = " UPDATE coupon_bollar SET `state` = 1 WHERE id ={$id}";
//                $reg = $Db->where(array('id'=>$id))->save(array('state'=>1));
                $reg = $Db->execute($sql);
                if ($reg){
                    $log = self::writeLog('coupon_bollar', $id, '启用优惠劵', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        exit('<script type="text/javascript">alert("禁用成功！");location.href="' . U('Index') . '";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("禁用失败，请重试！");history.go(-1);</script>');
                }
            }else{

                $reg = $Db->where(array('id'=>$id))->save(array('state'=>0));
                if ($reg){
                    $log = self::writeLog('coupon_bollar', $id, '禁用优惠劵', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        exit('<script type="text/javascript">alert("启用成功！");location.href="' . U('Index') . '";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("启用失败，请重试！");history.go(-1);</script>');
                }
            }
        }
    }
    //修改优惠劵
    public function edit(){
        $Db=self::CouponbollarModel();
        if(!empty($_POST)){
            if ($_FILES['imgurl']['error'] === 0){
                // 实例化上传类
                $upload = new \Think\Upload();
                // 设置附件上传大小（300KB）
                $upload->maxSize = 307200;
                // 设置附件上传类型
                $upload->exts = array('jpg', 'png', 'jpeg');
                // 设置附件上传目录
                $upload->savePath = '/Uploads/coupon_img/';
                //设置子目录
                $upload->autoSub = true;
                //同名则替换
                $upload->uploadReplace = true;
                // 上传文件
                $info = $upload->upload();
                // 上传错误提示错误信息
                if (!$info) {
                    $this->error($upload->getError());
                }
            }
            $rules = self::relse();
            if (!$Db->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$Db->getError().'.");history.go(-1);</script>');
            }else{
                $data['andtime']=I('post.andtime');//开始领取时间
                $data['endtime']=I('post.endtime');
                $data['type']=I('post.type');      //优惠劵类型（现金卷，折扣卷）
                switch ($data['type']){
                    case 0:
                        if (I('post.money') == ''){exit('<script type="text/javascript">alert("优惠金额不能为空");history.go(-1);</script>');}
                        else{$data['money']=I('post.money');}
                        break;
                    case 1:
                        if (I('post.discount') == ''){exit('<script type="text/javascript">alert("折扣率不能为空");history.go(-1);</script>');}
                        else{$data['discount']=I('post.discount');}
                        break;
                }
                $data['time_limit']=I('post.time_limit');
                switch ($data['time_limit']){
                    case 0:
                        if (I('post.termofvalidity') == ''){exit('<script type="text/javascript">alert("截止日期不能为空");history.go(-1);</script>');}
                        else{$data['termofvalidity']=I('post.termofvalidity');}
                        break;
                    case 1:
                        if (I('post.termofvaliditytian') == ''){exit('<script type="text/javascript">alert("有效天数不能为空");history.go(-1);</script>');}
                        else{$data['termofvaliditytian']=I('post.termofvaliditytian');}
                        break;
                }
                $savepath = $info['imgurl']['savepath'];                   //获取广告路径
                $data['imgurl'] =  $savepath . $info['imgurl']['savename'];
                $data['coupon_name'] = I('post.coupon_name');
                $data['number']=I('post.number');
                $data['min_consume']=I('post.min_consume');
                $data['limit']=I('post.limit');
                $data['number']=I('post.number');
                $data['info']=I('post.info');

                $Bollarsave=$Db->where(array('id'=>$_GET['id']))->save($data);
                if($Bollarsave){
                    exit('<script type="text/javascript">alert("修改成功");location.href="'.U('Couponbollar/Index').'";</script>');

                }else{
                    exit('<script type="text/javascript">alert("修改失败");history.go(-1);</script>');
                }
            }

        }else{
            if (isset($_GET['id'])){
                $CouponbollarFind=$Db->where(array('id'=>$_GET['id']))->find();
                $this->assign('list',$CouponbollarFind);
                $this->display('add');
            }else{
                exit('<script type="text/javascript">alert("参数错误");history.go(-1);</script>');
            }

        }

    }
    //删除优惠劵类型
    public function  delete(){
        $id = $_GET['id'];
        if(!empty($id)){
            $Db=self::CouponbollarModel();
            $deleUser = $Db->where(array('id'=>$id))->save(array('is_del'=>1));
            if($deleUser){
                exit('<script type="text/javascript">alert("删除成功");location.href="'.U('Couponbollar/Index').'";</script>');
            }else{
                exit('<script type="text/javascript">alert("删除失败");history.go(-1);</script>');
            }
        }
    }

    function isNumber($num){
        return is_numeric($num);
    }

    function coupon_rules($data){

        switch ($data['type']){
            case '0':
                if (!is_numeric($data['money']) || $data['money'] <= 0){
                    exit('<script type="text/javascript">alert("优惠金额填写错误");history.go(-1);</script>');
                }
                unset($data['discount']);
                break;
            case '1':
                if (!$data['discount'] || ($data['discount'] <= 0 && $data['discount'] >= 1) || !is_numeric($data['discount'])){
                    exit('<script type="text/javascript">alert("折扣率填写错误");history.go(-1);</script>');
                }
                unset($data['money']);
                break;
            case '2':
                if ($data['money'] <= 0 || !is_numeric($data['money'])){
                    exit('<script type="text/javascript">alert("优惠金额填写错误");history.go(-1);</script>');
                }
                unset($data['discount']);
                break;
        }

        if ($data['time_limit'] == 2){
            unset($data['termofvaliditytian']);
            unset($data['termofvalidity']);
        }else{
            if($data['time_limit'] == 0){
                if(!$data['termofvalidity']){
                    exit('<script type="text/javascript">alert("限时时间不能为空");history.go(-1);</script>');
                }
                unset($data['termofvaliditytian']);
            }
            if($data['time_limit'] == 1){
                if(!($data['termofvaliditytian'])){
                    exit('<script type="text/javascript">alert("限时天数不能为空");history.go(-1);</script>');
                }
                if (!is_numeric($data['termofvaliditytian']) || $data['termofvaliditytian'] <= 0){
                    exit('<script type="text/javascript">alert("限时天数需为正整数");history.go(-1);</script>');
                }
                unset($data['termofvalidity']);
            }
        }

        if ($data['condition_limit'] ==2){
            unset($data['coupon_condition']);
        }else{
            if (!is_numeric($data['coupon_condition']) || $data['coupon_condition'] <= 0){
                exit('<script type="text/javascript">alert("最低租车时长或最低消费填写错误");history.go(-1);</script>');
            }
        }


        return $data;
    }

}