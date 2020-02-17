<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *广告分类控制器
 */
class AdClassController extends BaseController
{
    protected static $table = 'ad_class';
    public function index()
    {
        $sql = "SELECT id,classname FROM  ".self::$table." WHERE isdel=%d ORDER BY ID DESC";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(ID) FROM ".self::$table." WHERE isdel=%d";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(0);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', flash);
        $this->display();

    }

    //广告分类添加方法 addAdClass()
    public function addAdClass()
    {
        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        //判断输出模板
        if (empty($_POST)) {
            $this->display();
        }
        if ($_POST) {
            $newclass = M(self::$table);
            $data = array(
                array('classname', 'require', '<script>alert("广告分类名称不能为空");history.back(-1);</script>', 1),
            );
            if (!$newclass->validate($data)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($newclass->getError());
            } else {
                //记录操作日志
                if ($newclass->add()) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        $this->success("添加成功!", U('AdClass/Index',1));
                    }
                } else {
                    $this->error("添加失败",'',1);
                }
            }
        }
    }


    //广告分类添加方法 editAdClass()
    public function editAdClass()
    {
        $newclass = M(self::$table);
        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        //判断操作数据的id的提交方式并接收
        if (empty($_POST)) {
            $id = I('get.id');
            cookie('id', $id);
            $cid = cookie('id');
            $data = $newclass->where(array('id='.$cid))->select();
            $this->assign('list', $data);
            $this->Display();
        }

        if ($_POST) {
            $cid = cookie('id');
            $data = array(
                array('classname', 'require', '<script>alert("广告分类名称不能为空!");history.back(-1);</script>', 1),
            );
            if (!$newclass->validate($data)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($newclass->getError());
            } else {
                if ($newclass->where(array('id=' . $cid))->save()) {
                //记录操作日志
                    $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        $this->success("修改成功!", U('AdClass/Index',1));
                    }
                } else {
                    $this->error("修改失败",'',1);
                }
            }
        }
    }


    //广告分类删除方法 del()
    public function del()
    {
        //实例化广告管理表
        $adInfo = M('ad_banner');
        //加载广告分类表
        $newclass = M(self::$table);
        //接收当前管理员登陆名
        $auserInfo = UserInfo();
        $id = I('get.id');
        $data['isdel'] = 1;
        //关联删除查询
        $sql = "SELECT COUNT(ID) FROM ad_banner WHERE (isdel=0) AND (classid=%d)";
        $arr = [$id];
        $adList = $adInfo->query($sql,$arr);
        //如果该分类下是否有关联数据
        if ($adList[0]['count(id)']>0){
            //如果该分类下存在数据则不能删除并终止该操作
            echo '<script>alert("该广告分类下有数据，暂时不能删除该分类!");history.back(-1);</script>';
            exit();
        } else {
            //如果该分类下没有数据则可删除该分类
            $LinkModlesace = $newclass->where(array('id' => $id))->save($data);
            if ($LinkModlesace) {
                //记录操作日志
                $log = self::writeLog(self::$table, $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log) {
                    $this->success('删除成功!', U('AdClass/Index',1));
                }
            } else {
                $this->error('删除失败','',1);
            }
        }

    }

    //批量删除
    public function delAll()
    {
        //实例化广告管理表
        $adInfo = M('ad_banner');
        //$isError 空数组用于存储不能删除的数据id。
        $isError = [];
        //$isDelStr 空字符串变量用于并接 $isError 数组里面的id。
        $isDelStr = '';
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $data['isdel'] = 1;
            //关联删除查询
            $arr = explode(",",$ids);
            foreach ($arr as $key){
                $sql = "SELECT COUNT(ID) FROM ad_banner WHERE (isdel=0) AND (classid=%d)";
                $ary = $key;
                $adList = $adInfo->query($sql,$ary);
                //如果该分类下是否有关联数据
                if ($adList[0]['count(id)']>0){
                    //如果该分类下存在数据则不能删除并终止该操作,并且追加到 $isError 数组
                    array_push($isError,$key);
                } else {
                    //执行逻辑删除数据
                    $res = $m->where('id='.$key)->save($data);
                    //删除成功并记录操作日志
                    if ($res) {
                        self::writeLog(self::$table, $key, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                    }
                }
            }
            //循环 $isError 数组里面的值 并赋值给 $isDelStr 字符串变量。
            foreach ($isError as $val) {
                $isDelStr .= $val . ',';
            }

            //如果字符串变量 $isError 为空，表示所选的数据都能删除。
            if (empty($isError)) {
                $this->ajaxReturn(array('state' => 1));
            } else {
                //如果字符串变量 $isError 有值，则表示所选的数据不能删除，并反馈给操作用户
                $this->ajaxReturn(array('state'=>0,'msg'=>'id为:('. $isDelStr .')的分类下存在数据不能删除该分类，请清空数据再尝试！'));
            }

        }
    }

}
