<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
class AuthruleController extends BaseController {

    public $sexArray = array(0,'男','女');

    static function UserModel(){
        return $AuthModel=M('AuthRule');
    }

    static  function relse(){
        return $rules = array(
            array('name','require','节点值不能为空',1), //默认情况下用正则进行验证
            array('title','require','节点名称不能为空！',1), //默认情况下用正则进行验证
            array('status','require','状态不能为空！',1), //默认情况下用正则进行验证
        ) ;
    }

    //权限节点
    public function index(){
        $authMolde=D('AuthRule')->relation(true)->where(array('pid'=>0))->select();
        $this->assign('list',$authMolde);
        $this->display();
    }

    //添加权限节点
    public function add(){
        $userModel = self::UserModel();
        $Authlist=$userModel->field('id,title')->where(array('pid'=>0))->select();
        $this->assign('list',$Authlist);
        $rules=self::relse();
        if($_POST){
            if (!$userModel->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$userModel->getError().'.");history.go(-1);</script>');
            }else{
                $data['name'] = I('post.name');
                $data['title'] = I('post.title');
                $data['condition'] = I('post.condition');
                $data['status'] = I('post.status');
                $data['pid'] = I('post.pid');
                $add = $userModel->add($data);
                if($add){
                    exit('<script type="text/javascript">alert("添加成功");location.href="'.U('Authrule/Index').'";</script>');
                }else{
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }

        $this->display();

    }
    //修改权限节点
    public function edit(){
        $userModel = self::UserModel();
        if (empty($_POST)) {
            $id = I('get.id');
            cookie('id',$id);
            $cId = cookie('id');
            //根据页面获取的id查询数据
            $result=$userModel->where(array('id'=>$cId))->select();
            $this->assign("updata",$result);
            $Authlist=$userModel->field('id,title')->where(array('pid'=>0))->select();
            $this->assign('lisa',$Authlist);
            if(!empty($cId)){
                $userFind = $userModel->where(array('id='.$id))->find();
                $this->assign('list',$userFind);
            }else{
                exit('<script type="text/javascript">alert("参数错误");history.go(-1);</script>');
            }
            $this->display();
        }

        $rules=self::relse();

        if($_POST){
            if (!$userModel->validate($rules)->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$userModel->getError().'.");history.go(-1);</script>');
            }else {
                $cId = cookie('id');
                $data['name'] = I('post.name');
                $data['title'] = I('post.title');
                $data['condition'] = I('post.condition');
                $data['status'] = I('post.status');
                $data['pid'] = I('post.pid');
                $userSave = $userModel->where(array('id=' . $cId))->save($data);
                if ($userSave) {
                    cookie($cId,null);
                    exit('<script type="text/javascript">alert("保存成功");location.href="' . U('Authrule/Index') . '";</script>');
                } else {
                    exit('<script type="text/javascript">alert("保存失败");history.go(-1);</script>');
                }
            }
        }

    }

    //删除权限节点
    public function  delete(){
        $id=I('get.id');
        if(!empty($id)){
            $userModel = self::UserModel();
            $deleUser = $userModel->where(array('id'=>$id))->delete();
            if($deleUser){
                exit('<script type="text/javascript">alert("删除成功");location.href="'.U('Authrule/Index').'";</script>');
            }else{
                exit('<script type="text/javascript">alert("删除失败");history.go(-1);</script>');
            }
        }

    }

}