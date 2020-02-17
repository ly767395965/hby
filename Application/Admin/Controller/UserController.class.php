<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
class UserController extends BaseController {

    public $sexArray = array(0,'男','女');

    static function UserModel(){
        return $userModel = M('AdminUser');
    }
    static  function relse(){
        return $rules = array(
            array('username','require','用户账户不能为空！',0), //默认情况下用正则进行验证
            array('name','require','真实姓名不能为空！',0), //默认情况下用正则进行验证
            array('Password','require','登录密码不能为空！',0), //默认情况下用正则进行验证
            array('Password ','6,16','密码必须为5~16位之间！',0,'length'), // 验证标题长度
            array('departmentid','require','所属部门不能为空！',0), //默认情况下用正则进行验证
            array('type','require','用户类型不能为空！',0), //默认情况下用正则进行验证
            array('sex','require','性别必填',0), //默认情况下用正则进行验证
            array('identity','require','身份证号不能为空',0), //默认情况下用正则进行验证
            array('phonenumber','require','联系方式不能为空',0), //默认情况下用正则进行验证
            array('phonenumber','/^(0|86|17951)?(13[0-9]|15[012356789]|18[0-9]|14[57])[0-9]{8}$/','非法手机号码',0),
            array('identity', '/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/', '身份证号不合法！', 0),
//            array('identity','','身份证号已经存在！',0,'unique',0), // 在新增的时候验证name字段是否唯一
//            array('username','','此账户已存在！',0,'unique',0), // 在新增的时候验证name字段是否唯一
        ) ;
    }
    //代理商数据判断
    static  function agent_rules(){
        return $rules = array(
            array('name','require','代理商名称不能为空！',0), //默认情况下用正则进行验证
            array('people','require','联系人不能为空！',0), //默认情况下用正则进行验证
            array('identitys','require','身份证号不能为空',0), //默认情况下用正则进行验证
            array('phone','require','联系方式不能为空',0), //默认情况下用正则进行验证
            array('phone','/^(0|86|17951)?(13[0-9]|15[012356789]|18[0-9]|14[57])[0-9]{8}$/','非法手机号码',0),
            array('identitys', '/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/', '身份证号不合法！', 0),
//            array('identitys','','身份证号已经存在！',0,'unique',0), // 在新增的时候验证name字段是否唯一
//            array('username','','此账户已存在！',0,'unique',0), // 在新增的时候验证name字段是否唯一
        ) ;
    }
    //查看员工信息
    public function index(){
        if(isset($_POST['demand'])){
            $name = I('post.demand');
            $sql="SELECT a.*,b.name AS agent_name FROM admin_user a LEFT JOIN car_agent b ON a.agent_id=b.id WHERE a.name Like '%%%s%%'";
            $countSql="SELECT count(a.id) FROM admin_user a LEFT JOIN car_agent b ON a.agent_id=b.id WHERE a.name Like '%%%s%%'";
            $ary = array($name);
        }elseif (isset($_POST['agent'])){
            $agent = I('post.agent');
            $sql="SELECT a.*,b.name AS agent_name FROM admin_user a LEFT JOIN car_agent b ON a.agent_id=b.id WHERE b.name Like '%%%s%%'";
            $countSql="SELECT count(a.id) FROM admin_user a LEFT JOIN car_agent b ON a.agent_id=b.id WHERE b.name Like '%%%s%%'";
            $ary = array($agent);
        }else{
            $sql = "SELECT a.*,b.name AS agent_name FROM admin_user a LEFT JOIN car_agent b ON a.agent_id=b.id ";
            $countSql = "SELECT count(id) FROM admin_user";
            $ary = array();
        }


//        $this->pageDisplay($sql,$countSql,$pageNum, $keyName,$listName,$showPage,$isPage);
        $this->pageDisplay($sql,$countSql,16,$ary,'count(id)','list','page',true);
        $this->assign('sex',$this->sexArray);
        $authModel=D('AuthGroup')->where('is_del = 0')->field('id,title')->select();
        $agent = M('car_agent')->field('id,name')->select();
        $this->assign('auth',$authModel);
        $this->assign('agent',$agent);
        $this->display();
    }

