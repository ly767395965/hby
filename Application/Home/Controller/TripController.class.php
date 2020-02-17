<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class NewsController
 * @package Home\Controller
 * 华邦出行
 */

class TripController extends BaseHomeController{

    protected static $table = 'new_aticle';

    public function index()
    {
        $sql = "SELECT id,title,describes,cover,addtime FROM  " . self::$table . " WHERE (isdel=0) AND (catid=3) ORDER BY ID DESC";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(ID) FROM " . self::$table . " WHERE (isdel=0) AND (catid=3)";
        //参数数组，按顺序传递你要传递的参数值
        $ary = [];
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplayimg($sql, $countSql, 5, $ary, 'count(id)', 'list', 'page','Trip/ajax',true);
        $str = date('Y-m-d', strtotime($sql[0]['addtime']) - 86400);
        $this->assign('date', $str);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->display();

    }

    public function trip_details()
    {
        $new = M (self::$table);
        $id = $_GET['id'];
        $sql = "SELECT id,title,describes,content,source,addtime FROM  " . self::$table . " WHERE (isdel=0) AND (catid=3) AND  (id=%d)";
        //参数数组，按顺序传递你要传递的参数值
        $ary = [$id];
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, '', 16, $ary, 'count(id)', 'list', 'page', '', false);
        //计算阅读量
        $sqladd = "UPDATE ".self::$table." SET reading = reading+1 WHERE id=%d";
        $new->execute($sqladd,$ary);
        //查询阅读量
        $read = "SELECT id,reading FROM  " . self::$table . " WHERE (isdel=0) AND (catid=3) AND (id=%d)";
        $reads = $new->query($read,$ary);
        $this->assign('read',$reads);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title','华邦出行_'.$sitetitle[0]['title']);
        $this->display();


    }


}