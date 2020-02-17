<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
class MemberController extends BaseController {
    protected static $table = 'work_member';
    //会员显示列表
    public function index(){
        $key = I('get.key');//接受模糊查询的条件
        $ary = [$key];//将条件传给数组
        $selectid = I('get.select');
        $sql = "SELECT id,username,phone,identitys,usertype,sex,score,balance,state,addtime,check_cycle FROM work_member ";
        $countSql = "SELECT count(id) FROM work_member";
        switch ($selectid){
            case 1:
                $sql = $sql."WHERE (username LIKE '%%%s%%')";
                $countSql = $countSql." WHERE (username LIKE '%%%s%%')";
                break;
            case  2:
                $sql = $sql." WHERE (phone LIKE '%%%s%%') ";
                $countSql = $countSql." WHERE (phone LIKE '%%%s%%')";
                break;
            default:
                $sql = $sql." ORDER BY id DESC";
                $countSql = $countSql;

        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid != null) {
            $sql = $sql . " ORDER BY id DESC";
            $ary = [$key];
        } else {
            $ary = [];
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        $this->display();
    }

    //账户冻结方法 Frozen（）
    public function Frozen(){
        $id = $_GET['id'];
        $state = M('work_member');
        $data =array();
        $data['state'] = 1;
        if($state->where(array('id='.$id))->save($data)){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table, $id, 'Frozen', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                echo '<script>alert("冻结成功");self.location=document.referrer;</script>';
            }
        }else{
            echo '<script>alert("冻结失败");self.location=document.referrer;</script>';
        }
    }

