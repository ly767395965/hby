<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
use Think\Upload;

class NetCarInfoController extends BaseController {
    public function test()
    {
//        echo deleteFile('/Uploads/NetCar/2017-09-26/59ca15b374fa9.png');
        photoSub('/Uploads/NetCar/2017-09-29/59cddfdd43e5a.png','update','/Uploads/NetCar/2017-10-23/59ed83cf17206.png');
    }

    public function index(){
        $ary = [];
        $sql = "SELECT a.id,a.VehicleNo,a.VehicleColor,a.FareType,a.BizStatus,a.CarState,a.PositionTime,a.VehicleType,b.brand,c.carmodelname FROM car_net a LEFT JOIN car_barand b ON a.BrandId=b.id LEFT JOIN car_carmodel c ON a.ModelId=c.id LEFT JOIN car_info d ON a.id=d.CarId";
        $countSql = "SELECT COUNT(a.id) FROM car_net a LEFT JOIN car_barand b ON a.BrandId=b.id LEFT JOIN car_carmodel c ON a.ModelId=c.id LEFT JOIN car_info d ON a.id=d.CarId";
        if (isset($_GET['select'])){
            $res['select'] = $select = I('get.select');
            $res['key'] = $key = I('get.key');
            $ary = [$key];
            switch ($select){
                case '0':
                    $where = " a.VehicleNo LIKE '%%%s%%'";
                    break;
                case '1':
                    $where = " b.brand LIKE '%%%s%%'";
                    break;
                case '2':
                    $where = " c.carmodelname LIKE '%%%s%%'";
                    break;
                case '3':
                    $state = I('get.auditing_state');
                    $res['auditing_state'] = $where = " a.carstate = {$state}";
                    break;
                case '4':
                    $where = " d.InsuranceId LIKE '%%%s%%'";
                    break;
                case '5':
                    $where = " d.GPSIMEI LIKE '%%%s%%'";
                    break;
            }
            $this->assign('res',$res);
        }else{
            $where = ' a.carstate > 0';
        }
        $sql .= ' WHERE a.Flag != 3 AND '.$where;
        $countSql .= ' WHERE a.Flag != 3 AND '.$where;

        $this->pageDisplay($sql, $countSql, 12, $ary, 'count(a.id)', 'list', 'page', true); //分页显示
        $this->display();

    }

