<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class FavorableController
 * @package Home\Controller
 * 优惠车辆列表控制器
 */
class FavorableController extends BaseHomeController
{
    protected static $table = 'car_carinfo';
    public function index()
    {
        $sql = "SELECT a.id,b.frontimg,b.carmodelname,b.configstyle,b.agestyle,b.bearboxtype,b.sitecount,a.goodprice,b.shortdayprice,b.weekdayprice,b.monthdayPrice,c.brand  FROM car_carinfo a LEFT JOIN car_carmodel b ON (a.carmodel=b.id) LEFT JOIN car_barand c ON (a.brand=c.id) LEFT JOIN car_agent d ON (a.agent_id=d.id) WHERE (a.isdel=0) AND (a.isdiscount=1) AND (a.usestatus = 0) AND (a.auditing_state = 1) AND ((d.agent_state != 1) OR (d.agent_state is null)) ORDER BY a.id DESC";
        //查询SQL语句，因为是字符串，可以进行.连接，注意空格
        $countSql = "SELECT COUNT(a.id) FROM car_carinfo a LEFT JOIN car_carmodel b ON (a.carmodel=b.id) LEFT JOIN car_barand c ON (a.brand=c.id) LEFT JOIN car_agent d ON (a.agent_id=d.id) WHERE (a.isdel=0) AND (a.isdiscount=1) AND (a.usestatus = 0) AND (a.auditing_state = 0) AND ((d.agent_state != 1) OR (d.agent_state is null))";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array();
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplayimg($sql, $countSql, 12, $ary, 'count(a.id)', 'list', 'page', 'Favorable/ajax' , true);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->display();
    }


}