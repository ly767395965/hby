<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Org\Util\Date;
use Think\Controller;

/**
 * Class CompanyController
 * @package Admin\Controller
 * 角色管理控制器
 */
class CompanyController extends BaseController
{
    //对应模型表名
    protected static $table = 'auth_group';

    public $sexArray = array(0, '男', '女');

    static function UserModel()
    {
        return $userModel = M(self::$table);
    }

    /*查看部门*/
    public function index()
    {
        if (!empty($_POST)) {
            $demand = I('post.demand');
            switch ($demand) {
                case '正常':
                    $where = " AND status=1";
                    break;
                case '禁用':
                    $where = " AND status=0";
                    break;
                default :
                    $where = " AND title LIKE '%%%s%%'";
            }
        } else {
            $where = '';
        }
        $sql = "SELECT * FROM ".self::$table." WHERE is_del=0" . $where;
        $ary = [$demand];
        $countSql = "SELECT count(id) FROM ".self::$table." WHERE is_del=0" . $where;
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        $this->assign('sex', $this->sexArray);
        $this->display();
    }

    /*添加员工信息*/
    public function addCompany()
    {
        $userModel = M(self::$table);
        $rules = array(
            array('title', 'require', '真实姓名必须填写！', 1), //默认情况下用正则进行验证
            array('status', 'require', '真实姓名必须填写！', 1), //默认情况下用正则进行验证
        );
        if ($_POST) {
            if (!$userModel->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($userModel->getError());
            } else {
                $data['title'] = I('post.title');
                $data['status'] = I('post.status');
                $data['rules'] = "64,65,66,67";
                $add = $userModel->add($data);
                if ($add) {
                    /*记录操作日志*/
                    //获取添加成功返回的数据id
                    $returnId = $userModel->order('id desc')->find();
                    $log = self::writeLog(self::$table, $returnId['id'], 'add', date('Y-m-d H:i:sA'), self::cookieName());
                    if ($log) {
                        $this->success('添加成功', U('Company/Index'), 1);
                    } else {
                        $this->error('添加失败', '', 1);
                    }
                }
            }
        } else {
            $this->display();
        }
    }

    /*修改部门*/
    public function editCompany()
    {
        $userModel = self::UserModel();
        if (empty($_POST)) {
            $id = $_GET['id'];
            cookie('id', $id);
            $cId = cookie('id');
            if (!empty($id)) {
                $groupFind = $userModel->where(array('id' => $cId))->find();
                $this->assign('list', $groupFind);
            } else {
                $this->error('参数错误');
            }
        }

        if ($_POST) {
            $cId = cookie('id');
            $data['title'] = I('post.title');
            $data['status'] = I('post.status');
            $groupAdd = $userModel->where(array('id' => $cId))->save($data);
            if ($groupAdd) {
                cookie($cId, null);
                $log = self::writeLog(self::$table, $cId, 'edit', date('Y-m-d H:i:sA'), self::cookieName());
                if ($log) {
                    $this->success('修改成功', U('Company/Index'));
                } else {
                    $this->error('修改失败');
                }
            }
        } else {
            $this->display();
        }
    }

    /*删除部门*/
    public function delCompany(){
        $m = M();
        $id = I('get.id');
        if (!empty($id)) {
            $userModel = self::UserModel();
            $where_access['group_id'] = $where['id'] = $id;
            $data['is_del'] = 1;
            $group_access = $m->table('auth_group_access')->where($where_access)->select();
            if ($group_access){
                exit('<script type="text/javascript">alert("有操作员正在使用此角色，无法删除！");history.go(-1);</script>');
            }else{
                $deleUser = $userModel->where($where)->save($data);
            }

            if ($deleUser) {
                /*记录操作日志*/
                $log = self::writeLog(self::$table, I('get.id'), 'del', date('Y-m-d H:i:sA'), self::cookieName());
                if ($log) {
                    $this->success('删除成功', U('Company/Index'));
                } else {
                    $this->error('删除失败');
                }
            }
        }
    }

    /*批量删除*/
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $where['id'] = array('IN', $ids);
            $data['is_del'] = 1;
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

    /*权限分配*/
    public function distribution()
    {
        $uid = I('get.id');
        if (!$uid) {
            $this->error("参数错误");
        }

        if (IS_POST) {
            $rule = I('post.rule');
            $ruleStr = implode(',', $rule);
            $where[id] = $uid;
            $data['rules'] = $ruleStr;
            $res = M(self::$table)->where($where)->save($data);
            if ($res) {
                $log = self::writeLog(self::$table, $uid, 'edit', date('Y-m-d H:i:sA'), self::cookieName());
                if ($log) {
                    $this->success('权限修改成功', U('Company/Index'));
                } else {
                    $this->error('权限修改失败');
                }
            }
        }
        $authModel = D('AuthRule');
        $rule = D('auth_group')->where(array('id' => $uid))->getField('rules');
        $this->assign('rule', $rule);
        $ruleModel = $authModel->where(array('pid' => 0))->select();
        $pieces = explode(",", $rule);
        foreach ($ruleModel as $key => $value) {
            if (in_array($value['id'], $pieces)) {
                $ruleModel[$key]['existence'] = 1;
            }
        }
        $data['pid'] = array('NEQ', 0);
        $rulelist = $authModel->where(array($data))->select();
        foreach ($rulelist as $k => $v) {
            if (in_array($v['id'], $pieces)) {
                $rulelist[$k]['existence'] = 1;
            }
        }
        $this->assign('rulelist', $rulelist);
        $this->assign('list', $ruleModel);
        $this->display();

    }

}