    //添加网约车
    public function addCar(){
        if (empty($_POST)){
            $sqlbarand = M('car_barand');
            $brand = $sqlbarand->where(array('isdel=0'))->select();
            $this->assign('brand',$brand);
            $this->display('addCar');
        }else{
            $rules = array(
                array('VehicleNo', 'require', '<script>alert("车牌号不能为空！");history.back(-1);</script>', 0),
                array('PlateColor', 'require', '<script>alert("车牌颜色不能为空！");history.back(-1);</script>', 0),
                array('Seats','number','<script>alert("核定载客位只能为数字");history.back(-1);</script>',0),
                array('BrandId','require','<script>alert("车辆品牌不能为空");history.back(-1);</script>',0),
                array('ModelId','require','<script>alert("车型不能为空");history.back(-1);</script>',0),
                array('CommercialType','require','<script>alert("服务类型不能为空");history.back(-1);</script>',0),
                array('FareType','require','<script>alert("运价类型不能为空");history.back(-1);</script>',0),
                array('Model','require','<script>alert("车辆型号不能为空");history.back(-1);</script>',0),
                array('VehicleType','require','<script>alert("车辆类型不能为空");history.back(-1);</script>',0),
                array('Mileage','require','<script>alert("行驶里程不能为空");history.back(-1);</script>',0),
            );
            $car_net = M('car_net');
            if (!$car_net->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($car_net->getError());
            } else {
                $rules = array(
                    array('OwnerName', 'require', '<script>alert("车辆所有人不能为空！");history.back(-1);</script>', 0),
                    array('EngineId', 'require', '<script>alert("发动机号不能为空！");history.back(-1);</script>', 0),
                    array('VIN','require','<script>alert("车辆VIN码不能为空");history.back(-1);</script>',0),
                    array('CertifyDateA','require','<script>alert("车辆注册日期不能为空");history.back(-1);</script>',0),
                    array('FuelType','require','<script>alert("燃料类型不能为空");history.back(-1);</script>',0),
                    array('EngineDisplace','number','<script>alert("发动机排量只能为数字");history.back(-1);</script>',0),
                    array('TransAgency','require','<script>alert("车辆运输证发证机构不能为空");history.back(-1);</script>',0),
                    array('TransArea','require','<script>alert("车辆经营区域不能为空");history.back(-1);</script>',0),
                    array('TransDateStart','require','<script>alert("车辆运输证有效期起不能为空");history.back(-1);</script>',0),
                    array('TransDateStop','require','<script>alert("车辆运输证有效期止不能为空");history.back(-1);</script>',0),
                    array('CertifyDateB','require','<script>alert("车辆初次登记日期不能为空");history.back(-1);</script>',0),
                    array('FeePrintId','require','<script>alert("发票打印设备序列号不能为空");history.back(-1);</script>',0),
                    array('GPSBrand','require','<script>alert("卫星定位装置品牌不能为空");history.back(-1);</script>',0),
                    array('GPSModel','require','<script>alert("卫星定位装置型号不能为空");history.back(-1);</script>',0),
                    array('GPSInstallDate','require','<script>alert("卫星定位设备安装日期不能为空");history.back(-1);</script>',0),
                    array('RegisterDate','require','<script>alert("报备日期不能为空");history.back(-1);</script>',0),
                    array('Insurance','require','<script>alert("保险公司名称不能为空");history.back(-1);</script>',0),
                    array('InsuranceId','require','<script>alert("保险号不能为空");history.back(-1);</script>',0),
                    array('InsuranceType','require','<script>alert("保险类型不能为空");history.back(-1);</script>',0),
                    array('InsurancePeice','require','<script>alert("保险金额不能为空");history.back(-1);</script>',0),
                    array('InsuranceStart','require','<script>alert("保险生效时间不能为空");history.back(-1);</script>',0),
                    array('InsuranceStop','require','<script>alert("保险到期时间不能为空");history.back(-1);</script>',0),
                );
                $car_info = M('car_info');
                if (!$car_info->validate($rules)->create()) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    exit($car_info->getError());
                } else {
                    if ($_FILES['PhotoId']['name'] != ''){
                        $info_ary['savePath'] = '/Uploads/NetCar/';
                        $info_ary['class'] = 'vehicleInfo';
                        $photo_res = photoUpdate($_FILES,$info_ary);
                        if ($photo_res['err'] == 2){
                            exit('<script type="text/javascript">alert("图片上传出错,请重试!");history.back(-1);</script>');
                        }
                        $data['PhotoId'] = $photo_res['PhotoId'];//车辆照片
                    }

                    $data['VehicleNo'] = I('post.VehicleNo');//车牌号
                    $data['PlateColor'] = I('post.PlateColor');//车牌颜色
                    $data['Seats'] = I('post.Seats');//核定载客人数
                    $data['VehicleColor'] = I('post.VehicleColor');//车身颜色
                    $data['BrandId'] = I('post.BrandId');//车辆品牌id
                    $data['ModelId'] = I('post.ModelId');//车型id
                    $data['CommercialType'] = I('post.CommercialType');//服务类型
                    $data['FareType'] = I('post.FareType');//运价类型编码(运价号)
                    $data['BizStatus'] = I('post.BizStatus');//运营状态
                    $data['CarState'] = I('post.CarState');//车辆状态
                    $data['Mileage'] = I('post.Mileage');//行驶里程
                    $data['Model'] = I('post.Model');//车辆型号
                    $data['VehicleType'] = I('post.VehicleType');//车辆类型
                    $data['State'] = 0;//是否有效
                    $data['Flag'] = 1;//操作标识
                    $add_id = $car_net->add($data);
                    if ($add_id){
                        $company = M()->query('SELECT CompanyId FROM company WHERE State=0 AND Flag != 3');
						$data_info['CarId'] = $add_id;
                        $data_info['Address'] = $company[0]['companyid'];//车辆注册地行政区编号,和公司地址编号相同
                        $data_info['OwnerName'] = I('post.OwnerName');//车辆所有人
                        $data_info['EngineId'] = I('post.EngineId');//发动机号
                        $data_info['VIN'] = I('post.VIN');//车辆VIN码
                        $data_info['CertifyDateA'] = I('post.CertifyDateA');//车辆注册日期
                        $data_info['FuelType'] = I('post.FuelType');//车辆燃料类型
                        $data_info['EngineDisplace'] = I('post.EngineDisplace');//发动机排量
                        $data_info['Certificate'] = I('post.Certificate');//运输证字号
                        $data_info['TransAgency'] = I('post.TransAgency');//车辆运输证发证机构
                        $data_info['TransArea'] = I('post.TransArea');//车辆经营区域
                        $data_info['TransDateStart'] = I('post.TransDateStart');//车辆运输证有效期起
                        $data_info['TransDateStop'] = I('post.TransDateStop');//车辆运输证有效期止
                        $data_info['CertifyDateB'] = I('post.CertifyDateB');//车辆初次登记日期
                        $data_info['FixState'] = I('post.FixState');//车辆检修状态
                        $data_info['NextFixDate'] = I('post.NextFixDate');//车辆下次年检时间
                        $data_info['CheckState'] = I('post.CheckState');//车辆年度审验状态
                        $data_info['FeePrintId'] = I('post.FeePrintId');//发票打印设备序列号
                        $data_info['GPSBrand'] = I('post.GPSBrand');//卫星定位装置品牌
                        $data_info['GPSModel'] = I('post.GPSModel');//卫星定位装置型号
                        $data_info['GPSIMEI'] = I('post.GPSIMEI');//卫星定位装置IMEI
                        $data_info['GPSInstallDate'] = I('post.GPSInstallDate');//卫星定位设备安装日期
                        $data_info['RegisterDate'] = I('post.RegisterDate');//报备日期
                        $data_info['Insurance'] = I('post.Insurance');//保险公司
                        $data_info['InsuranceId'] = I('post.InsuranceId');//保险号
                        $data_info['InsuranceType'] = I('post.InsuranceType');//保险类型
                        $data_info['InsurancePeice'] = I('post.InsurancePeice');//保险金额
                        $data_info['InsuranceStart'] = I('post.InsuranceStart');//保险生效时间
                        $data_info['InsuranceStop'] = I('post.InsuranceStop');//保险到期时间
                        $add = $car_info->add($data_info);
                        if ($add){
                            $this->infoSub($add_id);
                            $this->infoSubMile($add_id);
                            $this->infoSubInsurce($add_id);
                            $this->infoSubStat($add_id);
                            exit('<script type="text/javascript">alert("车辆信息录入成功!");location.href="'.U('NetCarInfo/Index').'";</script>');
                        }else{
                            exit('<script type="text/javascript">alert("车辆信息录入失败!");history.back(-1);</script>');
                        }
                    }else{
                        exit('<script type="text/javascript">alert("车辆信息录入失败!");history.back(-1);</script>');
                    }

                }
            }
        }
    }

    //修改车辆信息
    public function editCar(){
        if (!empty($_POST)){
            $rules = array(
                array('VehicleNo', 'require', '<script>alert("车牌号不能为空！");history.back(-1);</script>', 0),
                array('PlateColor', 'require', '<script>alert("车牌颜色不能为空！");history.back(-1);</script>', 0),
                array('Seats','number','<script>alert("核定载客位只能为数字");history.back(-1);</script>',0),
                array('BrandId','require','<script>alert("车辆品牌不能为空");history.back(-1);</script>',0),
                array('ModelId','require','<script>alert("车型不能为空");history.back(-1);</script>',0),
                array('CommercialType','require','<script>alert("服务类型不能为空");history.back(-1);</script>',0),
                array('FareType','require','<script>alert("运价类型不能为空");history.back(-1);</script>',0),
                array('Model','require','<script>alert("车辆型号不能为空");history.back(-1);</script>',0),
                array('VehicleType','require','<script>alert("车辆类型不能为空");history.back(-1);</script>',0),
                array('Mileage','require','<script>alert("行驶里程不能为空");history.back(-1);</script>',0),
            );
            $car_net = M('car_net');
            if (!$car_net->validate($rules)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($car_net->getError());
            } else {
                $rules = array(
                    array('OwnerName', 'require', '<script>alert("车辆所有人不能为空！");history.back(-1);</script>', 0),
                    array('EngineId', 'require', '<script>alert("发动机号不能为空！");history.back(-1);</script>', 0),
                    array('VIN','require','<script>alert("车辆VIN码不能为空");history.back(-1);</script>',0),
                    array('CertifyDateA','require','<script>alert("车辆注册日期不能为空");history.back(-1);</script>',0),
                    array('FuelType','require','<script>alert("燃料类型不能为空");history.back(-1);</script>',0),
                    array('EngineDisplace','number','<script>alert("发动机排量只能为数字");history.back(-1);</script>',0),
                    array('TransAgency','require','<script>alert("车辆运输证发证机构不能为空");history.back(-1);</script>',0),
                    array('TransArea','require','<script>alert("车辆经营区域不能为空");history.back(-1);</script>',0),
                    array('TransDateStart','require','<script>alert("车辆运输证有效期起不能为空");history.back(-1);</script>',0),
                    array('TransDateStop','require','<script>alert("车辆运输证有效期止不能为空");history.back(-1);</script>',0),
                    array('CertifyDateB','require','<script>alert("车辆初次登记日期不能为空");history.back(-1);</script>',0),
                    array('FeePrintId','require','<script>alert("发票打印设备序列号不能为空");history.back(-1);</script>',0),
                    array('GPSBrand','require','<script>alert("卫星定位装置品牌不能为空");history.back(-1);</script>',0),
                    array('GPSModel','require','<script>alert("卫星定位装置型号不能为空");history.back(-1);</script>',0),
                    array('GPSInstallDate','require','<script>alert("卫星定位设备安装日期不能为空");history.back(-1);</script>',0),
                    array('RegisterDate','require','<script>alert("报备日期不能为空");history.back(-1);</script>',0),
                    array('Insurance','require','<script>alert("保险公司名称不能为空");history.back(-1);</script>',0),
                    array('InsuranceId','require','<script>alert("保险号不能为空");history.back(-1);</script>',0),
                    array('InsuranceType','require','<script>alert("保险类型不能为空");history.back(-1);</script>',0),
                    array('InsurancePeice','require','<script>alert("保险金额不能为空");history.back(-1);</script>',0),
                    array('InsuranceStart','require','<script>alert("保险生效时间不能为空");history.back(-1);</script>',0),
                    array('InsuranceStop','require','<script>alert("保险到期时间不能为空");history.back(-1);</script>',0),
                );
                $car_info = M('car_info');
                if (!$car_info->validate($rules)->create()) {
                    // 如果创建失败 表示验证没有通过 输出错误提示信息
                    exit($car_info->getError());
                } else {
                    if ($_FILES['PhotoId']['name'] != ''){
                        $info_ary['savePath'] = '/Uploads/NetCar/';
                        $info_ary['class'] = 'vehicleInfo';
                        //查看是否有旧图片,如果有则要找出来,上传新图后销毁旧图
                        $photo_sql = "SELECT PhotoId FROM car_net WHERE id=%d";
                        $old_path = M()->query($photo_sql,[I('post.id')]);
                        if ($_FILES['PhotoId']['name'] != '' && $old_path[0]['photoid']){
                            $old_url = $old_path[0]['photoid'];
                        }else{
                            $old_url = '';
                        }

                        $photo_res = photoUpdate($_FILES,$info_ary,$old_url);
                        if ($photo_res['err'] == 2){
                            exit('<script type="text/javascript">alert("图片上传出错,请重试!");history.back(-1);</script>');
                        }
                        $data['PhotoId'] = $photo_res['PhotoId'];//车辆照片
                    }

                    $where['id'] = I('post.id');
                    $data['VehicleNo'] = I('post.VehicleNo');//车牌号
                    $data['PlateColor'] = I('post.PlateColor');//车牌颜色
                    $data['Seats'] = I('post.Seats');//核定载客人数
                    $data['VehicleColor'] = I('post.VehicleColor');//车身颜色
                    $data['BrandId'] = I('post.BrandId');//车辆品牌id
                    $data['ModelId'] = I('post.ModelId');//车型id
                    $data['CommercialType'] = I('post.CommercialType');//服务类型
                    $data['FareType'] = I('post.FareType');//运价类型编码(运价号)
                    $data['BizStatus'] = I('post.BizStatus');//运营状态
                    $data['CarState'] = I('post.CarState');//车辆状态
                    $data['Mileage'] = I('post.Mileage');//行驶里程
                    $data['Model'] = I('post.Model');//车辆型号
                    $data['VehicleType'] = I('post.VehicleType');//车辆类型
                    $data['State'] = 0;//是否有效
                    $data['Flag'] = 2;//操作标识
                    $save = $car_net->where($where)->save($data);

                    $data_info['OwnerName'] = I('post.OwnerName');//车辆所有人
                    $data_info['EngineId'] = I('post.EngineId');//发动机号
                    $data_info['VIN'] = I('post.VIN');//车辆VIN码
                    $data_info['CertifyDateA'] = I('post.CertifyDateA');//车辆注册日期
                    $data_info['FuelType'] = I('post.FuelType');//车辆燃料类型
                    $data_info['EngineDisplace'] = I('post.EngineDisplace');//发动机排量
                    $data_info['Certificate'] = I('post.Certificate');//运输证字号
                    $data_info['TransAgency'] = I('post.TransAgency');//车辆运输证发证机构
                    $data_info['TransArea'] = I('post.TransArea');//车辆经营区域
                    $data_info['TransDateStart'] = I('post.TransDateStart');//车辆运输证有效期起
                    $data_info['TransDateStop'] = I('post.TransDateStop');//车辆运输证有效期止
                    $data_info['CertifyDateB'] = I('post.CertifyDateB');//车辆初次登记日期
                    $data_info['FixState'] = I('post.FixState');//车辆检修状态
                    $data_info['NextFixDate'] = I('post.NextFixDate');//车辆下次年检时间
                    $data_info['CheckState'] = I('post.CheckState');//车辆年度审验状态
                    $data_info['FeePrintId'] = I('post.FeePrintId');//发票打印设备序列号
                    $data_info['GPSBrand'] = I('post.GPSBrand');//卫星定位装置品牌
                    $data_info['GPSModel'] = I('post.GPSModel');//卫星定位装置型号
                    $data_info['GPSIMEI'] = I('post.GPSIMEI');//卫星定位装置IMEI
                    $data_info['GPSInstallDate'] = I('post.GPSInstallDate');//卫星定位设备安装日期
                    $data_info['RegisterDate'] = I('post.RegisterDate');//报备日期
                    $data_info['Insurance'] = I('post.Insurance');//保险公司
                    $data_info['InsuranceId'] = I('post.InsuranceId');//保险号
                    $data_info['InsuranceType'] = I('post.InsuranceType');//保险类型
                    $data_info['InsurancePeice'] = I('post.InsurancePeice');//保险金额
                    $data_info['InsuranceStart'] = I('post.InsuranceStart');//保险生效时间
                    $data_info['InsuranceStop'] = I('post.InsuranceStop');//保险到期时间
                    $save_car = $car_info->where(array('CarId'=>$where['id']))->save($data_info);
                    if ($save || $save_car){
                        $this->infoSub($where['id']);
                        $this->infoSubMile($where['id']);
                        $this->infoSubInsurce($where['id']);
                        exit('<script type="text/javascript">alert("车辆信息修改成功!");location.href="'.U('NetCarInfo/Index').'";</script>');
                    }else{
                        exit('<script type="text/javascript">alert("车辆信息修改失败或未发生修改!");history.back(-1);</script>');
                    }
                }
            }
        }else{
            $id = I('get.id');
            $sql = "SELECT a.id,a.VehicleNo,a.PlateColor,a.Seats,a.VehicleColor,a.BrandId,a.ModelId,a.PhotoId,a.CommercialType,a.FareType,a.BizStatus,a.CarState,a.Mileage,a.Model,a.VehicleType,b.OwnerName,b.EngineId,b.VIN,b.CertifyDateA,b.FuelType,b.EngineDisplace,b.Certificate,b.TransAgency,b.TransArea,b.TransDateStart,b.TransDateStop,b.CertifyDateB,b.FixState,b.NextFixDate,b.CheckState,b.FeePrintId,b.GPSBrand,b.GPSModel,b.GPSIMEI,b.GPSInstallDate,b.RegisterDate,b.Insurance,b.InsuranceId,b.InsuranceType,b.InsurancePeice,b.InsuranceStart,b.InsuranceStop,c.FareTypeNote,d.brand as brandname,e.carmodelname as modelname FROM car_net a LEFT JOIN car_info b ON a.id=b.CarId LEFT JOIN transport_price c ON a.FareType=c.id LEFT JOIN car_barand d ON a.BrandId=d.id LEFT JOIN car_carmodel e ON a.ModelId=e.id WHERE a.id = %d";
            $list = M()->query($sql,[$id]);
            if ($list){
                $this->assign('list',$list[0]);
                $this->display('editCar');
            }else{
                exit('<script type="text/javascript">alert("未获取到车辆信息,请重试!");history.back(-1);</script>');
            }

        }
    }

    //对车辆进行逻辑删除
    public function delCar(){
        $id = I('get.id');
        $data['Flag'] = 3;
        $del = M('car_net')->where(array('id'=>$id))->save($data);
        if ($del){
            $this->infoSub($id);
			$this->infoSubStat($id);
            exit('<script type="text/javascript">alert("成功删除车辆!");location.href="'.U('NetCarInfo/Index').'";</script>');
        }else{
            exit('<script type="text/javascript">alert("删除车辆失败");history.back(-1);</script>');
        }
    }

    //改变车辆审核状态
    public function auditing(){
        $id = I('get.id');
        $state = I('get.state');
        if ($state == 2){
            $data['CarState'] = 1;
        }else{
            $data['CarState'] = 2;
        }
        $data['Flag'] = 2;
        $save = M('car_net')->where(array('id'=>$id))->save($data);
        if ($save){
            $this->infoSub($id);
            exit('<script type="text/javascript">alert("操作成功!");location.href="'.U('NetCarInfo/Index').'";</script>');
        }else{
            exit('<script type="text/javascript">alert("操作失败");history.back(-1);</script>');
        }
    }

    //对应品牌的车型
    public function ajax(){
        $where['isdel'] = 0;
        $where['barandid'] = I('post.id');//获取品牌id
        //查询车型
        $sqlcarmodel = M('car_carmodel');
        //根据品牌查询相对应的车型
        $model = $sqlcarmodel->where($where)->field('id,barandid,carmodelname')->select();
        $this->ajaxReturn($model);
    }

    //运价信息查询
    public function fareAjax(){
        $sql = "SELECT FareTypeNote,id,StartFare,StartMile,UnitPricePerMile,UnitPricePerMinute FROM transport_price WHERE Flag != 3";
        $ary = [];
        if (isset($_GET['key'])){
            $key = I('get.key');
            $sql .= " AND FareTypeNote LIKE '%%%s%%'";
            $ary = [$key];
        }
        $list = M()->query($sql,$ary);
        if (!$list){
            $reg['error'] = 1;
        }else{
            $reg['error'] = 0;
            $reg['list'] = $list;
        }
        echo json_encode($reg);
    }

    //车辆轨迹点查询
    public function traLocation(){
        echo microtime(true);

//        $this->display('test3');
    }

    //车辆定位页面
    public function location(){
        $this->display('location');
    }

    //车辆定位信息
    public function locationInfo(){
        $ary = [];
        if ($_GET['key_query'] != ''){
            $key_query = I('get.key_query');
            $ary[] = $key_query;
            $where = " AND a.VehicleNo LIKE '%%%s%%'";
        }

        $sql = "SELECT a.VehicleNo,a.BizStatus,a.PositionTime,a.Longitude,a.Latitude,b.drivername FROM car_net a LEFT JOIN car_driverinfo b ON a.id = b.CarId WHERE a.CarState = 1 AND a.Flag != 3".$where;
        $list = M()->query($sql,$ary);
        if ($list){
            foreach ($list as $key => &$val){
                switch ($val['bizstatus']){
                    case 0:
                        $val['bizstatus'] = '空闲';
                        break;
                    case 1:
                        $val['bizstatus'] = '载客';
                        break;
                    case 2:
                        $val['bizstatus'] = '接单';
                        break;
                    case 3:
                        $val['bizstatus'] = '空驶';
                        break;
                    case 4:
                        $val['bizstatus'] = '停运';
                        break;
                }
                $ary = $this->gaoDeToBaidu($val['longitude'],$val['latitude']);
                $val['longitude'] = $ary[0];
                $val['latitude'] = $ary[1];
            }
            $res['list'] = $list;
            $res['error'] = 0;
        }else{
            $res['error'] = 1;
        }
        echo json_encode($res);
    }


    public function gaoDeToBaidu($gd_lon,$gd_lat){
        $PI = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $gd_lon; $y = $gd_lat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $PI);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $PI);
        $lng = $z * cos($theta) + 0.0065;//lng
        $lat = $z * sin($theta) + 0.006;//lat
        return [$lng,$lat];
    }

    //车辆轨迹页面
    public function trajectory(){
        if (isset($_GET['id'])){
            $id = I('get.id');
            $sql = "SELECT VehicleNo FROM car_net WHERE id=%d";
            $id = M()->query($sql,[$id]);
            $this->assign('vehicleno',$id[0]['vehicleno']);
        }
        $this->display('trajectory');
    }

    //车辆轨迹点获取
    public function trajectoryInfo(){
        if (!is_numeric($_POST['select'])){
            $select = '1';
            $query_key = '';
            $start = date('Y-m-d 00:00:00 ',time() - 864000);
            $stop = date('Y-m-d 00:00:00 ',time());
            $start = '2017-07-03 00:00:00';
            $stop = '2017-09-04 09:12:02';
        }else{
            $select = I('post.select');
            $query_key = I('post.key');
            $start = I('post.start');
            $stop = I('post.stop');
        }

        $where = " WHERE (a.GpsTime BETWEEN '%s' AND '%s')";
        $ary = [$start,$stop];

        switch ($select){
            case '0';
                if ($query_key != ''){
                    $where .= " AND b.VehicleNo='%s'";
                    $ary[] = $query_key;
                }
                break;
            case '1';
                if ($query_key != ''){
                    $where .= " AND a.OrderCode='%s'";
                    $ary[] = $query_key;
                }
                break;
        }



        if ($select == 0){
            $sql = "SELECT a.Longitude,a.Latitude,a.CarState,a.GpsTime,a.Speed,a.Distance,a.CarId,a.OrderCode FROM car_trajectory a LEFT JOIN car_net b ON a.CarId=b.id".$where;
            $group_sql = "SELECT a.CarId FROM car_trajectory a LEFT JOIN car_net b ON a.CarId=b.id".$where." GROUP BY a.CarId";
        }else{
            $sql = "SELECT a.Longitude,a.Latitude,a.CarState,a.GpsTime,a.Speed,a.Distance,a.CarId,a.OrderCode FROM car_trajectory a LEFT JOIN car_net b ON a.CarId=b.id".$where." AND (a.CarState = 5 OR a.CarState = 1 OR a.CarState = 6)";
            $group_sql = "SELECT a.OrderCode,a.CarId as order_sum,b.VehicleNo FROM car_trajectory a LEFT JOIN car_net b ON a.CarId=b.id".$where." GROUP BY a.OrderCode";
        }
        $lists = M()->query($sql,$ary);//定位点
        $sum = M()->query($group_sql,$ary);//分组,车辆辆数,订单数

        if ($select == 0){
            $i = 0;
            foreach ($sum as $key => $val){ //根据车辆台数或订单数,初步分组
                $business_sql = "SELECT a.CarId,a.LoginTime,a.businessclass,b.VehicleNo FROM driver_business a LEFT JOIN car_net b ON a.CarId=b.id WHERE (a.LoginTime BETWEEN '%s' AND '%s') AND a.CarId=%d";
                $business = M()->query($business_sql,[$start,$stop,$val['carid']]);
                $bus_ary['carid'] = $val['carid'];

                foreach ($business as $k_one => $v_one){
                    if ($v_one['businessclass'] == 0){
                        $bus_ary['start'] = $v_one['logintime'];
                        if (count($business)-1 == $k_one){

                            $ary_car = $this->car_group($lists,$bus_ary,$start,$stop);
                            if ($ary_car['path']){
                                $data[$i]['carid'] = $val['carid'];
                                $data[$i]['name'] = $v_one['vehicleno'];
                                $data[$i]['all_distance'] = $ary_car['all_distance'];
                                $data[$i]['path'] = $ary_car['path'];
                                $data[$i]['time'] = $ary_car['time'];
                                $lists = $ary_car['list'];
                                $bus_ary = ['carid' => $val['carid']];
                                $i++;
                            }

                        }
                    }else if ($v_one['businessclass'] == 1){

                        $ary_car = $this->car_group($lists,$bus_ary,$start,$stop);
                        if ($ary_car['path']){
                            $bus_ary['stop'] = $v_one['logintime'];
                            $data[$i]['name'] = $v_one['vehicleno'];
                            $data[$i]['carid'] = $val['carid'];
                            $data[$i]['all_distance'] = $ary_car['all_distance'];
                            $data[$i]['path'] = $ary_car['path'];
                            $data[$i]['time'] = $ary_car['time'];
                            $lists = $ary_car['list'];
                            $bus_ary = ['carid' => $val['carid']];
                            $i++;
                        }

                    }
                }


            }
        }else{
            $tra = 0;//用于记录轨迹条数
            foreach ($sum as $key => $val){ //根据车辆台数或订单数,初步分组
                if ($val['ordercode'] == 0){
                    continue;
                }

                $times = 0;//用于判定是否为第一个符合条件的定位点
                $i = 0;//记录定位点个数

                foreach ($lists as $k => $v){
                    if ($v['ordercode'] == $val['ordercode']){
                        $data[$tra]['path'][$i][] = $v['longitude'];
                        $data[$tra]['path'][$i][] = $v['latitude'];
                        $data[$tra]['time'][$i] = $v['gpstime'];
                        if ($times == 1){
                            $data[$tra]['all_distance'] += $v['distance'];
                        }else{
                            $times = 1;
                        }
                        unset($lists[$k]);
                        $i++;
                    }
                }
                if (count($data[$tra]['path']) < 2){
                    array_splice($data,$tra,1);
                }else{
                    $data[$tra]['name'] = $val['vehicleno'];
                    ++$tra;
                }

            }

        }


        if ($data){
//            echo "<pre>";
//            print_r($data);
            $res['error'] = 0;
            $res['data'] = $data;
            if ($query_key != ''){
                $res['all_car'] = 1;
            }

        }else{
            $res['error'] = 1;
        }
        echo json_encode($res);
    }

    public function car_group($list,$ary,$start,$stop){
        $times = 0;
        $all_distance = 0;
        if ($ary['start']){
            if ($ary['stop']){
                foreach ($list as $key=>$val){
                    if ($val['gpstime'] >= $ary['start'] && $val['gpstime'] <= $ary['stop'] && $val['carid'] == $ary['carid']){
                        $data[$times][] = $val['longitude'];
                        $data[$times][] = $val['latitude'];
                        $time[$times] = $val['gpstime'];
                        if ($times >0){
                            $all_distance += $val['distance'];
                        }
                        $times++;
                        unset($list[$key]);
                    }

                }
            }else{
                foreach ($list as $key=>$val){
                    if ($val['gpstime'] >= $ary['start'] && $val['gpstime'] <= $stop && $val['carid'] == $ary['carid']){
                        $data[$times][] = $val['longitude'];
                        $data[$times][] = $val['latitude'];
                        $time[$times] = $val['gpstime'];
                        if ($times >0){
                            $all_distance += $val['distance'];
                        }
                        $times++;
                        unset($list[$key]);
                    }

                }
            }
        }else{
            if ($ary['stop']){
                foreach ($list as $key=>$val){
                    if ($val['gpstime'] >= $start && $val['gpstime'] <= $ary['stop'] && $val['carid'] == $ary['carid']){
                        $data[$times][] = $val['longitude'];
                        $data[$times][] = $val['latitude'];
                        $time[$times] = $val['gpstime'];
                        if ($times >0){
                            $all_distance += $val['distance'];
                        }
                        $times++;
                        unset($list[$key]);
                    }
                }
            }
        }
        $res['path'] = $data;
        $res['time'] = $time;
        $res['all_distance'] = $all_distance;
        $res['list'] = $list;
        return $res;
    }

    //车辆基本信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoVehicle',$id);
    }

    //车辆里程信息报送
    function infoSubMile($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoVehicTotalMile',$id);
    }

    //车辆保险信息报送
    function infoSubInsurce($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoVehicleInsurance',$id);
    }
	
	 //公司运营规模信息报送
    function infoSubStat($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoCompanyStat',$id);
    }

}