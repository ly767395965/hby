<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class NewsController
 * @package Home\Controller
 * 关于我们控制器
 */

class TestController extends BaseHomeController{
    protected static $table = 'new_aticle';
    public function index(){
        $new = M(self::$table);
        $sql = "SELECT id,title,content FROM  " . self::$table . " WHERE (isdel=0) AND (catid=2)";
        //获取数据总记录数量
        $ary = [];
//        $list = $new->query()
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, '', 16, $ary, 'count(id)', 'list', 'page', '',false);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->display();

    }



}