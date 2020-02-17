<?php
namespace Admin\Controller;
use Think\Controller;
class HbyCompanyController extends CommonController {
    public function index(){
        $this->display();
    }

/*
    function pageDisPlay($sql,$pageNum,$name,$ary) {
        $m = M();
        $list = $m->execute($sql,$ary);
        $count = $list;// 查询满足要求的总记录数
        $page = new \Think\Page($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show = $page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $sql = $sql . " LIMIT $page->firstRow,$page->listRows";
        $list = $m->query($sql,$ary);
        $this->assign($name,$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出$this->display(); // 输出模板
        if($list){
            $this->ajaxReturn(array('state'=>1, 'msg'=>'查看成功！', 'href'=>U(CONTROLLER_NAME.'/Index')));
        }else{
            $this->ajaxReturn(array('state'=>5, 'msg'=>'增加失败！'));
        }
        $this->display();
    }
    */

}