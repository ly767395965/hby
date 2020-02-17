<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Org\Util\Date;
use Org\Util\PinYin;
use Org\Util\PinyinAction;
use Think\Controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/08/14
 * Time: 9:08
 * 超经营区域车辆控制器
 */
class OverRegionController extends BaseController
{
    protected static $table = 'car_barand';
    //超经营区域记录
    public function index(){
        if (isset($_GET['select'])){
            $res['select'] = I('get.select');
            if ($res['select'] == 3){
                $res['auditing_state'] = I('get.auditing_state');
                $ary = [$res['auditing_state']];
            }else{
                $res['key'] = I('get.key');
                $ary = [$res['key']];
            }
            $res['start'] = I('get.start');
            $res['stop'] = I('get.stop');
            $ary[] = $res['start'];
            $ary[] = $res['stop'];

            switch ($res['select']){
                case '0':
                    $where = " WHERE c.VehicleNo LIKE '%%%s%%' ";
                    break;
                case '1':
                    $where = " WHERE b.drivername LIKE '%%%s%%' ";
                    break;
                case '2':
                    $where = " WHERE d.OrderCode LIKE '%%%s%%' ";
                    break;
                case '3':
                    $where = " WHERE a.OverRegion = %d ";
                    break;
            }
            $where .= " AND (a.addtime BETWEEN '%s' AND '%s') ";
        }else{
            $where = " WHERE (a.addtime BETWEEN '%s' AND '%s') ";
            $res['start'] = Date('Y-m-d H:i:s',time()-2592000);
            $res['stop'] = Date('Y-m-d H:i:s',time());
            $ary = [$res['start'],$res['stop']];
        }
        $this->assign('res',$res);
        cookie('excel_query',json_encode($res));


        $sql = "SELECT a.id,b.drivername,c.VehicleNo,d.OrderCode, e.region_name as Aboard,f.region_name as Debus,a.OverRegion,a.addtime FROM over_region a LEFT JOIN car_driverinfo b ON a.DriverId=b.id LEFT JOIN car_net c ON a.CarId=c.id LEFT JOIN order_settlement d ON a.OrderCode=d.OrderCode LEFT JOIN address_num e ON a.AboardAddress=e.address LEFT JOIN address_num f ON a.DebusAddress=f.address".$where;
        $countSql = "SELECT count(a.id) FROM over_region a LEFT JOIN car_driverinfo b ON a.DriverId=b.id LEFT JOIN car_net c ON a.CarId=c.id LEFT JOIN order_settlement d ON a.OrderCode=d.OrderCode LEFT JOIN address_num e ON a.AboardAddress=e.address LEFT JOIN address_num f ON a.DebusAddress=f.address".$where;
        $this->pageDisplay($sql, $countSql, 12, $ary, 'count(id)', 'list', 'page', true); //分页显示
        $this->display();
    }

    //超经营区域审核方法
    public function checkOrder($ordercode){
        $sql = "SELECT AboardPolitics,DebusPolitics,DriverId,CarId,OrderCode FROM order_settlement WHERE OrderCode=%d";
        $order = M()->query($sql,[$ordercode]);
        $sql = "SELECT address FROM address_num WHERE Is_operate = 1 AND Is_del = 0";
        $address = M()->query($sql);
        foreach ($address as $key => $val){
            if ($order[0]['aboardpolitics'] == $address[0]['address']){
                $res['aboardpolitics'] = 1;
            }
            if ($order[0]['debuspolitics'] == $address[0]['address']){
                $res['debuspolitics'] = 1;
            }
        }

        if ($res['aboardpolitics'] == 1 && $res['debuspolitics'] == 1){
            $data['OverRegion'] = 3;
        }
        if ($res['aboardpolitics'] == 1){
            $data['OverRegion'] = 1;
        }
        if ($res['aboardpolitics'] == 1){
            $data['OverRegion'] = 2;
        }

        $data['DriverId'] = $order[0]['DriverId'];
        $data['CarId'] = $order[0]['CarId'];
        $data['OrderCode'] = $order[0]['OrderCode'];
        $data['AboardAddress'] = $order[0]['aboardpolitics'];
        $data['DebusAddress'] = $order[0]['debuspolitics'];
        $data['addtime'] = Date('Y-m-d H:i:s',time());
        M()->add($data);

    }


