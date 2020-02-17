<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/30
 * Time: 10:19
 */

namespace App\Controller;

use Common\Common\App;
use Common\Common\BaseHomeController;
use Think\Controller;
use Common\Common\sendAPI;

class NetCarController extends Controller{
    public function index()
    {
        $this->display();
    }

    public function driver_register(){
        $token = I('post.token');
        $phone = I('post.phone');
        $password = I('post.password');
        $code = I('post.code');

        $tkCheck = $this->tokenCheck($token);
        if (!$tkCheck){
            $rs = array('error'=>1,'code'=>1,'msg'=>'token错误');
            exit(json_encode($rs));
        }

        $driver_info = cookie('driver_info');
        if ($driver_info['phone'] == $phone && $driver_info['code'] == $code){
            $rs_phone = $this->verificationPhone($phone);
            if (!$rs_phone){
                $model = M();
                $model->startTrans();
                $time = date('Y-m-d H:i:s',time());
                $password = md5($password);
                $sql = "INSERT INTO car_driverinfo (phone, uerpass, addtime, UpdateTime) VALUE ('%s', '%s', '%s', '%s')";
                $add_info = M()->execute($sql, [$phone, $password, $time, $time]);

                $sql = "INSERT INTO car_drivermore (DriverId, CompanyId, UpdateTime) VALUE ('%s', 'hbcx', '%s')";
                $add_more = M()->execute($sql, [$add_info, $time]);
                if ($add_info && $add_more){
                    $model->commit();
                    $rs = array('error'=>0,'code'=>0,'msg'=>'success');
                }else{
                    $model->rollback();
                    $rs = array('error'=>1,'code'=>4,'msg'=>'注册失败');
                }
            }else{
                $rs = array('error'=>1,'code'=>3,'msg'=>'该手机号已被注册');
            }
        }else{
            $rs = array('error'=>1,'code'=>2,'msg'=>'验证码错误');
        }
        echo json_encode($rs);
    }

    /**
     * 驾驶员信息补充或修改
     */
    public function driverInfoEdit(){
        $m = M();
        $token = I('post.token');
        $tkCheck = $this->tokenCheck($token);
        if (!$tkCheck){
            $rs = array('error'=>1,'code'=>1,'msg'=>'token错误');
            exit(json_encode($rs));
        }
        if (count($_FILES) > 0){
            $info_ary['savePath'] = '/Uploads/netdriveimg/';
            $result = $this->photoUpdate($_FILES,$info_ary);
            if ($result['res'] == 0){
                foreach ($result['photo_info'] as $key=>$val){
                    if ($key == 'LicensePhotoId'){
                        $driver_more['LicensePhotoId'] = $val['savepath'].$val['savename'];
                    }elseif ($key == 'PhotoId'){
                        $driver_info['PhotoId'] = $val['savepath'].$val['savename'];
                    }
                }
            }else{
                $rs = array('error'=>1,'code'=>2,'msg'=>'图片上传失败');
                exit(json_encode($rs));//返回图片上传失败信息,并截停
            }
        }
        $phone = I('post.phone');
        $driver_info['drivername'] = I('post.drivername');//驾驶员姓名
        $driver_info['userpass'] = I('post.userpass');//登陆密码
        $driver_more['Address'] = $car_info['Address'] = I('post.Address');//注册地行政区划代码
        $driver_info['idCard'] = I('post.idCard');//身份证号/驾驶号
        $driver_more['GetDriverLicenseDate'] = I('post.GetDriverLicenseDate');//初次领取驾驶证日期(年-月-日)
        $car_net['VehicleNo'] = I('post.VehicleNo');//车牌号
        $car_net['VehicleType'] = I('post.VehicleType');//车辆类型(小型轿车,小型越野客车,小型普通客车...)
        $car_info['OwnerName'] = I('post.OwnerName');//车辆所有人
        $car_info['CertifyDateA'] = I('post.CertifyDateA');//车辆注册日期
        if ($driver_info['drivername'] != '' && $driver_info['idCard'] != '' && $driver_more['GetDriverLicenseDate'] != '' && $car_net['VehicleNo'] != '' && $car_net['VehicleType'] != '' && $car_info['OwnerName'] != '' && $car_info['CertifyDateA'] != '' && $driver_info['userpass'] != ''){
            $sql = "SELECT id, CarId FROM car_driverinfo WHERE phone='%s'";
            $deriver = $m->query($sql,[$phone]);
            if ($deriver){
                $m->startTrans();//开启事务
                $driver_info['userpass'] = md5($driver_info['userpass']);
                $add_driver_info = $m->table('car_driverinfo')->where(['id'=>$deriver[0]['id']])->save($driver_info);
                $add_driver_more = $m->table('car_drivermore')->where(['DriverId'=>$deriver[0]['id']])->save($driver_more);

                if ($deriver[0]['carid']){
                    $add_car_net = $m->table('car_net')->save($car_net);
                    $add_car_info = $m->table('car_info')->save($car_info);
                }else{
                    $add_car_net = $m->table('car_net')->add($car_net);
                    $car_info['CarId'] = $add_car_net;
                    $add_car_info = $m->table('car_info')->add($car_info);
                }

                if ($add_driver_info || $add_driver_more || $add_car_net || $add_car_info){
                    $m->commit();//事务执行
                    $rs = array('error'=>0,'code'=>0,'msg'=>'success','driver_register'=>array('driver_id'=>$deriver[0]['id'],'CarId'=>$deriver[0]['id']));
                }else{
                    $rs = array('error'=>1,'code'=>5,'msg'=>'信息修改失败');
                    $m->rollback();//事务回滚
                }
            }else{
                $rs = array('error'=>1,'code'=>4,'msg'=>'驾驶员信息异常');
            }

        }else{
            $rs = array('error'=>1,'code'=>3,'msg'=>'参数错误');
        }
        echo json_encode($rs);
    }

