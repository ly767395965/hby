<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Org\Util\Date;
use Think\Controller;
class CoupontypeController extends BaseController {
    //访问数据库    定义为静态方法
    static function BollarclassModel(){
        return $couponModel = M('coupon_activity');
    }
    static  function relse(){
        return $rules = array(
            array('name','require','优惠劵名称不能为空！',0), //默认情况下用正则进行验证
            array('name','2,10','优惠劵名称长度不符！',0,'length'), // 验证标题长度
            array('start_time','require','活动开始时间不能为空！',0), //默认情况下用正则进行验证
            array('end_time','require','活动结束时间不能为空！',0), //默认情况下用正则进行验证
            array('limit','require','客户可参与次数不能为空！',0), //默认情况下用正则进行验证

        );
    }

    //查看优惠活动
    public function index(){
//        $coupon_sql = "SELECT * FROM coupon_bollar WHERE is_del = 0";
        $sql = "SELECT a.*, b.coupon_name FROM coupon_activity a LEFT JOIN coupon_bollar b ON a.coupon_id=b.id  WHERE a.is_del = 0";
        $ary = array();
        $countSql = "SELECT count(id) FROM coupon_activity WHERE is_del = 0";

        $this->pageDisplay($sql,$countSql,20,$ary,'count(id)','list','page',true);

//        $this->assign('sex',$this->sexArray);
        $this->display();
    }
    //添加优惠活动
    public function add(){
        $Db = self::BollarclassModel();
        $rules = self::relse();

        if($_POST) {
            if (!$Db->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert("'.$Db->getError().'");history.go(-1);</script>');

            } else {
                $data['name'] = I('post.name');
                $data['status'] = 1;
                $data['act_synopsis'] = I('post.act_synopsis');
                $data['act_content'] = I('post.act_content');
                $data['addtime'] = Date('Y-m-d H:i:s');
                $data['start_time'] = I('post.start_time');
                $data['end_time'] = I('post.end_time');
                $data['limit'] = I('post.limit');

                if($data['limit'] < 1 && $data['limit'] != -1){
                    exit('<script type="text/javascript">alert("客户可参与次数填写错误");history.go(-1);</script>');
                }
                $data['coupon_id'] = I('post.coupon_id');
                $data['act_type'] = I('post.act_type');
                $add = $Db->add($data);
                if ($add) {
                    exit('<script type="text/javascript">alert("添加成功");location.href="' . U('Coupontype/Index') . '";</script>');
                } else {
                    exit('<script type="text/javascript">alert("添加失败");history.go(-1);</script>');
                }
            }
        }
        $this->display();
    }
    //修改优惠活动
    public function edit(){
        $Db=self::BollarclassModel();
        $rules = self::relse();

        if(!empty($_POST)){
            if (!$Db->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit('<script type="text/javascript">alert(".'.$Db->getError().'.");history.go(-1);</script>');
            } else{
//                $data['coupon_id']=I('post.coupon_id');
//                $coupon_info = M('coupon_bollar')->where(array('id'=>$data['coupon_id'],'is_del'=>0))->select();
//                if (!$coupon_info){
//                    exit('<script type="text/javascript">alert("你选择的优惠劵不存在");history.go(-1);</script>');
//                }

                $data['name'] = I('post.name');
//                $data['status'] = I('post.status');
                $data['act_synopsis'] = I('post.act_synopsis');
                $data['act_content'] = I('post.act_content');
                $data['start_time'] = I('post.start_time');
                $data['end_time'] = I('post.end_time');
                $data['limit'] = I('post.limit');

                if($data['limit'] < 1 && $data['limit'] != -1){
                    exit('<script type="text/javascript">alert("客户可参与次数填写错误");history.go(-1);</script>');
                }
                $data['coupon_id'] = I('post.coupon_id');
                $data['act_type'] = I('post.act_type');

                $id = I('get.id');
                $Bollarsave=$Db->where(array('id'=>$id))->save($data);
                if($Bollarsave){
                    exit('<script type="text/javascript">alert("修改成功");location.href="'.U('Coupontype/Index').'";</script>');
                }else{
                    exit('<script type="text/javascript">alert("修改失败");history.go(-1);</script>');
                }
            }
        }else{
            if(!empty($_GET['id'])){
                $couponFind=$Db->where(array('id'=>$_GET['id']))->find();
                $this->assign('list',$couponFind);
                $this->display('add');
            }else{
                exit('<script type="text/javascript">alert("参数错误");history.go(-1);</script>');
            }
        }

    }
    //派发优惠劵
    public function distribute(){
        $coupon_act = self::BollarclassModel();
        $m = M();
        if (!empty($_POST)){
            //获取需要添加的id
            $distribute_type = I('distribute_type');
            switch ($distribute_type){      //判断发放对象,并组装发放客户id
                case 0:
                    $ids = I('post.ids');
                    $id_ary = explode(",", $ids);
                    array_pop ($id_ary);
                    break;
                case 1:
                    $id_ary = $m->table('work_member')->select();
                    break;
                case 3:
                    $id_ary[]['id'] = I('post.ids');
                    break;
                default:
                    $reg['code'] = 0;
                    $reg['msg'] = '该功能还未开放！';
                    $this->ajaxReturn($reg);
                    exit;
            }

            $coupon_id = $_SESSION['coupon_act']['coupon_id'];
            $coupon_info = $m->table('coupon_bollar')->where(array('id'=>$coupon_id))->find();
            if ($coupon_info['number'] != -1 && ($coupon_info['sum']+count($id_ary)) >= $coupon_info['number']){
                $reg['code'] = 0;
                $reg['msg'] = '发送的优惠劵剩余数量不足';
                $this->ajaxReturn($reg);
                exit;
            }

            $coupon_bollar['sum'] = $coupon_info['sum']+count($id_ary);

            $data['bid'] = $coupon_info['id'];
            $data['coupon_type'] = $coupon_info['type'];
            $data['money'] = $coupon_info['money'];
            $data['discount'] = $coupon_info['discount'];
            $data['min_consume'] = $coupon_info['min_consume'];
            $data['addtime'] = date("Y-m-d H:i:s");
            $data['updatetime'] = $data['addtime'];
            switch ($coupon_info['time_limit']){
                case '0':
                    $data['termofvalidity'] = $coupon_info['termofvalidity'];
                    break;
                case '1':
                    $data['termofvalidity'] = Date('Y-m-d H:i:s',strtotime("+ ".$coupon_info['termofvaliditytian']." day"));
                    break;
            }

            for ($i=0;$i<count($id_ary);$i++){
                if ($distribute_type == 0){
                    $data['uid'] = $id_ary[$i];
                }else{
                    $data['uid'] = $id_ary[$i]['id'];
                }
                $dataAll[] = $data;
            }
            $res = $m->table('coupon_bollaruser')->addAll($dataAll);
            if ($res) {
                $m->table('coupon_bollar')->where(array('id'=>$coupon_id))->save($coupon_bollar);
                foreach ($id_ary as $val) {
                    self::writeLog('coupon_bollaruser', $val, '直接发放优惠卷', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $reg['code'] = 1;
                $reg['msg'] = '发放成功';
                $this->ajaxReturn($reg);
            }
        }else{
            if (isset($_GET['select'])){
                $msg['select'] = I('get.select');
                $msg['key'] = I('get.key');
                $this->assign('msg',$msg);
            }else{
                $couponact_id = I('get.id');
                $act_info = $coupon_act->where(array('id'=>$couponact_id))->find();
                if ($act_info){
                    session('coupon_act',$act_info);
                }else{
                    exit('<script type="text/javascript">alert("参数错误");history.go(-1);</script>');
                }
            }
            $sql = "SELECT a.id, a.username, a.phone, a.usertype, a.identitys, b.coupon_times FROM work_member a LEFT JOIN (SELECT COUNT(*) as coupon_times, uid FROM coupon_bollaruser WHERE bid='%d' GROUP BY uid HAVING coupon_times > 0 ) b ON a.id=b.uid WHERE a.state=0";
            $countSql = "SELECT count(id) FROM work_member a LEFT JOIN (SELECT COUNT(*) as coupon_times, uid FROM coupon_bollaruser WHERE bid='%d' GROUP BY uid HAVING coupon_times > 0 ) b ON a.id=b.uid WHERE a.state=0";
            $ary = [$act_info['coupon_id']];
            if (isset($msg['select'])){
                switch ($msg['select']){
                    case 0:
                        $sql .= " AND a.username LIKE '%%%s%%'";
                        $countSql .=" AND a.username LIKE '%%%s%%'";
                        break;
                    case 1:
                        $sql .= " AND a.phone LIKE '%%%s%%'";
                        $countSql .=" AND a.phone LIKE '%%%s%%'";
                        break;
                }
                $ary = [$act_info['coupon_id'],$msg['key']];
            }
            $this->pageDisplay($sql,$countSql,20,$ary,'count(id)','list','page',true);
            $this->display();
        }
    }
    //删除优惠劵类型
    public function  delete(){
        $id=I('get.id');
        if(!empty($id)){
            $Db=self::BollarclassModel();
            $data['is_del'] = 1;
            $data['status'] = 0;
            $deleUser = $Db->where(array('id'=>$id))->save($data);
            if($deleUser){
                exit('<script type="text/javascript">alert("删除成功");location.href="'.U('Coupontype/Index').'";</script>');
            }else{
                exit('<script type="text/javascript">alert("删除失败");history.go(-1);</script>');
            }
        }
    }
    //优惠活动禁启用
    public function status(){
        if (isset($_GET['id']) && isset($_GET['status'])){
            $Db = self::BollarclassModel();
            $id = I('get.id');
            $status = I('get.status');
            $auserInfo = UserInfo();
            if ($status){
                $reg = $Db->where(array('id'=>$id))->save(array('status'=>1));
                if ($reg){
                    $log = self::writeLog('coupon_activity', $id, '禁用优惠活动', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        exit('<script type="text/javascript">alert("禁用成功！");location.href="' . U('Index') . '";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("禁用失败，请重试！");history.go(-1);</script>');
                }
            }else{
                $act_type = I('get.act_type');
                $judge = $Db->query('SELECT id FROM coupon_activity WHERE id != %d AND act_type = %d AND status = 0 AND is_del = 0',[$id,$act_type]);
                if ($judge){
                    exit('<script type="text/javascript">alert("请先禁用其他相同类型的活动!");history.go(-1);</script>');
                }else{
                    $reg = $Db->where(array('id'=>$id))->save(array('status'=>0));
                }

                if ($reg){
                    $log = self::writeLog('coupon_activity', $id, '启用优惠活动', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        exit('<script type="text/javascript">alert("启用成功！");location.href="' . U('Index') . '";</script>');
                    }
                }else{
                    exit('<script type="text/javascript">alert("启用失败，请重试！");history.go(-1);</script>');
                }
            }
        }
    }
    
    //优惠劵信息查询
    public function couponQuery(){
        if (isset($_GET['act'])){
            $act = I('get.act');
            if($act == 'choose'){
                $query_info = I('get.query_info');
                $couponbollar = I('get.couponbollar');
                if ($query_info != ''){
                    switch ($couponbollar){
                        case 0:
                            $where['id'] = $query_info;
                            break;
                        case 1:
                            $where['coupon_name'] = array('like',"{$query_info}%");
                            break;
                    }
                    $reg['query'] = $couponbollar;
                    $reg['query_key'] = $query_info;
                }
                $where['issue_state'] = 0;
                $where['state'] = 0;
                $where['is_del'] = 0;
                $content = M('coupon_bollar')->where($where)->select();

                foreach ($content as $key => &$val){
                    switch ($val['time_limit']){
                        case 0:
                            $val['validity_day'] = explode(' ',$val['termofvalidity'])[0];
                            break;
                        case 1:
                            $val['validity_day'] = $val['termofvaliditytian'].'天';
                            break;
                        case 2:
                            $val['validity_day'] = '永久';
                            break;
                    }
                    if ($val['number'] < 0){
                        $val['number'] = '无限';
                    }
                }



                $reg['content'] = $content;
            }
            if ($content){
                $reg['code'] = 1;
            }else{
                $reg['code'] = 0;
                $reg['msg'] = '操作出错';
            }

        }else{
            $reg['code'] = 0;
            $reg['msg'] = '未知操作';
        }

        $this->ajaxReturn($reg);
    }
}