<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Think\Controller;

/**
 * Class CarController
 * @package Admin\Controller
 * 网约车平台服务机构信息控制器
 */
class NetCarInstitutionController extends BaseController{
    public function index() {
        $site = M("company_server");
        if (empty($_POST)) {
            $m = M();
            $sql = "SELECT CompanyId FROM company WHERE id = 1 ";
            $info = $m->query($sql);
            $result = $site->select();
            $result[0]['companyid'] = $info[0]['companyid'];
            $this->assign("list", $result);
            $this->display();
        }
        if ($_POST) {
            $id = $_POST['id'];
            if ($id == "" ) {
                $array['ServiceName'] = I('post.servicename');
                $array['Address'] = I('post.address');
                $array['ServiceNo'] = I('post.serviceno');
                $array['DetailAddress'] = I('post.detailaddress');
                $array['ResponsibleName'] = I('post.responsiblename');
                $array['ResponsiblePhone'] = I('post.responsiblephone');
                $array['Managername'] = I('post.managername');
                $array['ManagerPhone'] = I('post.managerphone');
                $array['ContactPhone'] = I('post.contactphone');
                $array['MailAddress'] = I('post.mailaddress');
                $array['CreateDate'] = I('post.createdate');
                $array['UpdateTime'] = Date('Y-m-d H:i:sA');
                $add_id = $site->add($array);
                if ($add_id) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    self::writeLog('company_server', $add_id, 'addCompanyServer', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    $this->success("添加成功!", U('NetCarInstitution/Index'));
                    $this->infoSub($add_id);
                } else {
                    $this->error("添加失败");
                }
            } else {
                $array['ServiceName'] = I('post.servicename');
                $array['Address'] = I('post.address');
                $array['ServiceNo'] = I('post.serviceno');
                $array['DetailAddress'] = I('post.detailaddress');
                $array['ResponsibleName'] = I('post.responsiblename');
                $array['ResponsiblePhone'] = I('post.responsiblephone');
                $array['Managername'] = I('post.managername');
                $array['ManagerPhone'] = I('post.managerphone');
                $array['ContactPhone'] = I('post.contactphone');
                $array['MailAddress'] = I('post.mailaddress');
                $array['CreateDate'] = I('post.createdate');
                $array['UpdateTime'] = Date('Y-m-d H:i:sA');
                $array['Flag'] = 2;
                $re = $site->where(array('id'=> $id))->save($array);
                if ($re) {
                    //接收当前管理员登陆名
                    $auserInfo = UserInfo();
                    self::writeLog('company_server', $id, 'editCompanyServer', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                        $this->success("修改成功!", U('NetCarInstitution/Index'));
                        $this->infoSub($id);
                } else {
                    $this->error("修改失败");
                }
            }

        }
    }

    //公司服务机构信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoCompanyService',$id);
    }






}