    //添加员工信息
    public function add(){
        $auserinfo = UserInfo();
        $userModel=M('AdminUser');
        $rules=self::relse();
        $rules[] = array('identity','','身份证号已经存在！',0,'unique',0); // 在新增的时候验证name字段是否唯一
        $rules[] = array('username','','此账户已存在！',0,'unique',0); // 在新增的时候验证name字段是否唯一
        if($_POST){
            if (!$userModel->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$userModel->getError().'.");history.go(-1);</script>');
            }else{
                $pwd=I('post.Password');
                $data['name'] = I('post.name');
                $data['username'] = I('post.username');
                $data['password'] =md5($pwd);
                $data['departmentid'] = I('post.departmentid');
                $data['type'] = I('post.type');
                $data['sex'] = I('post.sex');
                $data['email'] = I('post.email');
                $data['identity'] = I('post.identity');
                $data['driverno'] = I('post.driverno');
                if ($auserinfo['agent_id'] == 0){
                    $data['agent_id'] = I('post.agent_id');
                }else{
                    $data['agent_id'] = $auserinfo['agent_id'];
                }

                $data['phonenumber'] = I('post.phonenumber');
                $data['remark'] = I('post.remark');
                $data['addtime'] = date('Y-m-d');

                $add = $userModel->add($data);
                if($add){
                    $userFind = $userModel->field('id,departmentid')->order('id desc')->find();
                    $map['uid']=$userFind['id'];
                    $map['group_id']=$userFind['departmentid'];

                    $accessModel = D('auth_group_access')->add($map);
                    exit('<script type="text/javascript">alert("添加成功");location.href="'.U('User/Index').'";</script>');
                }else{
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }

        $this->display();

    }
    //修改员工信息
    public function edit(){
        $userModel = self::UserModel();
        if (empty($_POST)) {
            $id = $_GET['id'];
            cookie('id', $id);
            $cId = cookie('id');
            $authModel = D('AuthGroup')->where('is_del = 0')->field('id,title')->select();
            $this->assign('auth', $authModel);
            $id = I('get.id');
            $userModel = self::UserModel();
            $BerList = $userModel->where(array('id' => $cId))->find();
            if (!empty($id)) {
                $userFind = $userModel->where(array('id' => $cId))->find();
//                var_dump($userFind);
//                exit;
                $this->assign('list', $userFind);
            } else {
                exit('<script type="text/javascript">alert("参数错误");history.go(-1);</script>');
            }
        }
        $rules=self::relse();
        if($_POST){
            $cId = cookie('id');
            if (!$userModel->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$userModel->getError().'.");history.go(-1);</script>');
            }else {
                $data['username'] = I('post.username');
                $data['departmentid'] = I('post.departmentid');
                $data['type'] = I('post.type');
                if ($data['type'] == 3){
                    $data['agent_id'] = I('post.agent_id');
                }else{
                    $data['agent_id'] = 0;
                }
                $data['email'] = I('post.email');
                $data['driverno'] = I('post.driverno');
                $data['phonenumber'] = I('post.phonenumber');
                $data['remark'] = I('post.remark');
                $where['id'] = $cId;
                $userSave = $userModel->where($where)->save($data);
                $data_group_access['group_id'] = I('post.departmentid');
                $where_group_access['uid'] = $cId;
                M('auth_group_access')->where($where_group_access)->save($data_group_access);
                if ($userSave !== true) {
                    cookie($cId,null);
                    exit('<script type="text/javascript">alert("保存成功");location.href="' . U('User/Index') . '";</script>');
                } else {
                    exit('<script type="text/javascript">alert("保存失败");history.go(-1);</script>');
                }
            }
        }
        $agent = M('car_agent')->select();
        $this->assign('sex',$this->sexArray);
        $this->assign('agent',$agent);
        $this->display();
    }