    //经营区域管理
    public function management(){
        if (isset($_GET['select'])){
            $res['select'] = I('get.select');
            if ($res['select'] == 2){
                $res['Is_operate'] = I('get.Is_operate');
                $ary = [$res['Is_operate'],$res['Is_operate']];
            }else{
                $res['key'] = I('get.key');
                $ary = [$res['key'],$res['key']];

            }
            $this->assign('res',$res);

            switch ($res['select']){
                case '0':
                    $where = " AND region_name LIKE '%%%s%%' ";
                    break;
                case '1':
                    $where = " AND address LIKE '%%%s%%' ";
                    break;
                case '2':
                    $where = " AND Is_operate=%d ";
                    break;
            }
        }else{
            $where = '';
            $ary = [];
        }
        $sql = "SELECT id,region_name,`leave`,address,Is_operate FROM address_num WHERE Is_del=0 AND Is_operate=1".$where." UNION SELECT id,region_name,`leave`,address,Is_operate FROM address_num WHERE Is_del=0 ".$where."AND Is_operate=0 ORDER BY address ASC";
        $countSql = "SELECT count(id) FROM address_num WHERE Is_del = 0".$where;

        $this->pageDisplay($sql, $countSql, 12, $ary, 'count(id)', 'list', 'page', true); //分页显示
        $this->display();
    }

    //添加行政区域信息
    public function addManagement(){
        if (empty($_POST)){
            $sql = "SELECT id,region_name,pid FROM address_num WHERE `leave`=0 AND Is_del=0";
            $province = M()->query($sql);
            $sql = "SELECT id,region_name FROM address_num WHERE `pid`=%d AND Is_del=0";
            $city = M()->query($sql,[$province[0]['id']]);
            $this->assign('province',$province);
            $this->assign('city',$city);
            $this->display();
        }else{
            $rules = array(
                array('region_name', 'require', '<script>alert("地域名不能为空！");history.back(-1);</script>', 0),
                array('address', 'require', '<script>alert("行政区编号不能为空！");history.back(-1);</script>', 0),
            );
            $address = M('address_num');
            if (!$address->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($address->getError());
            } else {
                $data['region_name'] = I('post.region_name');//地域名
                $data['address'] = I('post.address');//行政区划编号
                $data['leave'] = I('post.leave');//地域级别
                $data['Is_operate'] = I('post.Is_operate');//是否为经营区域
                switch ($data['leave']){
                    case '1':
                        $data['pid'] = I('post.province');
                        break;
                    case '2':
                        $data['pid'] = I('post.city');
                        break;
                }
                $add = $address->add($data);

                if ($add){
                    $auserInfo = UserInfo();
                    self::writeLog('address_num',$add, 'addManagement',Date('Y-m-d H:i:sA'),$auserInfo['name']);
                    exit('<script type="text/javascript">alert("地域信息录入成功!");location.href="'.U('NetCarInfo/Index').'";</script>');
                }else{
                    exit('<script type="text/javascript">alert("地域信息录入失败!");history.back(-1);</script>');
                }
            }
        }
    }

