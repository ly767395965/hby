<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
class AuthorityController extends BaseController {
    public function index(){
        $sql = "SELECT * FROM rank_authority";
        $countSql = "SELECT count(id) FROM rank_authority";
        $ary = array();
        $this->pageDisplay($sql,$countSql,20,$ary,'count(id)','list','page',true);
        $this->display();
    }

    /**
     * 
     */
    public function add(){
        $rankModel=M('RankAuthority');
        if($_POST){
            $data['name'] = I('post.name');
            $data['pid'] = I('post.pid');
            $add=$rankModel->add($data);
            if($add){
                exit('<script type="text/javascript">alert("添加成功");location.href="'.U('Authority/Index').'";</script>');
            }else{
                exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
            }

        }

        $this->display();

    }

}