    //删除员工信息
    public function  delete(){
        $id=I('get.id');
        if(!empty($id)){
            $userModel = self::UserModel();
            $deleUser = $userModel->where(array('id'=>$id))->delete();
            if($deleUser){
                $auserInfo = UserInfo();
                self::writeLog('AdminUser', $deleUser, '删除员工', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                exit('<script type="text/javascript">alert("删除成功");location.href="'.U('User/Index').'";</script>');
            }else{
                exit('<script type="text/javascript">alert("删除失败");history.go(-1);</script>');
            }
        }
    }
    //修改员工登陆密码
    public function  Password(){
        $m = M();
        if(!empty($_POST)){

            $rules = array(
                array('Password','require','新密码不能为空！',0), //默认情况下用正则进行验证
                array('Password ','6,16','密码必须为6~16位之间！','length'), // 验证标题长度

            );
            if (!$m->table('admin_user')->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert("'.$m->getError().'");history.go(-1);</script>');
            }else {
                $where['id'] = I('post.id');
                $pwd_old = trim(I('post.old_password'));
                $pwd_old = md5($pwd_old);
                $pwd_one = I('post.Password');
                $pwd_two = I('post.password_two');
                if ($pwd_one != $pwd_two){
                    exit('<script type="text/javascript">alert("两次输入的密码不一致！");history.go(-1);</script>');
                }
                $user_old = $m->table('admin_user')->field('password')->where($where)->find();
                $user_old['password'] = trim($user_old['password']);
                if ($pwd_old != $user_old['password']){
                    exit('<script type="text/javascript">alert("原始密码错误！");history.go(-1);</script>');
                }
                $data['password'] = md5($pwd_one);
                if ($pwd_old == $data['password']){
                    exit('<script type="text/javascript">alert("新密码和原始密码重复！");history.go(-1);</script>');
                }
                $user_pwd = $m->table('admin_user')->where($where)->save($data);
                if ($user_pwd){
                    $auserInfo = UserInfo();
                    self::writeLog('admin_user', $user_pwd, '修改员工密码', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    exit('<script type="text/javascript">alert("修改成功");history.go(-2);</script>');
                }else{
                    exit('<script type="text/javascript">alert("修改失败");history.go(-1);</script>');
                }
            }
        }else{
            $id = I('get.id');
            $sql = "SELECT a.id,a.username,a.type,b.title FROM admin_user a LEFT JOIN auth_group b ON a.departmentid = b.id WHERE a.id = {$id}";
            $userinfo = $m->query($sql);
            $userinfo = $userinfo[0];
            $this->assign('list',$userinfo);
//            echo "<pre>";
//            print_r($userinfo);
            $this->display();
        }
    }
    //代理商信息
    public function  agent(){
        $sql = "SELECT * FROM car_agent";
        $countSql = "SELECT count(id) FROM car_agent";
        $ary = [];
        if (isset($_POST['demand'])){
            $key = I('post.demand');
            $sql .= " WHERE name LIKE '%%%s%%'";
            $countSql .= " WHERE name LIKE '%%%s%%'";
            $ary = [$key];
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        $this->display();
    }
    //添加代理商
    public function  addagent(){
        if(isset($_POST['name'])){
            $agent = M('car_agent');
            $rules=self::agent_rules();
            $rules[] = array('identitys','','身份证号已经存在！',0,'unique',0); // 在新增的时候验证name字段是否唯一
            $rules[] = array('username','','此账户已存在！',0,'unique',0); // 在新增的时候验证name字段是否唯一
            if (!$agent->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$agent->getError().'.");history.go(-1);</script>');
            }else{
                $data['name'] = I('post.name');
                $data['people'] = I('post.people');
                $data['phone'] = I('post.phone');
                $data['address'] = I('post.address');
                $data['identitys'] = I('post.identitys');
                $data['remark'] = I('post.remark');
                $data['addtime'] = date("Y-m-d H:i:s", time());
                $data['updatetime'] = $data['addtime'];
                $res = $agent->add($data);
                if($res){
//                    接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog('car_agent', $res, '添加代理商', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        exit('<script type="text/javascript">alert("添加成功");location.href="'.U('User/agent').'";</script>');
                    }

                }else{
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }else{
            $this->agent();
        }
    }
    //修改代理商信息
    public function agentedit(){
        $agentModel = M('car_agent');
        if (empty($_POST)) {
            echo "66";
            $id = $_GET['id'];
            session('agent_id', $id);
            $cId = session('agent_id');
            if (!empty($id)) {
                $agentFind = $agentModel->where(array('id' => $cId))->find();
                $this->assign('list', $agentFind);
            } else {
                exit('<script type="text/javascript">alert("参数错误");history.go(-1);</script>');
            }
        }
        $rules=self::agent_rules();
        if($_POST){
            $where['id'] = session('agent_id');
            if (!$agentModel->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$agentModel->getError().'.");history.go(-1);</script>');
            }else {
                $data['name'] = I('post.name');
                $data['phone'] = I('post.phone');
                $data['people'] = I('post.people');
                $data['address'] = I('post.address');
                $data['identitys'] = I('post.identitys');
                $data['remark'] = I('post.remark');
                $data['updatetime'] = date("Y-m-d H:i:s", time());
                $res = $agentModel->where($where)->save($data);
                if ($res !== true) {
                    session('agent_id',null);
                    $auserInfo = UserInfo();
                    $log = self::writeLog('car_agent', $res, '修改代理商', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        exit('<script type="text/javascript">alert("修改成功");location.href="'.U('User/agent').'";</script>');
                    }
                } else {
                    exit('<script type="text/javascript">alert("修改失败");history.go(-1);</script>');
                }
            }
        }
        $this->display();
    }
    //禁启用代理商
    public function agent_switch(){
        if (isset($_GET['state'])){
            $agentModel = M('car_agent');
            $state = I('get.state');
            $where['id'] = I('get.id');
            $data['agent_state'] = $state;
            $res = $agentModel->where($where)->save($data);
            if ($res){
                $auserInfo = UserInfo();
                if ($state){
                    self::writeLog('car_agent', $where['id'], '禁用成功', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    exit('<script type="text/javascript">alert("禁用代理商");location.href="'.U('User/agent').'";</script>');
                }else{
                    self::writeLog('car_agent', $where['id'], '启用代理商', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    exit('<script type="text/javascript">alert("启用成功");location.href="'.U('User/agent').'";</script>');
                }
            }else{
                exit('<script type="text/javascript">alert("操作失败，请重试或联系相关人员");history.go(-1);</script>');
            }
        }else{
            $this->display('agent');
        }

    }

    public function clearLoginFail(){
        $tab = M();
        $id = I('get.id');
        $clean = "UPDATE admin_user SET loginfail=%d WHERE id=%d ";
        $re = $tab->execute($clean,[0,$id]);
        if ($re){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog('admin_user', $id, 'clearLoginFail', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            echo '<script>alert("清除成功");self.location=document.referrer;</script>';
        } else {
            echo '<script>alert("清除失败");self.location=document.referrer;</script>';
        }


    }
}