    //修改行政区域信息
    public function editManagement(){
        if (empty($_POST)){
            $id = I('get.id');
            if ($id){
                $sql = "SELECT id,region_name,pid,`leave`,address,Is_operate FROM address_num WHERE id=%d AND Is_del=0";
                $list = M()->query($sql,[$id])[0];
                $sql = "SELECT id,region_name,pid FROM address_num WHERE `leave`=0 AND Is_del=0";
                $province = M()->query($sql);
                if ($list['leave'] == 2){
                    $sql = "SELECT id,region_name FROM address_num WHERE `pid`=%d AND Is_del=0";
                    $city = M()->query($sql,[$province[0]['id']]);
                    $this->assign('city',$city);
                }

                $this->assign('list',$list);
                $this->assign('province',$province);

                $this->display('addManagement');
            }else{
                exit('<script type="text/javascript">alert("未获取到信息id!");history.back(-1);</script>');
            }
        }else{
            $rules = array(
                array('region_name', 'require', '<script>alert("地域名不能为空！");history.back(-1);</script>', 0),
                array('address', 'require', '<script>alert("行政区编号不能为空！");history.back(-1);</script>', 0),
            );
            $address = M('address_num');
            if (!$address->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($address->getError());
            } else {
                $data['region_name'] = I('post.region_name');//地域名
                $data['address'] = I('post.address');//行政区划编号
                $data['leave'] = I('post.leave');//地域级别
                $data['Is_operate'] = I('post.Is_operate');//是否为经营区域
                switch ($data['leave']){
                    case '1':
                        $data['pid'] = I('post.province');
                        break;
                    case '2':
                        $data['pid'] = I('post.city');
                        break;
                }
                $where['id'] = I('post.id');

                $save = $address->where($where)->save($data);
                if ($data['Is_operate'] == 0){
                    $this->regionEdit($where['id'],$data['Is_operate']);//改变子地域或父地域状态
                }else{
                    $this->regionEdit($data['pid'],$data['Is_operate']);//改变子地域或父地域状态
                }
                if ($save){
                    $auserInfo = UserInfo();
                    self::writeLog('address_num',$where['id'], 'editManagement',Date('Y-m-d H:i:sA'),$auserInfo['name']);
                    exit('<script type="text/javascript">alert("地域信息修改成功!");location.href="'.U('OverRegion/management').'";</script>');
                }else{
                    exit('<script type="text/javascript">alert("地域信息修改失败!");history.back(-1);</script>');
                }
            }
        }
    }

    //子地域经营权修改
    public function regionEdit($req,$Is_operate){
        if ($Is_operate == 0){
            $sql = "SELECT id,pid FROM address_num WHERE pid =%d AND Is_operate=1";//找出所有pid等于这个id的记录(子地域)
            $data['Is_operate'] = 0;
        }else{
            $sql = "SELECT id,pid FROM address_num WHERE id =%d AND Is_operate=0";//找出所有id等于这个pid的记录(父地域)
            $data['Is_operate'] = 1;
        }
        $msg = M()->query($sql,[$req]);
        if ($msg){
            foreach ($msg as $key=>$val){
                M('address_num')->where(array('id'=>$val['id']))->save($data);
                if ($Is_operate == 0){
                    $this->regionEdit($val['id'],$Is_operate);
                }else{
                    $this->regionEdit($val['pid'],$Is_operate);
                }
            }
        }

    }

    //删除经营区域信息
    public function delManagement(){
        $id = I('get.id');
        $sql = 'UPDATE address_num SET Is_del=1 WHERE id=%d';
        $del = M()->execute($sql,[$id]);
        if ($del){
            $auserInfo = UserInfo();
            self::writeLog('address_num',$id, 'delManagement',Date('Y-m-d H:i:sA'),$auserInfo['name']);
            exit('<script type="text/javascript">alert("该条信息已被删除");location.href="'.U('OverRegion/management').'";</script>');
        }else{
            exit('<script type="text/javascript">alert("删除失败!");history.back(-1);</script>');
        }
    }

    //城市查询ajax
    public function cityAjax(){
        $pid = I('get.pid');
        $sql = "SELECT id,region_name FROM address_num WHERE `pid`=%d AND Is_del=0";
        $city = M()->query($sql,[$pid]);
        if ($city){
            $res['list'] = $city;
            $res['error'] = 0;
        }else{
            $res['error'] = 1;
        }
        echo json_encode($res);
    }

