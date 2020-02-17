<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 * 网约车运价信息控制器
 */
class NetCarFreightController extends BaseController{
    public function index() {
        $startDate = $_GET['start'];//开始时间
        $stopDate = $_GET['stop'];//结束时间
        $key = I('get.key');//接受模糊查询的条件
        $state = I('get.state');//状态
        $selectId = I('get.select');
        $sql = "SELECT id,FareName,FareValidOn,FareValidOff,StartFare,StartMile,UnitPricePerMile,UnitPricePerMinute,MorningPeakTimeOn,MorningPeakTimeOff,EveningPeakTimeOn,EveningPeakTimeOff,PeakUnitPrice,PeakPriceStartMile,AddTime,State FROM transport_price ";
        $countSql = "SELECT COUNT(id) FROM transport_price";
        if (!empty($_GET)) {
            switch ($selectId) {
                case 1:
                    $sql = $sql." WHERE (FareValidOn BETWEEN '%s' AND '%s') ";
                    $countSql = $countSql." WHERE (FareValidOn BETWEEN '%s' AND '%s') ";
                    break;
                case 2:
                    $sql = $sql." WHERE (FareValidOff BETWEEN '%s' AND '%s') ";
                    $countSql = $countSql." WHERE (FareValidOff BETWEEN '%s' AND '%s') ";
                    break;
                case 3:
                    $sql = $sql." WHERE (State = '%d') ";
                    $countSql = $countSql." WHERE (State = '%d')  ";
                    break;
                default;
                    $sql = $sql;
                    $countSql = $countSql;
                    break;
            }
        }
        if ($selectId > 0){
//            $sql = $sql . "ORDER BY id DESC";
            if ($selectId == 1 || $selectId == 2){
                $ary = [$startDate,$stopDate];
            } else {
                $ary = [$state];
            }

            $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true); //分页显示
        } else {
            $ary = [];
            $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true); //分页显示
        }
        $this->display();
    }


    //添加运价信息
    public function addFreightInfo(){
        if (!$_POST){
            $this->display();
        }

        if ($_POST){
            $tab = M('transport_price');
            $data['FareName'] = I('post.FareName');
            $data['Address'] = I('post.Address');
            $data['FareValidOn'] = I('post.FareValidOn');
            $data['FareValidOff'] = I('post.FareValidOff');
            $data['StartFare'] = I('post.StartFare');
            $data['StartMile'] = I('post.StartMile');
            $data['UnitPricePerMile'] = I('post.UnitPricePerMile');
            $data['UnitPricePerMinute'] = I('post.UnitPricePerMinute');
            $data['UpPrice'] = I('post.UpPrice');
            $data['UpPriceStartMile'] = I('post.UpPriceStartMile');
            $data['MorningPeakTimeOn'] = I('post.MorningPeakTimeOn');
            $data['MorningPeakTimeOff'] = I('post.MorningPeakTimeOff');
            $data['EveningPeakTimeOn'] = I('post.EveningPeakTimeOn');
            $data['EveningPeakTimeOff'] = I('post.EveningPeakTimeOff');
            $data['OtherPeakTimeOn'] = I('post.OtherPeakTimeOn');
            $data['OtherPeakTimeOff'] = I('post.OtherPeakTimeOff');
            $data['PeakUnitPrice'] = I('post.PeakUnitPrice');
            $data['PeakPriceStartMile'] = I('post.PeakPriceStartMile');
            $data['LowSpeedPricePerminute'] = I('post.LowSpeedPricePerminute');
            $data['NightPricePerMile'] = I('post.NightPricePerMile');
            $data['NightPricePerMinute'] = I('post.NightPricePerMinute');
            $data['OtherPrice'] = I('post.OtherPrice');
            $data['FareTypeNote'] = I('post.FareTypeNote');
            $data['AddTime'] = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $data['UpdateTime'] = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $data['State'] = 0;
            $data['Flag'] = 1;
            $result = $tab->add($data); // 写入数据到数据库
            if($result){
                $returnid = M('transport_price')->order('id desc')->find();
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                self::writeLog('transport_price', $returnid['id'], 'addFreightInfo', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                $this->success("添加成功!", U('NetCarFreight/Index'));
                $this->infoSub($result);
            } else {
                $this->error("添加成功");
            }
        }
    }

    //修改运价信息
    public function editFreightInfo(){
        if ($_GET){
            $tab = M();
            $id = I('get.id');
            $sql = "SELECT id,FareName,Address,FareValidOn,FareValidOff,StartFare,StartMile,UnitPricePerMile,UnitPricePerMinute,UpPrice,UpPriceStartMile,MorningPeakTimeOn,
            MorningPeakTimeOff,EveningPeakTimeOn,EveningPeakTimeOff,OtherPeakTimeOn,OtherPeakTimeOff,PeakUnitPrice,PeakPriceStartMile,
            LowSpeedPricePerminute,NightPricePerMile,NightPricePerMinute,OtherPrice,FareTypeNote FROM transport_price WHERE id = %d ";
            $list = $tab->query($sql,[$id]);
            $this->assign('list',$list);
            $this->display();
        }

        if ($_POST){
            $tab = M('transport_price');
            $data['FareName'] = I('post.FareName');
            $data['Address'] = I('post.Address');
            $data['FareValidOn'] = I('post.FareValidOn');
            $data['FareValidOff'] = I('post.FareValidOff');
            $data['StartFare'] = I('post.StartFare');
            $data['StartMile'] = I('post.StartMile');
            $data['UnitPricePerMile'] = I('post.UnitPricePerMile');
            $data['UnitPricePerMinute'] = I('post.UnitPricePerMinute');
            $data['UpPrice'] = I('post.UpPrice');
            $data['UpPriceStartMile'] = I('post.UpPriceStartMile');
            $data['MorningPeakTimeOn'] = I('post.MorningPeakTimeOn');
            $data['MorningPeakTimeOff'] = I('post.MorningPeakTimeOff');
            $data['EveningPeakTimeOn'] = I('post.EveningPeakTimeOn');
            $data['EveningPeakTimeOff'] = I('post.EveningPeakTimeOff');
            $data['OtherPeakTimeOn'] = I('post.OtherPeakTimeOn');
            $data['OtherPeakTimeOff'] = I('post.OtherPeakTimeOff');
            $data['PeakUnitPrice'] = I('post.PeakUnitPrice');
            $data['PeakPriceStartMile'] = I('post.PeakPriceStartMile');
            $data['LowSpeedPricePerminute'] = I('post.LowSpeedPricePerminute');
            $data['NightPricePerMile'] = I('post.NightPricePerMile');
            $data['NightPricePerMinute'] = I('post.NightPricePerMinute');
            $data['OtherPrice'] = I('post.OtherPrice');
            $data['FareTypeNote'] = I('post.FareTypeNote');
            $data['UpdateTime'] = date('Y-m-d H:i:s ',$_SERVER['REQUEST_TIME']);
            $data['Flag'] = 2;
            $where['id'] = I('post.id');
            $re = $tab->where($where)->save($data);
            if($re){
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                self::writeLog('transport_price', I('post.id'), 'editFreightInfo', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                $this->success("修改成功!", U('NetCarFreight/Index'));
                $this->infoSub($where['id']);
            } else {
                $this->error("修改成功");
            }
        }

    }

    //标记数据为无效
    public function invalidFreightInfo(){
        $tab = M();
        $id = I('get.id');
        $sql = "UPDATE transport_price SET State = %d WHERE id = %d ";
        $arr = [1,$id];
        $re = $tab->execute($sql,$arr);
        if($re){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog('transport_price', $id, 'invalidFreightInfo', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            echo '<script>alert("已成功标记该条数据为无效");self.location=document.referrer;</script>';
            $this->infoSub($id);
        } else {
            echo '<script>alert("操作失败");self.location=document.referrer;</script>';
        }
    }

    //标记数据为为有效
    public function effectiveFreightInfo(){
        $tab = M();
        $id = I('get.id');
        $sql = "UPDATE transport_price SET State = %d WHERE id = %d ";
        $arr = [0,$id];
        $re = $tab->execute($sql,$arr);
        if($re){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog('transport_price', $id, 'effectiveFreightInfo', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            echo '<script>alert("已成功标记该条数据为有效");self.location=document.referrer;</script>';
            $this->infoSub($id);
        } else {
            echo '<script>alert("操作失败");self.location=document.referrer;</script>';
        }
    }

    //网约车平台公司运价信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoCompanyFare',$id);
    }

}