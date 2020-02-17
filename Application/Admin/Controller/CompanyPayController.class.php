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
 * 公司支付信息控制器
 */
class CompanyPayController extends BaseController
{
    public function index(){

        if (empty($_POST)){
            $sql = "SELECT id,PayName,PayId,PayType,PayScope,PrepareBank,CountDate FROM company_pay";
            $list = M()->query($sql);
            if ($list){
                $this->assign('list',$list);
            }
            $this->display();
        }else{
            $id = I('post.id');
            $sql = 'SELECT CompanyId FROM company WHERE State=0 AND Flag != 3';
            $company = M('company')->query($sql);
            $data['PayName'] = $company[0]['CompanyId'];
            $data['PayName'] = I('post.PayName');
            $data['PayId'] = I('post.PayId');
            $data['PayType'] = I('post.PayType');
            $data['PayScope'] = I('post.PayScope');
            $data['PrepareBank'] = I('post.PrepareBank');
            $data['CountDate'] = I('post.CountDate');
            $data['UpdateTime'] = Date('Y-m-d H:i:s',time());
            if ($id == ''){
                $add = M('company_pay')->add($data);
                $id = $add;
            }else{
                $edit = M('company_pay')->where(array('id'=>$id))->save($data);
            }
            if ($add || $edit){
                $auserInfo = UserInfo();
                self::writeLog('company_pay', $id, 'editCompanyPay', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                $this->success("保存成功!", U('CompanyPay/Index'));
                $this->infoSub($id);
            }else{
                $this->error("保存失败");
            }
        }
    }

    //公司支付信息报送
    function infoSub($id){
        $sub = new SubmittedController();
        $sub->controlSub('baseInfoCompanyPay',$id);
    }

    /**
     * @ 添加网约车平台公司支付信息
     * editCompanyPay()
     */
    public function addCompanyPay(){
        if (empty($_POST)){
            $this->display();
        }
        if ($_POST){
            $sql = 'SELECT CompanyId FROM company WHERE State=0 AND Flag != 3';
            $company = M('company')->query($sql);
            $data['PayName'] = $company[0]['CompanyId'];
            $data['PayName'] = I('post.PayName');
            $data['PayId'] = I('post.PayId');
            $data['PayType'] = I('post.PayType');
            $data['PayScope'] = I('post.PayScope');
            $data['PrepareBank'] = I('post.PrepareBank');
            $data['CountDate'] = I('post.CountDate');
            $data['UpdateTime'] = Date('Y-m-d H:i:s',time());
            $add = M('company_pay')->add($data);
            $id = $add;

            if ($add){
                $auserInfo = UserInfo();
                self::writeLog('company_pay', $id, 'addCompanyPay', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                $this->success("提交成功", U('CompanyPay/Index'));
                $this->infoSub($id);
            }else{
                $this->error("提交失败");
            }
        }

    }

    /**
     * @ 修改网约车平台公司支付信息
     * editCompanyPay()
     */
    public function editCompanyPay()
    {
        if (empty($_POST)){
            $id = I('get.id');
            $sql = "SELECT id,PayName,PayId,PayType,PayScope,PrepareBank,CountDate FROM company_pay WHERE id = %d ";
            $list = M()->query($sql,[$id]);
            if ($list){
                $this->assign('list',$list[0]);
            }
            $this->display();
        }
        if ($_POST){
            $id = I('post.id');
            $sql = 'SELECT CompanyId FROM company WHERE State=0 AND Flag != 3';
            $company = M('company')->query($sql);
            $data['PayName'] = $company[0]['CompanyId'];
            $data['PayName'] = I('post.PayName');
            $data['PayId'] = I('post.PayId');
            $data['PayType'] = I('post.PayType');
            $data['PayScope'] = I('post.PayScope');
            $data['PrepareBank'] = I('post.PrepareBank');
            $data['CountDate'] = I('post.CountDate');
            $data['UpdateTime'] = Date('Y-m-d H:i:s',time());
            $edit = M('company_pay')->where(array('id'=>$id))->save($data);
            if ($edit){
                $auserInfo = UserInfo();
                self::writeLog('company_pay', $id, 'editCompanyPay', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                $this->success("保存成功!", U('CompanyPay/Index'));
                $this->infoSub($id);
            }else{
                $this->error("保存失败");
            }
        }

    }

    public function aa(){
        $this->display();
    }
}