    /**
     * 发送验证码
     */
    public function sendCode(){
        $token = I('post.token');
        $phone = I('post.phone');

        $rs_token = $this->tokenCheck($token);//验证token
        if (!$rs_token){
            $rs = array('error'=>1, 'code'=>1, 'msg'=>'token错误');
            exit(json_encode($rs));
        }

        $driver = $this->verificationPhone($phone);//检测手机号是否被注册
        if ($driver){
            $rs = array('error'=>1, 'code'=>2, 'msg'=>'手机号已被注册');
        }else{
            $code = rand(1000,9999);
            $driver_info = ['code'=>$code,'phone'=>$phone];
            cookie('driver_info',$driver_info,600);
            $rs_send = $this->registerCode($code,$phone);//发送验证码
            if ($rs_send){
                $rs = array('error'=>0, 'code'=>0, 'msg'=>'success');
            }else{
                $rs = array('error'=>1, 'code'=>3, 'msg'=>'验证码发送失败,请重试!');
            }
        }
        echo json_encode($rs);
    }

    /**验证手机号是否注册
     * @param $phone 待验证的手机号
     */
    public function verificationPhone($phone){
        $sql = "SELECT id FROM car_driverinfo WHERE phone='%s'";
        $driver = M()->query($sql, [$phone]);
        if ($driver){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 图片上传方法
     *
     * @param array $files 文件上传信息
     * @param array $info_ary 上传文件的配置信息[savePath=>存储路径,maxSize=>文件大小,exts=>上传类型,autoSub=>是否设置子目录,uploadReplace=>是否同名替换]
     * @param array $old_url 需要删除的旧的文件路径(数据库中存储的路径)
     * @return int
     */
    private function photoUpdate($files, $info_ary,$old_url=''){
        if ($info_ary['savePath'] == '' || $files == ''){
            $res = array('res'=>1,'msg'=>'上传图片为空,或储存路径为空');
        }else{
            $info_ary['maxSize'] = $info_ary['maxSize']=='' ? 512000 : $info_ary['maxSize'];//判断是否设置了限制大小,如果未限制则启用默认最大限制500KB;
            $info_ary['exts'] = $info_ary['exts']=='' ? ['jpg', 'png', 'jpeg'] : $info_ary['exts'];
            $info_ary['autoSub'] = $info_ary['autoSub']=='' ? true : $info_ary['autoSub'];
            $info_ary['uploadReplace'] = $info_ary['uploadReplace']=='' ? true : $info_ary['uploadReplace'];
            // 实例化上传类
            $upload = new \Think\Upload();
            // 设置附件上传大小
            $upload->maxSize = $info_ary['maxSize'];
            // 设置附件上传类型
            $upload->exts = $info_ary['exts'];
            // 设置附件上传目录
            $upload->savePath = $info_ary['savePath'];
            //设置子目录
            $upload->autoSub = $info_ary['autoSub'];
            //同名则替换
            $upload->uploadReplace = $info_ary['uploadReplace'];
            //设置保存格式
            $upload->saveExt ='jpg';

            // 上传文件
            $info = $upload->upload($files);
            // 上传错误提示错误信息
            if ($info){
                if (count($old_url)){    //删除旧文件
                    foreach ($old_url as $key => $val){
                        deleteFile($val['url']);
                    }
                }
                $res = array('res'=>0,'msg'=>'上传成功','photo_info'=>$info);
            }else{
                $res = array('res'=>2,'msg'=>'上传失败');
            }
        }
        return $res;
    }

    /**
     * 驾驶端注册验证码短信发送
     */
    public function registerCode($code,$phone){
        $create = date('Y-m-d H:i',$_SERVER['REQUEST_TIME']+600);//创建当前时间
        $url 		= "http://www.api.zthysms.com/sendSms.do";//提交地址
        $username 	= 'huabanghy';//用户名
        $password 	= 'Ujwidz';//原密码
        $sendAPI = new sendAPI($url, $username, $password);
        $content = '尊敬的用户:这是你本次注册的验证码:'.$code.'，该验证码在'.$create.'前有效，请尽快使用。【华邦出行】';//57字
        $data = array(
            'content' 	=> $content,//短信内容
            'mobile' 	=> $phone,//手机号码
            'xh'		=> ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $return = $sendAPI->sendSMS('POST');//GET or POST
        $result = explode(',', $return);
        if ($result[0] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * token验证
     * @param string $token App端传过来的token值
     */
    public function tokenCheck($token){
        $time_one = time();
        $time_two = $time_one-60;
        $time_one = md5($time_one);
        $time_two = md5($time_two);
        if ($time_one != $token && $time_two != $token){
            return false;
        }else{
            return true;
        }
    }

    public function test(){
        echo '收到<br/>';
        $rs = $this->registerCode('6438',13628500972);
        if ($rs){
            echo 'true';
        }else{
            echo 'false';
        }
    }
}