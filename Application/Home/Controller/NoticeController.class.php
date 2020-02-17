<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class NoticeController
 * @package Home\Controller
 *租车须知控制器
 */
class NoticeController extends BaseHomeController{
    protected static $table = 'new_aticle';
    public function index(){
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        //查询租车须知
        $sql = "SELECT id,title,content,addtime,describes FROM  " . self::$table . " WHERE (isdel=0) AND (catid=10)";
        $countSql = "SELECT COUNT(ID) FROM " . self::$table . " WHERE (isdel=0) AND (catid=10)";
        $ary = [];
        $this->pageDisplay($sql,$countSql, 5, $ary, 'count(id)', 'list', 'page','Notice/ajax', true);
    	 $this->display();

    }



    public function notice_details()
    {
        $new = M (self::$table);
        $id = $_GET['id'];
        $sql = "SELECT id,title,describes,content,source,addtime FROM  " . self::$table . " WHERE (isdel=0) AND (catid=10) AND  (id=%d)";
        //参数数组，按顺序传递你要传递的参数值
        $ary = [$id];
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, '', 16, $ary, 'count(id)', 'list', 'page', '',false);
        //计算阅读量
        $sqladd = "UPDATE ".self::$table." SET reading = reading+1 WHERE id=%d";
        $new->execute($sqladd,$ary);
        //查询阅读量
        $read = "SELECT id,reading FROM  " . self::$table . " WHERE (isdel=0) AND (catid=10) AND (id=%d)";
        $reads = $new->query($read,$ary);
        $this->assign('read',$reads);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title','租车须知_'.$sitetitle[0]['title']);
        $this->display();


    }


}