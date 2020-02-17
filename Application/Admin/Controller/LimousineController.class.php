<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

class LimousineController extends BaseController {
    public function index(){
        $sql = "SELECT a.id,a.bossid,a.schoolid,b.schoolname,c.username,(SELECT COUNT(id) FROM sc_carts WHERE bossid=a.bossid) as cartNumber FROM sc_school a LEFT JOIN sc_academy b ON a.schoolid = b.id LEFT JOIN work_member c ON a.bossid = c.id ";
        //获取数据总记录数量
        $countSql = "SELECT COUNT(a.id) FROM sc_school a LEFT JOIN sc_academy b ON a.schoolid = b.id LEFT JOIN work_member c ON a.bossid = c.id ";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(0);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, $countSql, 16, '', 'count(a.id)', 'list', 'page', true);
        $this->display();

    }

    //查看当前学校的车辆信息
    public function seeCarts(){
        $bossid = I('get.bossid');
        $m = M();
        $sql = "SELECT a.id,b.carno,c.sitecount FROM sc_carts a LEFT JOIN car_carinfo b ON a.cartid=b.id LEFT JOIN car_carmodel c ON b.carmodel=c.id WHERE a.bossid='%d'";
        $list = $m->query($sql,[$bossid]);
    }



    //查看当前学校的车辆
    public function lookCarts(){
        $uid = I('get.id');
        $schoolid = I('get.schoolid');
        $m = M();
        $sql = "SELECT a.id,a.bossid,a.schoolid,b.schoolname,c.username,d.id as cardataid,d.linenumber,i.route_name,e.id as orderid,e.drive_state,g.carno,h.drivername FROM sc_school a LEFT JOIN sc_academy b ON a.schoolid = b.id LEFT JOIN work_member c ON a.bossid = c.id LEFT JOIN sc_carts d ON a.bossid=d.bossid LEFT JOIN `order` e ON d.orderid=e.id LEFT JOIN order_car f ON d.cartid=f.carid LEFT JOIN car_carinfo g ON f.carid=g.id LEFT JOIN car_driverinfo h ON f.driveid=h.id LEFT JOIN sc_route i ON d.linenumber=i.id WHERE a.bossid = '%d' AND a.schoolid = '%d' AND e.order_state = '%d' GROUP BY d.id";
        $list = $m->query($sql,[$uid,$schoolid,3]);
        $this->assign('list',$list);
//        $routeList = $this->seeRoute($schoolid);
//        $this->assign('route',$routeList);
        $this->display();

    }

    //查看当前学校的路线
    public function seeRoute($schoolid){
        $m = M();
        $sql = "SELECT id,route_name FROM sc_route WHERE school_id = '%d' AND is_enable = '%d' AND is_delete = '%d'";
        $routeList = $m->query($sql,[$schoolid,0,0]);
        return $routeList;
    }

    //给车辆分配路线
    public function  allotRoute(){
        $m = M();
        $Route = I('post.Route');
        $carid = I('post.carid');
        $sql = "UPDATE sc_carts SET linenumber = '%d' WHERE id = '%d'";
        $res = $m->execute($sql,[$Route,$carid]);
        $this->ajaxReturn($res);
    }


    //查看当前院校线路信息
    public function routeView(){
        $schoolid = I('get.schoolid');
        cookie('school_id',$schoolid);
        $sql = "SELECT * FROM sc_route WHERE school_id=%d AND is_delete = 0";
        $route = M()->query($sql,[$schoolid]);

        $sql = "SELECT COUNT(id) as enable_num FROM sc_route WHERE school_id=%d AND is_enable=0";
        $enable_num = M()->query($sql,[$schoolid]);

        foreach ($route as $key=>$val){
            $route[$key]['route_length_q'] = round($route[$key]['route_length_q']/1000,2);
        }
        $this->assign('route',$route);
        $this->assign('route_num',count($route));
        $this->assign('enable_num',$enable_num[0]['enable_num']);
        $this->display();
    }

    //查询站点信息
    public function siteQuery(){
        $route_id = I('post.route_id');
        if (!$route_id){
            $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
        }else{
            $sql = "SELECT * FROM sc_route_site WHERE route_id=%d ORDER BY site_order ASC";
            $site = M()->query($sql,[$route_id]);
            if ($site){
                $rs = array('error'=>0,'code'=>0,'msg'=>'success','list'=>$site);
            }else{
                $rs = array('error'=>1,'code'=>2,'msg'=>'查询结果为空');
            }
        }
        echo json_encode($rs);
    }
    
    //站点信息更新
    public function changeSite(){
        $type = I('post.type');
        $data['site_id'] = I('post.site_id');
        $data['route_id'] = I('post.route_id');
        $data['site_name'] = I('post.site_name');
        $data['site_order'] = I('post.site_order');
        $data['site_lng'] = I('post.site_lng');
        $data['site_lat'] = I('post.site_lat');
        $data['is_site'] = I('post.is_site');
        $data['direction'] = I('post.direction');
        switch ($type){
            case 0:
                if ($this->checkSiteInfo($data)){
                    $rs = $this->addSite($data);
                }else{
                    $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
                }
                break;
            case 1:
                if ($this->checkSiteInfo($data)){
                    $rs = $this->editSite($data);
                }else{
                    $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
                }
                break;
            case 2:
                if ($data['site_id']){
                    $rs = $this->deleteSite($data);
                }else{
                    $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
                }
                break;
            default:
                $rs = array('error'=>1,'code'=>2,'msg'=>'操作类型获取失败');
        }
        $rs['type'] = $type;
        echo json_encode($rs);
    }

    //添加站点信息
    public function addSite($data){
        $model = M();
        $model->startTrans();

        $add['route_id'] = $data['route_id'];
        $add['is_site'] = $data['is_site'];
        $add['site_name'] = $data['site_name'];
        $add['site_order'] = $data['site_order'];
        $add['direction'] = $data['direction'];
        $add['site_lng'] = $data['site_lng'];
        $add['site_lat'] = $data['site_lat'];
        $add['updatetime'] = date('Y-m-d H:i:s');
        $add['addtime'] = $add['updatetime'];
        $sql = "SELECT id FROM sc_route_site WHERE route_id=%d AND site_order >= %d";
        $site_info = M()->query($sql,[$data['route_id'],$data['site_order']]);

        $add_rs = $model->table('sc_route_site')->add($add);//添加站点信息
        if ($add_rs){
            $rs = array('error'=>0,'code'=>0,'msg'=>'success','list'=>$add_rs);
            if ($site_info){
                foreach ($site_info as $val){
                    $sql = "UPDATE sc_route_site SET site_order=site_order+1 WHERE id=".$val['id'];//整合以前的站点序号
                    $update = $model->execute($sql);
                    if (!$update){
                        $rs = array('error'=>1,'code'=>4,'msg'=>'站点序号更新失败');
                        break;
                    }
                }
            }
            $this->updateRoute($data['route_id']);
            $model->commit();
        }else{
            $rs = array('error'=>1,'code'=>3,'msg'=>'添加站点失败');
            $model->rollback();
        }
        return $rs;
    }

    //修改站点信息
    public function editSite($data){
        $model = M();
        $model->startTrans();
        $save['site_name'] = $data['site_name'];
        $save['site_lng'] = $data['site_lng'];
        $save['site_lat'] = $data['site_lat'];
        $save['site_order'] = $data['site_order'];
        $save['is_site'] = $data['is_site'];
        $save['direction'] = $data['direction'];
        $save['updatetime'] = date('Y-m-d H:i:s');
        $save_rs = $model->table('sc_route_site')->where(array('id'=>$data['site_id']))->save($save);
        if ($save_rs){
            $model->commit();
            $rs = array('error'=>0,'code'=>0,'msg'=>'success','list'=>$save_rs);
        }else{
            $model->rollback();
            $rs = array('error'=>1,'code'=>3,'msg'=>'修改站点信息失败');
        }
        return $rs;
    }

    //删除站点信息
    public function deleteSite($data){
        $model = M();
        $model->startTrans();
        $sql = "DELETE FROM sc_route_site WHERE id = %d";
        $de_rs = $model->execute($sql,[$data['site_id']]);
        if ($de_rs){
            $rs = array('error'=>0,'code'=>0,'msg'=>'success');
            $sql = "SELECT id,route_id FROM sc_route_site WHERE route_id=%d AND site_order >= %d";
            $sites_info = $model->query($sql,[$data['route_id'],$data['site_order']]);
            foreach ($sites_info as $val){
                $sql = "UPDATE sc_route_site SET site_order=site_order-1 WHERE id=".$val['id'];//整合以前的站点序号
                $update = $model->execute($sql);
                if (!$update){
                    $rs = array('error'=>1,'code'=>4,'msg'=>'站点序号更新失败');
                    break;
                }
            }
            $this->updateRoute($data['route_id']);
            $model->commit();

        }else{
            $model->rollback();
            $rs = array('error'=>1,'code'=>3,'msg'=>'删除站点失败');
        }
        return $rs;
    }

    //更新路线站点数
    public function updateRoute($route_id){
        $sql = "SELECT count(id) as site_num FROM sc_route_site WHERE route_id=%d AND is_site=1 AND direction != 2 UNION ALL SELECT count(id) as site_num FROM sc_route_site WHERE route_id=%d AND is_site=1 AND direction != 1";
        $route_info = M()->query($sql,[$route_id,$route_id]);
        $sql = "UPDATE sc_route SET site_num_q=%d,site_num_h=%d WHERE id=%d";
        $up = M()->execute($sql,[$route_info[0]['site_num'],$route_info[1]['site_num'],$route_id]);
        return $route_info;
    }
    
    //更新路线长度
    public function udRouteLength(){
        $route_id = I('post.route_id');
        $route_length = I('post.route_length');
        $type = I('post.type');
        $sql = "UPDATE sc_route SET ";
        if ($type == 1){
            $sql .= " route_length_q = %d";
        }else{
            $sql .= " route_length_h = %d";
        }
        $sql .= " WHERE id=%d";
        $save = M()->execute($sql, [$route_length, $route_id]);
        if ($save){
            $rs = array('error'=>0,'code'=>0,'msg'=>'success');
        }else{
            $rs = array('error'=>1,'code'=>1,'msg'=>'更新失败');
        }
        echo json_encode($rs);
    }

    //检测站点信息合法性
    public function checkSiteInfo($data){
        if ($data['site_name'] && $data['site_lng'] && $data['site_lat'] && $data['site_id'] && $data['site_order']){
            return true;
        }else{
            return false;
        }
    }

    //更改线路相关信息
    public function changeRoute(){
        $type = I('post.type');
        $data['school_id'] = cookie('school_id');
        $data['route_id'] = I('post.route_id');
        switch ($type){
            case 0:
                $data['route_name'] = I('post.route_name');
                if ($data['school_id'] && $data['route_name']){
                    $rs = $this->addRoute($data);
                }else{
                    $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
                }
                break;
            case 1:
                $data['route_name'] = I('post.route_name');
                if ($data['route_id'] && $data['route_name']){
                    $rs = $this->editRoute($data);
                    $route_info = M('sc_route')->where(array('id'=>$data['route_id']))->field('id,route_name,route_length_q,site_num_q,is_enable')->find();
                    $rs['list'] = $route_info;
                }else{
                    $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
                }
                break;
            case 2:
                $data['is_enable'] = M('sc_route')->where(array('id'=>$data['route_id']))->getField('is_enable');
                if ($data['is_enable'] == 1){
                    $data['is_enable'] = 0;
                }else if ($data['is_enable'] == 0){
                    $data['is_enable'] = 1;
                }
                if ($data['route_id'] && $data['is_enable'] !== null){
                    $rs = $this->editRoute($data);
                    $rs['is_enable'] = $data['is_enable'];
                }else{
                    $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
                }
                break;
            case 3:
                $data['is_delete'] = 1;
                if ($data['route_id']){
                    $rs = $this->editRoute($data);
                }else{
                    $rs = array('error'=>1,'code'=>1,'msg'=>'参数错误');
                }
                break;
            default:
                $rs = array('error'=>1,'code'=>2,'msg'=>'操作类型获取出错');
        }
        $rs['type'] = $type;
        echo json_encode($rs);
    }

    public function addRoute($data){
        $data['addtime'] = date('Y-m-d H:i:s');
        $add = M('sc_route')->add($data);
        if ($add){
            $data['route_id'] = $add;
            $rs = array('error'=>0,'code'=>0,'msg'=>'success','list'=>$data);
        }else{
            $rs = array('error'=>1,'code'=>3,'msg'=>'添加失败');
        }
        return $rs;
    }

    public function editRoute($data){
        $save = M('sc_route')->where(array('id'=>$data['route_id']))->save($data);
        if ($save){
            $rs = array('error'=>0,'code'=>0,'msg'=>'success','list'=>$save);
        }else{
            $rs = array('error'=>1,'code'=>3,'msg'=>'操作失败');
        }
        return $rs;
    }

}