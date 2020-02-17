<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;

use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *友情链接控制器
 */
class LinksController extends BaseController {
    protected static $table = 'link_links';
    //友链管理列表
    public function index() {
        $key = I('get.key');//接受模糊查询的条件
        $ary = [$key];//将条件传给数组
        $selectid = I('get.select');
        $sql = "SELECT id,linkname,linkurl FROM link_links  WHERE (isdel=0) ";
        $countSql = "SELECT COUNT(ID) FROM link_links WHERE isdel=0 ";
        switch ($selectid){
            case 1:
                $sql = $sql." AND (linkname LIKE '%%%s%%')";
                $countSql = $countSql." AND (linkname LIKE '%%%s%%')";
                break;
            case  2:
                $sql = $sql." AND (linkurl LIKE '%%%s%%')";
                $countSql = $countSql." AND (linkurl LIKE '%%%s%%')";
                break;
            default:
                $sql = $sql." ORDER BY id DESC";
                $countSql = $countSql;
        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid != null) {
            $sql = $sql . "ORDER BY id DESC";
            $ary = [$key];
        } else {
            $ary = [];
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);

        $this->display();

    }

    //友情链接添加方法 addLinks()
    public function addLinks() {
        //判断输出模板
        if (empty($_POST)){
            $this->display();
        }
        if ($_POST) {
            $link = M(self::$table);
            $Data = array(
                array('linkname','require','<script>alert("友链名称不能为空!");history.back(-1);</script>',0),
                array('linkurl','url','<script>alert("友链地址不合法!");history.back(-1);</script>',0),
            );
            if(!$link->validate($Data)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($link->getError());
            }else{
                //记录操作日志
                if ($link->add()) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log){
                        $this->success("添加成功!",U('Links/Index',1));
                    }
                }else{
                    $this->error("添加失败",'',1);
                }
            }
        }
    }

    //友情链接修改方法 editLinks()
    public function editLinks(){
        $link = M(self::$table);
//        判断操作数据的id的提交方式并接收
        if (empty($_POST)){
            $id = $_GET['id'];
            cookie('id',$id);
            $cid = cookie('id');
            $reult = $link->where(array('id='.$cid))->select();
            $this->assign("list",$reult);
            $this->display();
        }

        if ($_POST) {
            $cid = cookie('id');
            $Data = array(
                array('linkname','require','<script>alert("友链名称不能为空!");history.back(-1);</script>',1),
                array('linkurl','require','<script>alert("友链地址不能为空!");history.back(-1);</script>',1),
            );
            if(!$link->validate($Data)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($link->getError());
            }else{
                //记录操作日志
                if ($link->where(array('id='.$cid))->save()) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log){
                        cookie('id',null);
                        $this->success("修改成功!",U('Links/Index',1));
                    }
                }else{
                    $this->error("修改失败",'',1);
                }
            }
        }

    }
 
    //友情链接删除方法 del()
    public function del() {
        $link = M('LinkLinks');
        $id = $_GET['id'];
        $data['isdel'] = 1;
        $LinkModlesace=$link->where(array('id'=>$id))->save($data);
        //记录操作日志
        if ($LinkModlesace) {
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table, $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
                $this->success('删除成功!', U('Links/Index',1));
            }
        }else{
            $this->error('删除失败','',1);
        }
        
    }

    //批量删除
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $where['id'] = array('IN', $ids);
            $data['isdel'] = 1;
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }
}
