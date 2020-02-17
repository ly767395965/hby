<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Org\Util\Date;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 * 公司基本信息控制器
 */
class NetCarCompanyController extends BaseController{
    public function index() {
        $site = M("company");
        if (empty($_POST)) {
            $result = $site->select();
            $this->assign("list", $result);
            $this->display();
        }
        if ($_POST) {
            $id = $_POST['id'];
            if ($_FILES['LealPhoto']['name'] != ''){
                $info_ary['savePath'] = '/Uploads/Company/';
                $info_ary['autoSub'] = false;
                $info_ary['class'] = 'companyInfo';
                //查看是否有旧图片,如果有则要找出来,上传新图后销毁旧图
                $photo_sql = "SELECT LealPhoto FROM company WHERE id=%d";
                $old_path = M()->query($photo_sql,[$id]);
                if ($_FILES['LealPhoto']['name'] != '' && $old_path[0]['lealphoto']){
                    $old_url = $old_path[0]['lealphoto'];
                }else{
                    $old_url = '';
                }
                $photo_res = photoUpdate($_FILES,$info_ary,$old_url);
                if ($photo_res['err'] == 2){
                    exit('<script type="text/javascript">alert("图片上传出错,请重试!");history.back(-1);</script>');
                }elseif ($photo_res['err'] == 3){
                    exit('<script type="text/javascript">alert("图片上传至部级平台,请重试或联系技术人员!");history.back(-1);</script>');
                }
                $array['LealPhoto'] = $photo_res['LealPhoto'];//车辆照片
            }


            if ($id == "" ) {
                $array['CompanyId'] = I('post.companyid');
                $array['CompanyName'] = I('post.companyname');
                $array['RevokeTime'] = I('post.revoketime');
                $array['Address'] = I('post.address');
                $array['ContactAddress'] = I('post.contactaddress');
                $array['EconomicType'] = I('post.economictype');
                $array['RegCapital'] = I('post.regcapital');
                $array['LefalName'] = I('post.lefalname');
                $array['LegalID'] = I('post.legalid');
                $array['LegalPhone'] = I('post.legalphone');
//                $array['LealPhoto'] = I('post.lealphoto');
                $array['UpdateTime'] = Date('Y-m-d H:i:sA');
                $array['Certificate'] = I('post.certificate');
                $array['OperationArea'] = I('post.operationarea');
                $array['Organization'] = I('post.organization');
                $array['StartDate'] = I('post.startdate');
                $array['StopDate'] = I('post.stopdate');
                $array['CertifyDate'] = I('post.certifydate');
                $array['IDPhoto'] = I('post.idphoto');
                $array['BusinessScope'] = I('post.businessscope');
                $array['Flag'] = 1;
                $add_id = $site->add($array);
                if ($add_id) {
                    session('companyId',$array['CompanyId']);//更新php端的公司标识
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    self::writeLog('company', $add_id, 'addCompanyInfo', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    $this->success("添加成功!", U('NetCarCompany/Index'));
                    $this->infoSub($add_id);//公司信息报送
                    $this->infoSubLicense($add_id);//公司经营许可信息报送
                } else {
                    $this->error("添加失败");
                }
            } else {
                $array['CompanyId'] = I('post.companyid');
                $array['CompanyName'] = I('post.companyname');
                $array['RevokeTime'] = I('post.revoketime');
                $array['Address'] = I('post.address');
                $array['ContactAddress'] = I('post.contactaddress');
                $array['EconomicType'] = I('post.economictype');
                $array['RegCapital'] = I('post.regcapital');
                $array['LefalName'] = I('post.lefalname');
                $array['LegalID'] = I('post.legalid');
                $array['LegalPhone'] = I('post.legalphone');
//                $array['LealPhoto'] = I('post.lealphoto');
                $array['UpdateTime'] = Date('Y-m-d H:i:sA');
                $array['Certificate'] = I('post.certificate');
                $array['OperationArea'] = I('post.operationarea');
                $array['Organization'] = I('post.organization');
                $array['StartDate'] = I('post.startdate');
                $array['StopDate'] = I('post.stopdate');
                $array['CertifyDate'] = I('post.certifydate');
                $array['IDPhoto'] = I('post.idphoto');
                $array['BusinessScope'] = I('post.businessscope');
                $array['Flag'] = 2;
                $re = $site->where(array('id'=> $id))->save($array);
                exit;
                if ($re) {
                    session('companyId',$array['CompanyId']);//更新php端的公司标识
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    self::writeLog('company', $id, 'editCompanyInfo', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    $this->infoSub($id);//公司信息报送
                    $this->success("修改成功!", U('NetCarCompany/Index'));
                } else {
                    $this->error("修改失败");
                }
            }

        }
    }

    //公司基本信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $token_v = Date('YmdHi',time());
        $token_v = md5($token_v);
        $sub->postSub('/companyId',['token_v'=>$token_v]);//更新node端的公司标识
		$sub->controlSub('baseInfoCompanyPermit',$id);//公司经营许可信息报送
        $sub->controlSub('baseInfoCompany',$id);//公司经营基本信息报送
		
    }

    //公司经营许可信息报送
    function infoSubLicense($id){
        $sub = new SubmittedController();
        
    }

}