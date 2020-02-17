<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 *网站后台操作日志控制器
 */
    class SystemLogController extends BaseController
{
    public function index()
    {
        $startDate = $_GET['start'];//开始时间
        $stopDate = $_GET['stop'];//结束时间
        $key = I('get.key');//接受模糊查询的条件
        $ary = [$key];//将条件传给数组
        $selectId = I('get.select');
        $sql = "SELECT id,tablename,dataid,operate,disposedate,adminname FROM site_log WHERE (disposedate BETWEEN '%s' AND '%s') AND (isdel=0) ";
        $countSql = "SELECT COUNT(ID) FROM site_log WHERE (disposedate BETWEEN '%s' AND '%s') AND (isdel=0) ";
        if (!empty($_GET)) {
            switch ($selectId) {
                case 1:
                    $sql = $sql."AND (adminname LIKE '%%%s%%') ";
                    $countSql = $countSql."AND (adminname LIKE '%%%s%%')";
                    break;
                case 2:
                    $sql = $sql."AND (operate LIKE '%%%s%%') ";
                    $countSql = $countSql."AND (operate LIKE '%%%s%%')";
                    break;
                case 3:
                    $sql = $sql." AND (tablename LIKE '%%%s%%')";
                    $countSql = $countSql."AND (tablename LIKE '%%%s%%')";
                    break;
                default :
                    $sql = $sql;
                    $countSql = "SELECT COUNT(ID) FROM site_log WHERE (disposedate BETWEEN '%s' AND '%s') AND isdel=0";
                    break;
            }
        }
        if ($selectId != null) {
            $sql = $sql . "ORDER BY id DESC";
            $ary = [$startDate,$stopDate,$key];
            $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        }
        $this->display();
    }

}
?>