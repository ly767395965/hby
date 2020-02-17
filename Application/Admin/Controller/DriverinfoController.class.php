<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;
use Think\Think;
use Think\Verify;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/13
 * Time: 9:52
 * 司机管理控制器
 */
class DriverinfoController extends BaseController
{
    protected static $table = 'car_driverinfo';

    static function redis(){
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        return $redis;
    }

    //司机信息查询方法 Index()
    public function index() {
        $key = I('get.key');//接受模糊查询的条件
        $ary = [];//将条件传给数组
        $startDate = $_GET['start'];//开始时间
        $stopDate = $_GET['stop'];//结束时间
        $selectid = I('get.select');
        $sql = "SELECT a.id,a.drivername,a.phone,a.AppVersion,a.cost,a.state,a.idcard,b.CertificateNo,b.NetworkCarProofOn,b.NetworkCarProofOff,b.DriverType,a.Address FROM  car_driverinfo a LEFT JOIN car_drivermore b ON a.id=b.DriverId WHERE (a.isdel=0) ";
        $countSql = "SELECT COUNT(a.ID) FROM  car_driverinfo a LEFT JOIN car_drivermore b ON a.id=b.DriverId WHERE (a.isdel=0)  ";
        switch ($selectid){
            case 1:
                $sql = $sql . "AND (a.drivername LIKE '%%%s%%') ";
                $countSql = $countSql . "AND (a.drivername LIKE '%%%s%%')";
                break;
            case 2:
                $sql = $sql . "AND (a.phone LIKE '%%%s%%') ";
                $countSql = $countSql . "AND (a.phone LIKE '%%%s%%')";
                break;
            case 3:
                $sql = $sql . "AND (a.Address LIKE '%%%s%%') ";
                $countSql = $countSql . "AND (a.Address LIKE '%%%s%%')";
                break;
            case 4:
                $sql = $sql . "AND (b.DriverType LIKE '%%%s%%') ";
                $countSql = $countSql . "AND (b.DriverType LIKE '%%%s%%')";
                break;
            case 5:
                $sql = $sql . "AND (a.AppVersion LIKE '%%%s%%') ";
                $countSql = $countSql . "AND (a.AppVersion LIKE '%%%s%%')";
                break;
            case 6:
                $sql = $sql . "AND (b.CertificateNo LIKE '%%%s%%') ";
                $countSql = $countSql . "AND (b.CertificateNo LIKE '%%%s%%')";
                break;
            case 7:
                $sql = $sql . "AND (a.state LIKE '%%%s%%') ";
                $countSql = $countSql . "AND (a.state LIKE '%%%s%%')";
                break;
            case 8:
                $sql = $sql . "AND (b.NetworkCarProofOn >= '%s' AND b.NetworkCarProofOff <= '%s' ) ";
                $countSql = $countSql . "AND (b.NetworkCarProofOn >= '%s' AND b.NetworkCarProofOff <= '%s' )";
                break;
            default:
                $sql = $sql." ORDER BY a.ID DESC";
                $countSql = $countSql;
        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid != null) {
            $sql = $sql . "ORDER BY a.id DESC";

            if ($selectid == 2){
                if ($key == "空闲"){
                    $key=0;
                }elseif ($key == "代驾"){
                    $key=1;
                }
                $ary = [$key];
            } else if($selectid == 8){
                $ary = [$startDate,$stopDate];
            }

        } else {
            $ary = [];
        }
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true);
        $this->display();
    }

    //司机信息添加方法 addDriverinfo()
    public function addDriverinfo() {
        $driverinfo = M(self::$table);
        //判断输出模板
        if (empty($_POST)){
            $this->display();
        }
        //获取页面元素并且判断是否为post传值
        if ($_POST) {
            if ($_FILES['PhotoId']['name'] != '' || $_FILES['LicensePhotoId']['name'] != ''){
                $info_ary['savePath'] = '/Uploads/Driverimg/';
                $info_ary['class'] = 'driverInfo';

                $photo_res = photoUpdate($_FILES,$info_ary);
                if ($photo_res['err'] == 2){
                    exit('<script type="text/javascript">alert("图片上传出错,请重试!");history.back(-1);</script>');
                }
				
				if ($photo_res['PhotoId'] != ""){
					$driver['PhotoId'] = $photo_res['PhotoId'];//驾驶员照片
				}
				if ($photo_res['LicensePhotoId'] != ""){
					$data['LicensePhotoId'] = $photo_res['LicensePhotoId'];//驾驶员驾驶证扫描件
				}
            }

            $driver['drivername'] = I('post.drivername');//驾驶员姓名
            $driver['phone'] = I('post.phone');//驾驶员电话
            $driver['cost'] = I('post.cost');//服务费
            $driver['Address'] = I('post.address');//行政区划代码
            $driver['state'] = I('post.state');//状态
            $driver['DriverGender'] = I('post.DriverGender');//性别
            $driver['idCard'] = I('post.idCard');//身份证号
            $driver['UpdateTime'] = Date('Y-m-d H:i:sA');
            $driver['Flag'] = 1;
            $driver['userpass'] = md5(substr($driver['phone'], -6));//登陆密码

            $car_driverinfo = $driverinfo->add($driver);

            //获取添加成功返回的数据id
            $returnid =M(self::$table)->order('id desc')->find();

            $Birthday = $this->getBrithday($driver['idCard']);//获取生日

            $data['DriverId'] = $returnid['id'];//驾驶员id
            $data['CompanyId'] = M()->query('SELECT CompanyId FROM company LIMIT 1')[0]['companyid'];//公司标识
            $data['LicenseId'] = I('post.LicenseId');//机动车驾驶证号
            $data['DriverNationality'] = I('post.DriverNationality');//国籍
            $data['DriverNation'] = I('post.DriverNation');//驾驶员民族
            $data['DriverMaritalStatus'] = I('post.DriverMaritalStatus');//驾驶员婚姻状态
            $data['DriverLanguageLevel'] = I('post.DriverLanguageLevel');//驾驶员外语能力
            $data['DriverEducation'] = I('post.DriverEducation');//驾驶员学历
            $data['DriverCensus'] = I('post.DriverCensus');//户口登记机关名称
            $data['DriverAddress'] = I('post.DriverAddress');//户口住址或常驻地址
            $data['DriverContactAdress'] = I('post.DriverContactAdress');//驾驶员通信地址
            $data['DriverType'] = I('post.DriverType');//准驾车型
            $data['GetDriverLicenseDate'] = I('post.GetDriverLicenseDate');//初次领取驾驶证日期
            $data['DriverLicenseOn'] = I('post.DriverLicenseOn');//驾驶证有效期限起
            $data['DriverLicenseOff'] = I('post.DriverLicenseOff');//驾驶证有效期限止
            $data['TaxiDriver'] = I('post.TaxiDriver');//是否巡游出租汽车驾驶员
            $data['CertificateNo'] = I('post.CertificateNo');//网络预约出租汽车驾驶员资格证号
            $data['NetworkCarIssueOrganization'] = I('post.NetworkCarIssueOrganization');//网络预约出租汽车驾驶员证发证机构
            $data['NetworkCarIssueDate'] = I('post.NetworkCarIssueDate');//资格证发证日期
            $data['GetNetworkCarProofDate'] = I('post.GetNetworkCarProofDate');//初次领取资格证日期
            $data['NetworkCarProofOn'] = I('post.NetworkCarProofOn');//资格证有效起始日期
            $data['NetworkCarProofOff'] = I('post.NetworkCarProofOff');//资格证有效截止日期
            $data['RegisterDate'] = I('post.RegisterDate');//报备日期
            $data['CommercialType'] = I('post.CommercialType');//服务类型
            $data['ContractCompany'] = I('post.ContractCompany');//驾驶员合同（或协议）签署公司
            $data['ContractOn'] = I('post.ContractOn');//合同（或协议）有效期起
            $data['ContractOff'] = I('post.ContractOff');//合同（或协议）有效期止
            $data['EmergencyContact'] = I('post.EmergencyContact');//紧急情况联系人
            $data['EmergencyContactPhone'] = I('post.EmergencyContactPhone');//紧急情况联系人电话
            $data['EmergencyContactAddress'] = I('post.EmergencyContactAddress');//紧急情况联系人通信地址
            $data['FullTimeDriver'] = I('post.FullTimeDriver');//是否专职驾驶员
            $data['InDriverBlacklist'] = I('post.InDriverBlacklist');//是否在驾驶员黑名单内
            $data['UpdateTime'] = $driver['UpdateTime'];//更新时间
            $data['DriverBirthday'] = $Birthday;//出生日期
            $data['DataState'] = 0;//状态 0有效，1失效

            $m = M('car_drivermore');
            $re = $m->add($data);

            //记录操作日志
            if ($car_driverinfo == true && $re > 0) {


                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                $this->infoSub($returnid['id']);
				$this->infoSubStat($add_id);
                if ($log){
                    $this->success("添加成功!",U('Driverinfo/Index',1));
                }

            }else{
                $this->error("添加失败",'',1);
            }

        }
    }

    //司机信息修改方法 editDriverinfo()
    public function  editDriverinfo()
    {
        //判断操作数据的id的提交方式并接收
        if (empty($_POST)){
            $id = $_GET['id'];
            cookie('id',$id);
            $cid = cookie('id');
            $sql = "SELECT a.PhotoId,a.drivername,a.phone,a.cost,a.address,a.state,a.DriverGender,a.idCard,b.CompanyId,b.LicenseId,b.DriverNationality,b.DriverNation,b.DriverMaritalStatus,b.DriverLanguageLevel,b.DriverEducation,b.DriverCensus,b.DriverAddress,b.DriverContactAdress,b.LicensePhotoId,b.DriverType,b.GetDriverLicenseDate,b.DriverLicenseOn,b.DriverLicenseOff,b.TaxiDriver,b.CertificateNo,b.NetworkCarIssueOrganization,b.NetworkCarIssueDate,b.GetNetworkCarProofDate,b.NetworkCarProofOn,b.NetworkCarProofOff,b.RegisterDate,b.CommercialType,b.ContractCompany,b.ContractOn,b.ContractOff,b.EmergencyContact,b.EmergencyContactPhone,b.EmergencyContactAddress,b.FullTimeDriver,b.InDriverBlacklist FROM car_driverinfo a LEFT JOIN car_drivermore b ON a.id = b.DriverId WHERE a.id = %d ";
            $data = M()->query($sql,[$cid]);
            $this->assign("list", $data[0]);
            cookie('imgname', $data[0]['photoid']);
            $this->display();
        }

        if ($_POST) {
            $cid = cookie('id');
            $driverinfo = M (self::$table);

            if ($_FILES['PhotoId']['name'] != '' || $_FILES['LicensePhotoId']['name'] != ''){
                $info_ary['savePath'] = '/Uploads/Driverimg/';
                $info_ary['class'] = 'driverInfo';
                //查看是否有旧图片,如果有则要找出来,上传新图后销毁旧图
                $photo_sql = "SELECT a.PhotoId, b.LicensePhotoId FROM car_driverinfo a LEFT JOIN car_drivermore b ON a.id=b.DriverId WHERE a.id=%d";
                $old_path = M()->query($photo_sql,[$cid]);
                if ($_FILES['PhotoId']['name'] != '' && $old_path[0]['photoid']){
                    $old_url[] = $old_path[0]['photoid'];
                }
                if ($_FILES['LicensePhotoId']['name'] != '' && $old_path[0]['licensephotoid']){
                    $old_url[] = $old_path[0]['licensephotoid'];
                }

                $photo_res = photoUpdate($_FILES,$info_ary,$old_url);
                if ($photo_res['err'] == 2){
                    exit('<script type="text/javascript">alert("图片上传出错,请重试!");history.back(-1);</script>');
                }
                $driver['PhotoId'] = $photo_res['PhotoId'];//驾驶员照片
                $data['LicensePhotoId'] = $photo_res['LicensePhotoId'];//驾驶员驾驶证扫描件
            }

            $driver['drivername'] = I('post.drivername');//驾驶员姓名
            $driver['phone'] = I('post.phone');//驾驶员电话
            $driver['cost'] = I('post.cost');//服务费
            $driver['Address'] = I('post.address');//行政区划代码
            $driver['state'] = I('post.state');//状态
            $driver['DriverGender'] = I('post.DriverGender');//性别
            $driver['idCard'] = I('post.idCard');//身份证号
            $driver['UpdateTime'] = Date('Y-m-d H:i:sA');
            $driver['Flag'] = 2;

            $car_driverinfo = $driverinfo->where(['id'=>$cid])->save($driver);

            $Birthday = $this->getBrithday($driver['idCard']);//获取生日

            $data['CompanyId'] = M()->query('SELECT CompanyId FROM company LIMIT 1')[0]['companyid'];//公司标识
            $data['LicenseId'] = I('post.LicenseId');//机动车驾驶证号
            $data['DriverNationality'] = I('post.DriverNationality');//国籍
            $data['DriverNation'] = I('post.DriverNation');//驾驶员民族
            $data['DriverMaritalStatus'] = I('post.DriverMaritalStatus');//驾驶员婚姻状态
            $data['DriverLanguageLevel'] = I('post.DriverLanguageLevel');//驾驶员外语能力
            $data['DriverEducation'] = I('post.DriverEducation');//驾驶员学历
            $data['DriverCensus'] = I('post.DriverCensus');//户口登记机关名称
            $data['DriverAddress'] = I('post.DriverAddress');//户口住址或常驻地址
            $data['DriverContactAdress'] = I('post.DriverContactAdress');//驾驶员通信地址
            $data['DriverType'] = I('post.DriverType');//准驾车型
            $data['GetDriverLicenseDate'] = I('post.GetDriverLicenseDate');//初次领取驾驶证日期
            $data['DriverLicenseOn'] = I('post.DriverLicenseOn');//驾驶证有效期限起
            $data['DriverLicenseOff'] = I('post.DriverLicenseOff');//驾驶证有效期限止
            $data['TaxiDriver'] = I('post.TaxiDriver');//是否巡游出租汽车驾驶员
            $data['CertificateNo'] = I('post.CertificateNo');//网络预约出租汽车驾驶员资格证号
            $data['NetworkCarIssueOrganization'] = I('post.NetworkCarIssueOrganization');//网络预约出租汽车驾驶员证发证机构
            $data['NetworkCarIssueDate'] = I('post.NetworkCarIssueDate');//资格证发证日期
            $data['GetNetworkCarProofDate'] = I('post.GetNetworkCarProofDate');//初次领取资格证日期
            $data['NetworkCarProofOn'] = I('post.NetworkCarProofOn');//资格证有效起始日期
            $data['NetworkCarProofOff'] = I('post.NetworkCarProofOff');//资格证有效截止日期
            $data['RegisterDate'] = I('post.RegisterDate');//报备日期
            $data['CommercialType'] = I('post.CommercialType');//服务类型
            $data['ContractCompany'] = I('post.ContractCompany');//驾驶员合同（或协议）签署公司
            $data['ContractOn'] = I('post.ContractOn');//合同（或协议）有效期起
            $data['ContractOff'] = I('post.ContractOff');//合同（或协议）有效期止
            $data['EmergencyContact'] = I('post.EmergencyContact');//紧急情况联系人
            $data['EmergencyContactPhone'] = I('post.EmergencyContactPhone');//紧急情况联系人电话
            $data['EmergencyContactAddress'] = I('post.EmergencyContactAddress');//紧急情况联系人通信地址
            $data['FullTimeDriver'] = I('post.FullTimeDriver');//是否专职驾驶员
            $data['InDriverBlacklist'] = I('post.InDriverBlacklist');//是否在驾驶员黑名单内
            $data['UpdateTime'] = $driver['UpdateTime'];//更新时间
            $data['DriverBirthday'] = $Birthday;//出生日期
            $data['DataState'] = 0;//状态 0有效，1失效
			
            $m = M('car_drivermore');

            $re = $m->where(array('DriverId'=>$cid))->save($data);
            //记录操作日志
            if ($car_driverinfo == true && $re > 0) {
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                $log = self::writeLog(self::$table,$cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                if ($log){
                    cookie('id',null);
//                        $returnid =M(self::$table)->order('id desc')->find();
//                        $this->redisWrite(self::$table,$cid,$returnid);  //将新添加的代驾人员添加进reids缓存中
                    $this->infoSub($cid);
                    $this->success("修改成功!", U('Driverinfo/Index',1));
                }
            } else {
                $this->error("修改失败",'',1);
            }
        }
    }



    //司机信息逻辑删除方法 del（）
    public function del() {
        $driverinfo = M (self::$table);
        $id = $_GET['id'];
        $where = 1;
        $data['isdel'] = $where;
        $result = $driverinfo->where(array('id='.$id))->save($data);
        //记录操作日志
        if ($result){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            $log = self::writeLog(self::$table,$id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log){
//                $this->redisWrite(self::$table,$id,$data);  //将新添加的代驾人员添加进reids缓存中
                $this->infoSub($id);
				$this->infoSubStat($add_id);
                $this->success("删除成功!",U('Driverinfo/Index',1));
            }
        }else{
            $this->error('删除失败','',1);
        }
    }

    //批量删除
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $where['id'] = array('IN', $ids);
            $data['isdel'] = 1;
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                    $this->infoSub($id);
//                    $this->redisWrite(self::$table,$id,$data);  //将新添加的代驾人员添加进reids缓存中
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }

    //数据产生变化时更新redis中的数据
    public function redisWrite($table,$id,$data){
        $redis = self::redis();
        $redis->hMset($table.':'.$id, $data);
    }

    //审核驾驶员端用户注册
    public function checkDriverinfo(){
        $tab = M();
        $id = I('get.id');
        $sql = "UPDATE car_driverinfo SET state = %d WHERE id = %d";
        $res = $tab->execute($sql,[0,$id]);
        if ($res > 0){
            //接收当前管理员登陆名
            $auserInfo = UserInfo();
            self::writeLog("car_driverinfo", $id, 'checkDriverinfo', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            $this->infoSub($id);
            echo '<script>alert("审核成功");self.location=document.referrer;</script>';
        } else {
            echo '<script>alert("审核失败");self.location=document.referrer;</script>';
        }
    }

//车辆基本信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoDriver',$id);
    }

//公司运营规模信息报送
    function infoSubStat($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoCompanyStat',$id);
    }
}

