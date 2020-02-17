<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Controller;
use Think\Page;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/08/14
 * Time: 9:08
 * 报备信息预警控制器
 */
class ReportController extends BaseController
{
    //车辆报备异常信息列表
    public function car_index(){
        if (isset($_GET['select'])){
            $res['select'] = I('get.select');
            $res['key'] = I('get.key');
            $ary = [$res['key']];
            $res['start'] = I('get.start');
            $res['stop'] = I('get.stop');
            $ary[] = $res['start'];
            $ary[] = $res['stop'];

            switch ($res['select']){
                case '0':
                    $where = " WHERE a.VehicleNo LIKE '%%%s%%' ";
                    break;
                case '1':
                    $where = " WHERE a.CarId LIKE '%%%s%%' ";
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
        cookie('car_query',json_encode($res));


        $sql = "SELECT a.id,a.CarId,a.VehicleNo,a.PlateColor,a.Seats,a.VehicleColor,a.Model,a.VehicleType,a.EngineId,a.VIN,a.EngineDisplace,a.addtime,b.VehicleNo as fa_VehicleNo,b.PlateColor as fa_PlateColor,b.Seats as fa_Seats,b.VehicleColor as fa_VehicleColor,b.Model as fa_Model,b.VehicleType as fa_VehicleType,c.EngineId as fa_EngineId,c.VIN as fa_VIN,c.EngineDisplace as fa_EngineDisplace FROM report_car_error a LEFT JOIN car_net b ON a.CarId=b.id LEFT JOIN car_info c ON a.CarId=c.CarId".$where;

        $list = M()->query($sql,$ary);
        $data = $this->carAry($list);

        $this->pageInfo($data,count($data),12,'list','page',true);
        $this->display('car_index');
    }

    //对车辆信息进行重组
    function carAry($list){
        $data = [];
        foreach ($list as $key => &$val){
            $data[$key]['id'] = $val['id'];
            $data[$key]['carid'] = $val['carid'];
            $data[$key]['addtime'] = $val['addtime'];
            $data[$key]['vehicleno'] = $val['vehicleno'];
            $data[$key]['times'] = 0;
            unset($val['id'],$val['carid'],$val['addtime'],$val['vehicleno']);
            foreach ($val as $k => $v){
                if (substr($k,0,3) != 'fa_' && $v != false){
                    $data[$key]['times']++;
                    switch ($k){
                        case 'platecolor':
                            $data[$key]['content'][$k]['name'] = '车牌颜色';
                            break;
                        case 'seats':
                            $data[$key]['content'][$k]['name'] = '核定载客位';
                            break;
                        case 'vehiclecolor':
                            $data[$key]['content'][$k]['name'] = '车身颜色';
                            break;
                        case 'Model':
                            $data[$key]['content'][$k]['name'] = '车辆型号';
                            break;
                        case 'EngineId':
                            $data[$key]['content'][$k]['name'] = '发动机号';
                            break;
                        case 'VIN':
                            $data[$key]['content'][$k]['name'] = '车辆VIN码';
                            break;
                        case 'EngineDisplace':
                            $data[$key]['content'][$k]['name'] = '发动机排量';
                            break;
                    }
                    $data[$key]['content'][$k]['true'] = $v;
                    $data[$key]['content'][$k]['false'] = $val['fa_'.$k];
                }
            }
        }

        return $data;
    }

    //驾驶员报备异常信息列表
    public function driver_index(){
        if (isset($_GET['select'])){
            $res['select'] = I('get.select');
            $res['key'] = I('get.key');
            $ary = [$res['key']];
            $res['start'] = I('get.start');
            $res['stop'] = I('get.stop');
            $ary[] = $res['start'];
            $ary[] = $res['stop'];

            switch ($res['select']){
                case '0':
                    $where = " WHERE a.drivername LIKE '%%%s%%' ";
                    break;
                case '1':
                    $where = " WHERE a.LicenseId LIKE '%%%s%%' ";
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
        cookie('driver_query',json_encode($res));

        $sql = "SELECT a.id,a.DriverId,a.drivername,a.LicenseId,a.DriverGender,a.idCard,a.addtime,b.DriverGender as fa_DriverGender,b.idCard as fa_idCard FROM report_driver_error a LEFT JOIN car_driverinfo b ON a.DriverId=b.id LEFT JOIN car_drivermore c ON a.DriverId=c.DriverId".$where;

        $list = M()->query($sql,$ary);
        $data = $this->driverAry($list);

        $this->pageInfo($data,count($data),12,'list','page',true);
        $this->display('driver_index');
    }

    //对驾驶员信息进行重组
    function driverAry($list){
        $data = [];
        foreach ($list as $key => &$val){
            $data[$key]['id'] = $val['id'];
            $data[$key]['driverid'] = $val['driverid'];
            $data[$key]['addtime'] = $val['addtime'];
            $data[$key]['licenseid'] = $val['licenseid'];
            $data[$key]['drivername'] = $val['drivername'];
            $data[$key]['times'] = 0;
            unset($val['id'],$val['driverid'],$val['addtime'],$val['licenseid'],$val['drivername']);
            foreach ($val as $k => &$v){
                if (substr($k,0,3) != 'fa_' && $v != false){
                    $data[$key]['times']++;
                    switch ($k){
                        case 'drivergender':
                            $data[$key]['content'][$k]['name'] = '性别';
                            if ($val['fa_'.$k] == 0){
                                $val['fa_'.$k] = '男';
                            }else{
                                $val['fa_'.$k] = '女';
                            }
                            break;
                        case 'idcard':
                            $data[$key]['content'][$k]['name'] = '身份证号';
                            break;
                    }
                    $data[$key]['content'][$k]['true'] = $v;
                    $data[$key]['content'][$k]['false'] = $val['fa_'.$k];
                }
            }
        }

        return $data;
    }


    /**
     * pageInfo 分页显示方法，
     * @param string $arr   进行分页的数组 注意进行分页的数组必须是多维数组
     * @param string $count 获取分页数据总条数
     * @param string $pageNum 每页显示几条数据
     * @param string $listName 模板name名，传递数据到模板关联
     * @param string $showPage 模板分页绑定的输出参数，显示分页导航
     * @param bool $isPage 分页开关，True显示分页，False不显示分页，只显数据
     * @return void
     */

    function pageInfo($arr, $count, $pageNum,$listName, $showPage, $isPage){
        if ($isPage == true) {
            $page = new Page($count, $pageNum);
            $page->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条记录  第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
            $page->setConfig('prev', '上一页');
            $page->setConfig('next', '下一页');
            $page->setConfig('last', '尾页');
            $page->setConfig('first', '首页');
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $show = $page->show();
            $times = 0;
            foreach ($arr as $key => $value){
                if ($key >= $page->firstRow && $page->listRows > $times){
                    $list[] = $value;
                    ++$times;
                }
            }
            $this->assign($listName, $list);
            $this->assign($showPage, $show);
        } else {
            $list = $arr;
            $this->assign($listName, $list);
        }

    }

    //excel导出,数组组装
    public function excelCar(){
        if (isset($_POST['file_name']) && isset($_POST['ex_type']) && $_POST['file_name'] != ''){
            $expTitle = I('post.file_name');
            $ex_type = I('post.ex_type');
            $excel_query = json_decode($_COOKIE['car_query']);

            if ($excel_query->select){      //判断是否有查询条件
                $res['select'] = $excel_query->select;
                $res['key'] = $excel_query->key;
                $ary = [$res['key']];

                $res['start'] = $excel_query->start;
                $res['stop'] = $excel_query->stop;
                $ary[] = $res['start'];
                $ary[] = $res['stop'];

                switch ($res['select']){
                    case '0':
                        $where = " WHERE a.CarId LIKE '%%%s%%' ";
                        break;
                    case '1':
                        $where = " WHERE a.VehicleNo LIKE '%%%s%%' ";
                        break;
                }
                $where .= " AND (a.addtime BETWEEN '%s' AND '%s') ";
            }else{
                $where = " WHERE (a.addtime BETWEEN '%s' AND '%s') ";
                $ary = [$excel_query->start,$excel_query->stop];
            }

            $sql = "SELECT a.id,a.CarId,a.VehicleNo,a.PlateColor,a.Seats,a.VehicleColor,a.Model,a.VehicleType,a.EngineId,a.VIN,a.EngineDisplace,a.addtime,b.VehicleNo as fa_VehicleNo,b.PlateColor as fa_PlateColor,b.Seats as fa_Seats,b.VehicleColor as fa_VehicleColor,b.Model as fa_Model,b.VehicleType as fa_VehicleType,c.EngineId as fa_EngineId,c.VIN as fa_VIN,c.EngineDisplace as fa_EngineDisplace FROM report_car_error a LEFT JOIN car_net b ON a.CarId=b.id LEFT JOIN car_info c ON a.CarId=c.CarId".$where;

            $list = M()->query($sql,$ary);

            $data = $this->carAry($list);

            if ($list){
                $expCellName = [];
                $expCellName[] = ['id','序号'];
                $expCellName[] = ['carid','车辆id'];
                $expCellName[] = ['vehicleno','车牌号'];
                $expCellName[] = ['times','异常信息个数'];
                $expCellName[] = ['error_info','异常信息详情'];
                $expCellName[] = ['addtime','记录时间'];

                foreach ($data as $key => &$val){
                    $val['times'] = $val['times'].'个';
                    foreach ($val['content'] as $k => $v){
                        $data[$key]['error_info'] .= $v['name'].'--'.$v['false'].'(有误) ';
                    }
                }

                $expTableData = $data;
                $this->exportExcel($expTitle,$expCellName,$expTableData,$ex_type);
            }else{
                exit('<script type="text/javascript">alert("文件内容不能为空!");history.back(-1);</script>');
            }
        }else{
            exit('<script type="text/javascript">alert("文件名或文件类型不能为空!");history.back(-1);</script>');
        }
    }

    //excel导出,数组组装
    public function excelDriver(){
        if (isset($_POST['file_name']) && isset($_POST['ex_type']) && $_POST['file_name'] != ''){
            $expTitle = I('post.file_name');
            $ex_type = I('post.ex_type');
            $excel_query = json_decode($_COOKIE['car_query']);

            if ($excel_query->select){      //判断是否有查询条件
                $res['select'] = $excel_query->select;
                $res['key'] = $excel_query->key;
                $ary = [$res['key']];

                $res['start'] = $excel_query->start;
                $res['stop'] = $excel_query->stop;
                $ary[] = $res['start'];
                $ary[] = $res['stop'];

                switch ($res['select']){
                    case '0':
                        $where = " WHERE a.drivername LIKE '%%%s%%' ";
                        break;
                    case '1':
                        $where = " WHERE a.LicenseId LIKE '%%%s%%' ";
                        break;
                }
                $where .= " AND (a.addtime BETWEEN '%s' AND '%s') ";
            }else{
                $where = " WHERE (a.addtime BETWEEN '%s' AND '%s') ";
                $ary = [$excel_query->start,$excel_query->stop];
            }

            $sql = "SELECT a.id,a.DriverId,a.drivername,a.LicenseId,a.DriverGender,a.idCard,a.addtime,b.DriverGender as fa_DriverGender,b.idCard as fa_idCard FROM report_driver_error a LEFT JOIN car_driverinfo b ON a.DriverId=b.id LEFT JOIN car_drivermore c ON a.DriverId=c.DriverId".$where;

            $list = M()->query($sql,$ary);

            $data = $this->driverAry($list);

            if ($list){
                $expCellName = [];
                $expCellName[] = ['id','序号'];
                $expCellName[] = ['driverid','驾驶员id'];
                $expCellName[] = ['drivername','驾驶员姓名'];
                $expCellName[] = ['licenseid','驾驶证号'];
                $expCellName[] = ['times','异常信息个数'];
                $expCellName[] = ['error_info','异常信息详情'];
                $expCellName[] = ['addtime','记录时间'];

                foreach ($data as $key => &$val){
                    $val['times'] = $val['times'].'个';
                    foreach ($val['content'] as $k => $v){
                        $data[$key]['error_info'] .= $v['name'].'--'.$v['false'].'(有误) ';
                    }
                }

                $expTableData = $data;
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