    //excel导出,数组组装
    public function excelAry(){
        if (isset($_POST['file_name']) && isset($_POST['ex_type']) && $_POST['file_name'] != ''){
            $expTitle = I('post.file_name');
            $ex_type = I('post.ex_type');
            $excel_query = json_decode($_COOKIE['excel_query']);

            if ($excel_query->select){      //判断是否有查询条件
                $res['select'] = $excel_query->select;
                if ($res['select'] == 3){
                    $res['auditing_state'] = I('get.auditing_state');
                    $ary = [$res['auditing_state']];
                }else{
                    $res['key'] = $excel_query->key;
                    $ary = [$res['key']];
                }
                $res['start'] = $excel_query->start;
                $res['stop'] = $excel_query->stop;
                $ary[] = $res['start'];
                $ary[] = $res['stop'];

                switch ($res['select']){
                    case '0':
                        $where = " WHERE c.VehicleNo LIKE '%%%s%%' ";
                        break;
                    case '1':
                        $where = " WHERE b.drivername LIKE '%%%s%%' ";
                        break;
                    case '2':
                        $where = " WHERE d.OrderCode LIKE '%%%s%%' ";
                        break;
                    case '3':
                        $where = " WHERE a.OverRegion = %d ";
                        break;
                }
                $where .= " AND (a.addtime BETWEEN '%s' AND '%s') ";
            }else{
                $where = " WHERE (a.addtime BETWEEN '%s' AND '%s') ";
                $ary = [$excel_query->start,$excel_query->stop];
            }

            $sql = "SELECT a.id,b.drivername,c.VehicleNo,d.OrderCode, e.region_name as Aboard,f.region_name as Debus,a.OverRegion,a.addtime FROM over_region a LEFT JOIN car_driverinfo b ON a.DriverId=b.id LEFT JOIN car_net c ON a.CarId=c.id LEFT JOIN order_settlement d ON a.OrderCode=d.OrderCode LEFT JOIN address_num e ON a.AboardAddress=e.address LEFT JOIN address_num f ON a.DebusAddress=f.address".$where;

            $list = M()->query($sql,$ary);
            if ($list){
                $expCellName = [];
                $expCellName[] = ['id','编号'];
                $expCellName[] = ['vehicleno','车牌号'];
                $expCellName[] = ['ordercode','订单号'];
                $expCellName[] = ['aboard','上车点行政区'];
                $expCellName[] = ['debus','下车点行政区'];
                $expCellName[] = ['overregion','超经营区域情况'];
                $expCellName[] = ['addtime','添加时间'];

                foreach ($list as $key => &$val){
                    $val['ordercode'] .= ' ';
                    switch ($val['overregion']){
                        case '1':
                            $val['overregion'] = '下车点超经营区域';
                            break;
                        case '2':
                            $val['overregion'] = '上车点超经营区域';
                            break;
                        case '3':
                            $val['overregion'] = '上下车点超经营区域';
                            break;
                    }
                }

                $expTableData = $list;
                $this->exportExcel($expTitle,$expCellName,$expTableData,$ex_type);
            }else{
                exit('<script type="text/javascript">alert("文件内容不能为空!");history.back(-1);</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("文件名或文件类型不能为空!");history.back(-1);</script>');
        }
    }

    //导出excel 引用类方法
    public function exportExcel($expTitle,$expCellName,$expTableData,$ex_type){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $_SESSION['account'].date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);
//        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);//水平方向上两端对齐  (该指令无效)
        $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(array('font' => array ('bold' => true),'alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));//加粗并居中
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8

        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
//        for($j=0;$j<$cellNum;$j++){
//            $objPHPExcel->getActiveSheet()->getStyle($cellName[$j].($i+2))->getFont()->setSize(12);       //设置字体大小
//            $objPHPExcel->getActiveSheet()->getStyle($cellName[$j].($i+2))->getFont()->setBold(true);     //设置字体粗细
//        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);    //设置列的宽度为自动
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);    //设置列的宽度为自动
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(13);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(13);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(18);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);

        ob_end_clean();//清除缓冲区,避免乱码
//        header('pragma:public');
//        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
//        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印

        if ($ex_type){
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$xlsTitle.'.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
        }else{
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename='.$xlsTitle.'.xls');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
        }
        exit;
    }

}