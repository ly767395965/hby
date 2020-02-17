<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *驾驶员培训课题控制器
 */
class TrainingTopicController extends BaseController
{

    public function index()
    {


        $sql = "SELECT id,topicname,state,addtime,flag FROM driver_topic WHERE isdel = %d";
        $countSql = "SELECT COUNT(ID) FROM driver_topic WHERE isdel=%d";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(0);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        $this->display();

    }


    /**
     * 添加驾驶员培训课题
     */
    public function addTopic()
    {
        if (empty($_POST)){
            $this->display();
        }
        if ($_POST){
            $tab = M();
            $Topicname = I('post.Topicname');
            $date = Date('Y-m-d H:i:sA');
            $sql = "INSERT INTO driver_topic (topicname,state,addtime,flag,isdel) VALUES ('%s',%d,'%s',%d,%d)";
            $arr = [$Topicname,0,$date,1,0];
            $re = $tab->execute($sql,$arr);
            if ($re){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                //获取添加成功返回的数据id
                $returnid = M('driver_topic')->order('id desc')->find();
                $log = self::writeLog('driver_topic', $returnid['id'], 'addTopic', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if($log){
                    $this->success("添加成功!", U('TrainingTopic/Index',1));
                }

            } else {
                $this->error("添加失败",'',1);
            }
        }


    }

    /**
     * 修改驾驶员培训课题
     */
    public function editTopic()
    {
        $tab = M();
        if ($_GET){
            $id = I('get.id');
            $sql = "SELECT id,topicname FROM driver_topic WHERE id = %d";
            $list = $tab->query($sql,[$id]);
            $this->assign('list',$list[0]);
            $this->display();
        }
        if ($_POST){
            $TopiId = I('post.TopiId');
            $topicname = I('post.Topicname');
            $up = "UPDATE driver_topic SET topicname='%s',flag=%d WHERE id=%d";
            $re = $tab->execute($up,[$topicname,2,$TopiId]);
            if ($re){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                //记录操作日志
                $log = self::writeLog('driver_topic', $TopiId, 'editTopic', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    cookie('id', null);
                    $this->success("修改成功!", U('TrainingTopic/Index',1));
                }
            } else {
                $this->error("修改失败",'',1);
            }

        }
    }

    //标记培训课题为无效
    public function thawTopic() {
        $id = $_GET['id'];
        $state = M('driver_topic');
        $data =array();
        $data['state'] = 1;
        if($state->where(array('id='.$id))->save($data)){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('driver_topic', $id, 'thawTopic', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                echo '<script>alert("操作成功");self.location=document.referrer;</script>';
            }
        }else{
            echo '<script>alert("操作失败");self.location=document.referrer;</script>';
        }
    }


    //标记培训课题为有效 Thaw()
    public function thawTopic1() {
        $id = $_GET['id'];
        $state = M('driver_topic');
        $data =array();
        $data['state'] = 0;
        if($state->where(array('id='.$id))->save($data)){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog('driver_topic', $id, 'thawTopic1', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                echo '<script>alert("操作成功");self.location=document.referrer;</script>';
            }
        }else{
            echo '<script>alert("操作失败");self.location=document.referrer;</script>';
        }
    }









    /**
     * 驾驶员培训课题删除方法
     */
    public function delTopic()
    {
        $adInfo = M('driver_topic');
        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        $id = I('get.id');
        $data['isdel'] = 1;
            //如果该分类下没有数据则可删除该分类
            $LinkModlesace = $adInfo->where(array('id' => $id))->save($data);
            if ($LinkModlesace) {
                //记录操作日志
                $log = self::writeLog('driver_topic', $id, 'delTopic', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    $this->success('删除成功!', U('TrainingTopic/Index',1));
                }
            } else {
                $this->error('删除失败','',1);
            }


    }

    //批量删除
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M('driver_topic');
            $where['id'] = array('IN', $ids);
            $data['isdel'] = 1;
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog('driver_topic', $id, 'delAllTopic', date('Y-m-d H:i:sA'), self::cookieName());

                }
                $this->ajaxReturn(array('state' => 1));
                $this->success('删除成功!', U('TrainingTopic/Index',1));
            }
        }


    }

}
