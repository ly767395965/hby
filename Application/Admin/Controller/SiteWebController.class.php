<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *站点设置控制器
 */
class SiteWebController extends BaseController{
    public function index() {

        $site = M("system_site");

        if (empty($_POST)) {
            $result = $site->select();
            $this->assign("list", $result);
            $this->display();
        }


        if ($_POST) {

            $Data = array(
                array('version', 'require', '<script>alert("APP版本号不能为空!");history.back(-1);</script>', 0),
                array('company', 'require', '<script>alert("公司名称不能为空!");history.back(-1);</script>', 0),
                array('phone', 'require', '<script>alert("公司电话不能为空!");history.back(-1);</script>', 0),
                array('domian', 'require', '<script>alert("网站网址不能为空!");history.back(-1);</script>', 0),
                array('author', 'require', '<script>alert("技术支持不能为空!");history.back(-1);</script>', 0),
                array('title', 'require', '<script>alert("网站标题不能为空!");history.back(-1);</script>', 0),
                array('keywords', 'require', '<script>alert("关键词不能为空!");history.back(-1);</script>', 0),
                array('describes', 'require', '<script>alert("网站描述不能为空!");history.back(-1);</script>', 0),
                array('email', 'require', '<script>alert("邮箱地址不能为空!");history.back(-1);</script>', 0),
                array('address', 'require', '<script>alert("公司地址不能为空!");history.back(-1);</script>', 0),
                array('public', 'require', '<script>alert("公安备案号不能为空!");history.back(-1);</script>', 0),
            );
            $id = $_POST['id'];
            if ($id == "" ) {
                if (!$site->validate($Data)->create()) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    exit($site->getError());
                } else {
                    if ($site->add()) {
                        $this->success("添加成功!", U('SiteWeb/Index'));
                    } else {
                        $this->error("添加失败");
                    }
                }

            } else {
                if ($site->validate($Data)->create()) {

                    if ($site->where(array('id='.$id))->save()) {
                        /**
                         * 记录操作日志
                         */
                        $log = M('site_log');
                        $system = array();
                        $auserInfo = UserInfo();//接收当前管理员登陆名
                        $system['tablename'] = "system_site";//当前操作的表名
                        $system['dataid'] = "";//当前操作的数据编号
                        $system['operate'] = "修改";//操作
                        $system['disposedate'] =  Date('Y-m-d H:i:sA');//当前操作时间
                        $system['adminname'] = $auserInfo['name'];//当前操作员
                        if ($log->add($system)){
                            $this->success("修改成功!", U('SiteWeb/Index'));
                        }
                    } else {
                        $this->error("修改失败");
                    }
                }


            }


        }

    }

    //询单业务
    public function prompt(){
        $str = I('get.str');
        if (!empty($str)){
            $m = M();
            //租车订单查询
            $sql = "SELECT COUNT(a.id) FROM `order` a LEFT JOIN work_member b ON a.uid=b.id WHERE (b.usertype=0) AND (a.is_del=0) AND (a.order_state = 1)";
            $list = $m->query($sql);

            //网约车订单查询
            $sql1 = "SELECT COUNT(id) FROM order_netcar WHERE OrderState < 2 ";
            $list1 = $m->query($sql1);
            if ($list[0]['count(a.id)'] > 0 || $list1[0]['count(id)'] > 0){
                $this->ajaxReturn(array('data'=>$list[0]['count(a.id)'],'netcarorder'=>$list1[0]['count(id)']));
            } else {
                $this->ajaxReturn(array('data'=>0));
            }
        }
    }



}