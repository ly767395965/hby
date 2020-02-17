<?php
namespace Home\Controller;

use Admin\Controller\MemberController;
use Common\Common\BaseHomeController;

/**
 * Class NewsController
 * @package Home\Controller
 * 新闻中心控制器
 */

class NewsController extends BaseHomeController
{
    protected static $table = 'new_aticle';

    public function index()
    {
        $sql = "SELECT id,title,describes,cover,addtime FROM  " . self::$table . " WHERE (isdel=0) AND (catid=1) ORDER BY ID DESC";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(ID) FROM " . self::$table . " WHERE (isdel=0) AND (catid=1)";
        //参数数组，按顺序传递你要传递的参数值
        $ary = [];
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplayimg($sql, $countSql, 5, $ary, 'count(id)', 'list', 'page','News/ajax', true);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->display();

    }

    public function new_details()
    {
        $new = M (self::$table);
        $id = $_GET['id'];
        $sql = "SELECT id,title,describes,content,source,addtime FROM  " . self::$table . " WHERE (isdel=0) AND (catid=1) AND  (id=%d)";
        //参数数组，按顺序传递你要传递的参数值
        $ary = [$id];
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, '', 16, $ary, 'count(id)', 'list', 'page', '', false);
        //计算阅读量
        $sqladd = "UPDATE ".self::$table." SET reading = reading+1 WHERE id=%d";
        $new->execute($sqladd,$ary);
        //查询阅读量
        $read = "SELECT id,reading FROM  " . self::$table . " WHERE (isdel=0) AND (catid=1) AND (id=%d)";
        $reads = $new->query($read,$ary);
        $this->assign('read',$reads);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title','华邦快讯_'.$sitetitle[0]['title']);
        $this->display();


    }


    

}