    //账户解冻方法 Thaw()
    public function Thaw() {
        $id = $_GET['id'];
        $state = M('work_member');
        $data =array();
        $data['state'] = 0;
        if($state->where(array('id='.$id))->save($data)){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table, $id, 'Thaw', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                echo '<script>alert("解冻成功");self.location=document.referrer;</script>';
            }
        }else{
            echo '<script>alert("解冻失败");self.location=document.referrer;</script>';
        }
    }

    //会员添加方法 addMember（）
    public function addMember() {
        $add = M('work_member');
        if (empty($_POST)){
            $this->display();
        }
        if (!empty($_POST)){
            $data = array(
                array('user', 'require', '<script>alert("姓名不能为空");history.back(-1);</script>', 1),
                array('phone', 'require', '<script>alert("用户账户不能为空");history.back(-1);</script>', 1),
                array('cost', 'require', '<script>alert("身份证号不能为空");history.back(-1);</script>', 1),
                array('usertype', 'require', '<script>alert("用户类型不能为空");history.back(-1);</script>', 1),
                array('pass', 'require', '<script>alert("用户密码不能为空");history.back(-1);</script>', 1),
            );
            if (I('post.usertype') == 0){
                $data[] = array('cost', 'require', '<script>alert("身份证号不能为空");history.back(-1);</script>', 1);
                $data[] = array('cost', '/[1-9]\d{5}(((1[9|8])\d{2})|(20[0-1]\d))((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)/', '<script>alert("身份证号格式不对！");history.back(-1);</script>', 1);
            }else{
                $data[] = array('check_cycle', 'require', '<script>alert("结账周期不能为空");history.back(-1);</script>', 1);
                $data[] = array('check_cycle', 'number', '<script>alert("结账周期必须为数字");history.back(-1);</script>', 1);
            }
            if (!$add->validate($data)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($add->getError());
            } else {
                //判断用户是否存在
                $sql = "SELECT COUNT(id) FROM work_member WHERE  (phone='%s')";
                $arr = I('post.phone');
                $list = $add->query($sql,$arr);
                $num = $list[0]['count(id)'];
                if ($num>0){
                    echo '<script>alert("该用户账户，请更换手机号注册");history.back(-1);</script>';
                } else {
                    $sex = I('post.sex');
                    $data = array();
                    if ($sex == '男'){
                        $data['sex'] = 1;
                    } else {
                        $data['sex'] = 2;
                    }
                    $data['username'] = I('post.user');
                    $data['phone'] = I('post.phone');
                    $data['identitys'] = I('post.cost');
                    $data['usertype'] = I('post.usertype');
                    if ($data['usertype'] == 1){
                        $data['check_cycle'] = I('post.check_cycle');
                    }
                    $data['userpass'] = md5(I('post.pass'));
                    $data['addtime'] = Date('Y-m-d H:m:s',time());

                    if ($add->add($data)){
                        //获取添加成功返回的数据id
                        $returnid = M(self::$table)->order('id desc')->find();
                        //接收当前管理员登陆名
                        $auserInfo = UserInfo();
                        $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                        if ($log) {
                            $this->success('会员添加成功',U('Member/Index'),1);
                        }
                    } else {
                        $this->error('会员添加失败','',1);
                    }
                }
            }
        }

    }

    //会员信息修改 editMember（）
    public function editMember() {
        $edit = M('work_member');
        if (empty($_POST)){
            $id = $_GET['id'];
            cookie('id',$id);
            $id = cookie('id');
            $sql = "SELECT id,username,phone,usertype,identitys,userpass,sex,check_cycle FROM work_member WHERE (id=%d)";
            $arr = [$id];
            $list = $edit->query($sql,$arr);
            $this->assign('list',$list);
            $this->display();
        }
        if (!empty($_POST)){
            $id = cookie('id');
            $data = array(
                array('user', 'require', '<script>alert("姓名不能为空");history.back(-1);</script>', 1),
                array('phone', 'require', '<script>alert("用户账户不能为空");history.back(-1);</script>', 1),
                array('cost', 'require', '<script>alert("身份证号不能为空");history.back(-1);</script>', 1),
                array('usertype', 'require', '<script>alert("用户类型不能为空");history.back(-1);</script>', 1),
            );
            if (I('post.usertype') == 0){
                $data[] = array('cost', 'require', '<script>alert("身份证号不能为空");history.back(-1);</script>', 1);
                $data[] = array('cost', '/[1-9]\d{5}(((1[9|8])\d{2})|(20[0-1]\d))((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)/', '<script>alert("身份证号格式不对！");history.back(-1);</script>', 1);
            }else{
                $data[] = array('check_cycle', 'require', '<script>alert("结账周期不能为空");history.back(-1);</script>', 1);
                $data[] = array('check_cycle', 'number', '<script>alert("结账周期必须为数字");history.back(-1);</script>', 1);
            }
            if (!$edit->validate($data)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($edit->getError());
            } else {
                $sex = I('post.sex');
                $username = I('post.user');
                $phone = I('post.phone');
                $identitys = I('post.cost');
                $usertype = I('post.usertype');
                if ($usertype == 1){
                    $check_cycle = I('post.check_cycle');
                }else{
                    $check_cycle = 0;
                }
                $update = "UPDATE work_member SET username='%s',phone='%s',identitys='%s',usertype=%d,sex=%d,check_cycle=%d WHERE (id=%d)";
                $arr1 = [$username,$phone,$identitys,$usertype,$sex,$check_cycle,$id];
                $editdata = $edit->execute($update,$arr1);
                if ($editdata !== false){
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $id, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id',null);
                        $this->success("修改成功!", U('Member/Index',1));
                    }
                } else {
                    cookie('id',null);
                    $this->error('修改失败');
                }

            }
        }
    }

    //初始化用户密码的方法 editPass（）
    public function editPass() {
        $user = M('work_member');
        cookie('id',$_GET['id']);
        $id = cookie('id');
        $sql = "UPDATE work_member SET userpass=md5('123456') WHERE (id=%d)";
        $arr = [$id];
        $list = $user->execute($sql,$arr);
        if ($list !== false){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table, $id, 'editpass', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id',null);
                $this->success("密码初始化成功!", U('Member/Index',1));
            }
        } else {
            cookie('id',null);
            $this->error("密码初始化失败!",'',1);
        }
    }

    //会员删除方法 del ()
    public function del() {
        cookie('id',$_GET['id']);
        $id = cookie('id');
        $member = M('work_member');
        $backups = M('work_member_backups');
        $sql = "SELECT id,state,username,phone,userpass,usertype,identitys,sex,score,balance,addtime,remarks FROM work_member WHERE (id=%d)";
        $arr = [$id];
        $list = $member->query($sql,$arr);

        $data = [];
        $data['dataid'] = $list[0]['id'];
        $data['state'] = $list[0]['state'];
        $data['username'] = $list[0]['username'];
        $data['phone'] = $list[0]['phone'];
        $data['userpass'] = $list[0]['userpass'];
        $data['usertype'] = $list[0]['usertype'];
        $data['identitys'] = $list[0]['identitys'];
        $data['sex'] = $list[0]['sex'];
        $data['score'] = $list[0]['score'];
        $data['balance'] = $list[0]['balance'];
        $data['addtime'] = $list[0]['addtime'];
        $data['remarks'] = $list[0]['remarks'];
        $data['deltime'] = Date('Y-m-d H:m:s',time());
        if ($backups->add($data)){
            $del = "DELETE  FROM work_member WHERE (id=%d)";
            $deldata = $member->execute($del,$arr);
            if ($deldata>0){
                // 记录操作日志
                $auserInfo = UserInfo(); //接收当前管理员登陆名
                $log = self::writeLog('work_member', $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id',null);
                    $this->success("删除成功!", U('Member/Index',1));
                }
            }
        } else {
            $this->error('删除失败!','',1);
        }
    }


}