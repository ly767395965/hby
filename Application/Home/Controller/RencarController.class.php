<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;

/**
 * Class RencarController
 * @package Home\Controller
 * 租车订单控制器
 */
class RencarController extends BaseHomeController
{
    protected static $table = 'car_carmodel';


    /*租车页面展示*/
    public function index()
    {
        $sql = "SELECT a.id,a.frontimg,a.carmodelname,a.displacement,a.agestyle,a.configstyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE a.isdel=0 AND (a.state = 0) ORDER BY a.sort ASC";
        //查询SQL语句，因为是字符串，可以进行.连接，注意空格
        $countSql = "SELECT COUNT(a.id) FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE a.isdel=0 AND (a.state = 0)";
        //参数数组，按顺序传递你要传递的参数值
        $ary = [];
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplayimg($sql, $countSql,6, $ary, 'count(a.id)', 'list', 'page','Rencar/ajax', true);

        //车品牌列表
        $m = M('car_barand');
        $where['isdel'] = 0;
        $where['state'] = 0;
        $row = $m->where($where)->field('id,brand')->select();
        //站点名称
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->assign('row', $row);
        $this->display();
    }

    public function search(){
        $brand = I('get.pp');
        $carmodeltype = I('get.cx');
        $bearboxtype = I('get.pdg');

        $sql = "SELECT a.id,a.frontimg,a.carmodelname,a.barandid,a.carmodeltype,a.bearboxtype,a.displacement,a.configstyle,a.agestyle,a.bearboxtype,a.sitecount,a.shortdayprice,a.weekdayprice,a.monthdayPrice,b.brand FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND (a.state = 0)";
        $countSql = "SELECT COUNT(a.id) FROM car_carmodel a LEFT JOIN car_barand b ON a.barandid=b.id WHERE (a.isdel=0) AND (a.state = 0)";

        if (!empty($brand)) {
            $sql = $sql."AND (a.barandid=$brand)";
            $countSql = $countSql ."AND (a.barandid=$brand)";
        }
        if (!empty($carmodeltype)) {
            $sql = $sql."AND (a.carmodeltype=$carmodeltype)";
            $countSql = $countSql ."AND (a.carmodeltype=$carmodeltype)";
        }
        if (!empty($bearboxtype)) {
            $sql = $sql."AND (a.bearboxtype='".$bearboxtype."')";
            $countSql = $countSql ."AND (a.bearboxtype='".$bearboxtype."')";
        }
        $ary = [];
        $sql = $sql." ORDER BY a.sort ASC";
        $this->pageDisplayimg($sql, $countSql, 6, $ary, 'count(a.id)', 'list', 'page','Rencar/ajax', true);

    }


}