<?php
namespace Home\Controller;

use Common\Common\BaseHomeController;
use Think\Upload;
use Think\Verify;

/**
 * Class NewsController
 * @package Home\Controller
 * 特惠活动控制器
 */

class ActivityController extends BaseHomeController{

    public function index(){
        $table = M('publish_event');

        //查询特惠活动
        $sql = "SELECT id,theme,describetxt,content,cover,end_time,NOW() as newdate  FROM publish_event WHERE (is_del=0) ORDER BY ID DESC";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(ID) FROM publish_event WHERE (is_del=0)";
        //参数数组，按顺序传递你要传递的参数值
        $ary = [];
        $this->pageDisplayimg($sql, $countSql, 3, $ary, 'count(id)', 'list', 'page','Activity/ajax',true);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title',$sitetitle[0]['title']);
        $this->display();

    }

    //活动内页 ActivityInside()
    public function ActivityInside() {
        $table = M('publish_event');
        $id = $_GET['id'];
        $sql = "SELECT id,theme,describetxt,content FROM publish_event WHERE (id=%d)";
        $arr = [$id];
        $list = $table->query($sql,$arr);
        $this->assign('list',$list);
        //站点信息
        $sitetitle = new IndexController();
        $sitetitle = $sitetitle->webInfo();
        $this->assign('sys_title','优惠活动_'.$sitetitle[0]['title']);
        $this->display();
    }




}