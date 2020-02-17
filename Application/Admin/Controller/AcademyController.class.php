<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Cache\Driver\Memcachesae;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *院校名单
 */
class AcademyController extends BaseController
{
    protected static $table = 'sc_academy';
    public function index()
    {
        $sql = "SELECT id,schoolname FROM  sc_academy";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(id) FROM sc_academy";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(0);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, $countSql, 16, '', 'count(id)', 'list', 'page', true);
        $this->display();

    }

    public function addAcademy()
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
                array('schoolname', 'require', '<script>alert("院校全称");history.back(-1);</script>', 1),
            );
            if (!$newclass->validate($data)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($newclass->getError());
            } else {
                //记录操作日志
                if ($newclass->add()) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    $log = self::writeLog(self::$table, $returnid['id'], 'addschool', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        $this->success("添加成功!", U('Academy/Index',1));
                    }
                } else {
                    $this->error("添加失败",'',1);
                }
            }
        }
    }


    public function editAcademy()
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
                array('schoolname', 'require', '<script>alert("院校全称不能为空!");history.back(-1);</script>', 1),
            );
            if (!$newclass->validate($data)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($newclass->getError());
            } else {
                if ($newclass->where(array('id=' . $cid))->save()) {
                //记录操作日志
                    $log = self::writeLog(self::$table, $cid, 'editAcademy', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        $this->success("修改成功!", U('Academy/Index',1));
                    }
                } else {
                    $this->error("修改失败",'',1);
                }
            }
        }
    }


    public function del() {
        $link = M('sc_academy');
        $id = $_GET['id'];
        $LinkModlesace=$link->where(array('id'=>$id))->delete();
        //记录操作日志
        if ($LinkModlesace) {
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table, $id, 'delAcademy', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
                $this->success('删除成功!', U('Academy/Index',1));
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
            $res = $m->where($where)->delete();
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAllAcademy', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }

}
