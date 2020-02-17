<?php
namespace Common\Common;

use Think\Controller;
use Think\Page;
use Think\Auth;


class BaseController extends Controller
{
    //权限判断
    protected function _initialize()
    {


        $auserInfo = UserInfo();

        if (!is_numeric($auserInfo['id'])) {
            exit("<script type='text/javascript'>alert('你还未登录,请登录后访问!');window.parent.frames.location.href='" . U('Login/Index') . "?backurl=" . base64_encode(__SELF__) . "';</script>");
        }else{
            if (!isset($_GET['loop'])){
                $user = M()->query('SELECT logintime FROM admin_user WHERE id=%d',[$auserInfo['id']]);
                if ($user){
                    if ($user[0]['logintime'] == $auserInfo['logintime']){
                        $user_info = aryAuthcode($auserInfo,'decode','hbykj');//重新加密设置
                        cookie('auserInfo',$user_info,1200);
                    }else{
                        cookie('auserInfo',null);
                        exit("<script type='text/javascript'>alert('异处登陆,注意账号安全!');window.parent.frames.location.href='" . U('Login/Index') . "?backurl=" . base64_encode(__SELF__) . "';</script>");
                    }
                }else{
                    cookie('auserInfo',null);
                    exit("<script type='text/javascript'>alert('账号异常,请重新登录!');window.parent.frames.location.href='" . U('Login/Index') . "?backurl=" . base64_encode(__SELF__) . "';</script>");
                }

            }

        }

        //如果  是超级管理员  有全部权限
        if ($auserInfo['type'] == 1) {
            return TRUE;
        }
        $auth = new Auth();
        $auserInfo = UserInfo();

        $ruleName = CONTROLLER_NAME . '/' . ACTION_NAME; //规则唯一标识
        $s = $auth->check($ruleName, $auserInfo['id']);

        if ($auth->check($ruleName, $auserInfo['id'])) {
            return TRUE;
        } else {

            exit("<script type='text/javascript'>alert('您无权访问此功能!');history.go(-1);</script>");

        }

    }

    //默认配置

    function check_verify($code, $id = "")
    {
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }


//    function __construct() {
//        parent::__construct();
//    }
    /**
     * pageDisplay 分页显示方法，
     * @param string $sql SQL查询语句
     * @param string $countSql 获取分页数据总条数 select count(id) from 表名 where 条件
     * @param string $pageNum 每页显示几条数据
     * @param array $ary 参数数组，用于条件查询参数传递，防止SQL注入的预处理
     * @param string $keyName 读取总记录条数的参数 要与countSql中的count(id)语句一样
     * @param string $listName 模板name名，传递数据到模板关联
     * @param string $showPage 模板分页绑定的输出参数，显示分页导航
     * @param bool $isPage 分页开关，True显示分页，False不显示分页，只显数据
     * @return void
     */

    function pageDisplay($sql, $countSql, $pageNum, $ary, $keyName, $listName, $showPage, $isPage)
    {

        $m = M();
        if ($isPage == true) {
            if ($ary == null){
                $countList = $m->query($countSql);
            }else{
                $countList = $m->query($countSql, $ary);
            }
            $count = $countList[0][$keyName];
            $page = new Page($count, $pageNum);
            $page->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条记录  第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
            $page->setConfig('prev', '上一页');
            $page->setConfig('next', '下一页');
            $page->setConfig('last', '尾页');
            $page->setConfig('first', '首页');
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $show = $page->show();
            $sql = $sql . " LIMIT %d,%d";
            if ($ary == null){
                $ary = [$page->firstRow,$page->listRows];
            }else{
                array_push($ary,$page->firstRow,$page->listRows);
            }
            $list = $m->query($sql, $ary);

//            var_dump($list);
            $this->assign($listName, $list);
            $this->assign($showPage, $show);
        } else {
            if ($ary == null){
                $list = $m->query($sql);
            }else{
                $list = $m->query($sql, $ary);
            }
//            var_dump($list);
            $this->assign($listName, $list);
        }

    }

    /**
     * 操作日志记录方法
     * @string $table 当前操作的表名
     * @int 当前操作的数据编号
     * @string $operate 操作
     * @date $disposeDate 当前操作时间
     * @string $adminName 当前操作员
     * @return int 增删改的相应反回值
     * @调用方式 $log = self::writeLog('publish_event',$returnId['id'],'add',date('Y-m-d H:i:sA'),$cookieName['name'])
     */
    public static function writeLog($table, $dataId, $operate, $disposeDate, $adminName)
    {
        $log = M('site_log');
        $system['tablename'] = $table;
        $system['dataid'] = $dataId;
        switch ($operate) {
            case 'delAllTrain' :
                $operate = '批量删除班次信息';
                break;
            case 'delTrain' :
                $operate = '删除班次信息';
                break;
            case 'addTrain' :
                $operate = '添加班次';
                break;
            case 'delAllAcademy' :
                $operate = '批量删除院校名称';
                break;
            case 'delAcademy' :
                $operate = '删除院校名称';
                break;
            case 'editAcademy' :
                $operate = '修改院校名称';
                break;
            case 'addschool' :
                $operate = '添加院校名称';
                break;
            case 'addCompanyPay' :
                $operate = '添加网约车平台公司支付信息';
                break;
            case 'clearLoginFail' :
                $operate = '清除管理员错误登录次数';
                break;
            case 'editCompanyPay' :
                $operate = '修改网约车平台公司支付信息';
                break;
            case 'cancelNetCarOrder' :
                $operate = '网约车订单取消';
                break;
            case 'delTrain' :
                $operate = '删除驾驶员培训记录';
                break;
            case 'delAllDriverTrain' :
                $operate = '批量删除驾驶员培训记录';
                break;
            case 'addDriverTrain' :
                $operate = '添加驾驶员培训记录';
                break;
            case 'thawTopic1' :
                $operate = '标记培训课题为有效';
                break;
            case 'thawTopic' :
                $operate = '标记培训课题为无效';
                break;
            case 'delTopic' :
                $operate = '删除驾驶员培训课题';
                break;
            case 'delAllTopic' :
                $operate = '批量删除驾驶员培训课题';
                break;
            case 'editTopic' :
                $operate = '修改驾驶员培训课题';
                break;
            case 'addTopic' :
                $operate = '添加驾驶员培训课题';
                break;
            case 'delManagement' :
                $operate = '删除地域信息';
                break;
            case 'addManagement' :
                $operate = '添加地域信息';
                break;
            case 'editManagement' :
                $operate = '修改经营地域信息';
                break;
            case 'driverGrade' :
                $operate = '网约车驾驶员信誉定级';
                break;
            case 'effectiveFreightInfo' :
                $operate = '标记网约车运价信息为有效';
                break;
            case 'invalidFreightInfo' :
                $operate = '标记网约车运价信息为无效';
                break;
            case 'editFreightInfo' :
                $operate = '修改网约车运价信息';
                break;
            case 'addFreightInfo' :
                $operate = '添加网约车运价信息';
                break;
            case 'addViolation' :
                $operate = '添加驾驶员违章信息';
                break;
            case 'driverHandle' :
                $operate = '处理驾驶员违章信息';
                break;
            case 'evaluateHandle' :
                $operate = '处理乘客投诉信息';
                break;
            case 'editCompanyServer' :
                $operate = '修改服务机构信息';
                break;
            case 'addCompanyServer' :
                $operate = '添加服务机构信息';
                break;
            case 'editCompanyInfo' :
                $operate = '修改公司基本信息';
                break;
            case 'addCompanyInfo' :
                $operate = '添加公司基本信息';
                break;
            case 'dispatch' :
                $operate = '网约车派单';
                break;
            case 'checkDriverinfo' :
                $operate = '审核代驾';
                break;
            case 'add' :
                $operate = '添加';
                break;
            case 'relet' :
                $operate = '普通客户续租';
                break;
            case 'max_relet' :
                $operate = '大客户续租';
                break;
            case 'cancelOrder' :
                $operate = '大客户取消订单';
                break;
            case 'max_order_opes' :
                $operate = '大客户录违章';
                break;
            case 'order_charge' :
                $operate = '大客户收取预付金额';
                break;
            case 'max_add' :
                $operate = '大客户手动录单';
                break;
            case 'edit' :
                $operate = '修改';
                break;
            case 'editpass' :
                $operate = '初始化密码';
                break;
            case 'del' :
                $operate = '删除';
                break;
            case 'Thaw' :
                $operate = '解冻';
                break;
            case 'Frozen' :
                $operate = '冻结';
                break;
            case 'delAll' :
                $operate = '批量删除';
                break;
            case 'send' :
                $operate = '派车';
                break;
            case 'driver' :
                $operate = '指派代驾';
                break;
            case 'take_car' :
                $operate = '客户取车';
                break;
            case 'cost' :
                $operate = '成本录入';
                break;
            case 'relet' :
                $operate = '续租';
                break;
            case 'charge' :
                $operate = '财务收费';
                break;
            case 'return_car' :
                $operate = '客户还车';
                break;
            case 'max_return_car' :
                $operate = '大客户还车';
                break;
            case 'dp_price' :
                $operate = '违章录入';
                break;
            case 'deposit' :
                $operate = '同意退押';
                break;
            case 'cancel' :
                $operate = '取消订单';
                break;
            case 'max_cancel' :
                $operate = '取消订单(大客户)';
                break;
            case 'refund' :
                $operate = '客户退款';
                break;
            case 'return_cost' :
                $operate = '财务结账';
                break;
            case 'max_return_cost' :
                $operate = '大客户结账';
                break;
            case 'fill_price' :
                $operate = '财务收账';
                break;
            case 'refund_cost' :
                $operate = '财务退款';
                break;
            case 'deposit_cost' :
                $operate = '收取押金';
                break;
            case 'deposit_refund' :
                $operate = '退还押金';
                break;
            case '不显示' :
                $operate = '不显示';
                break;
            case '显示' :
                $operate = '显示';
                break;
            case '审核通过' :
                $operate = '审核通过';
                break;
            case '取消审核' :
                $operate = '取消审核';
                break;
            case '添加代理商' :
                $operate = '添加代理商';
                break;
            case '修改代理商' :
                $operate = '修改代理商';
                break;
            case '禁用代理商' :
                $operate = '禁用代理商';
                break;
            case '启用代理商' :
                $operate = '启用代理商';
                break;
            case '进行结算' :
                $operate = '进行结算';
                break;
            case '取消申请' :
                $operate = '取消申请';
                break;
            case '申请结账' :
                $operate = '申请结账';
                break;
            case '发行优惠劵' :
                $operate = '发行优惠劵';
                break;
            case '禁发优惠劵' :
                $operate = '禁发优惠劵';
                break;
            case '启用优惠劵' :
                $operate = '启用优惠劵';
                break;
            case '禁用优惠劵' :
                $operate = '禁用优惠劵';
                break;
            case '禁用优惠活动' :
                $operate = '禁用优惠活动';
                break;
            case '启用优惠活动' :
                $operate = '启用优惠活动';
                break;
            case '直接发放优惠卷' :
                $operate = '直接发放优惠卷';
                break;
            case '取消代驾' :
                $operate = '取消代驾';
                break;
            case '大客户派车' :
                $operate = '大客户派车';
                break;
            case '大客户指派代驾' :
                $operate = '大客户指派代驾';
                break;
            default :
                $operate = '错误';
        }

        $system['operate'] = $operate;
        $system['disposedate'] = $disposeDate;
        $system['adminname'] = $adminName;
        $res = $log->add($system);
        return $res;
    }

    /**
     * 过滤相应特殊字符，保留样式，防止SQL注入
     * @string $str
     * @return string
     */
    public static function filterStr($str)
    {
        $filteredStr = "sql,insert,update,join,union,
                        where,like,drop,create,modify,
                        rename,after,cas,1==1,<script>,
                        </script>,fuck,共产党,傻逼,你妈,
                        妈逼,日你妈,操,操你妈,
                        ";
        $filteredArr = explode(',', $filteredStr);
        foreach ($filteredArr as $value) {
            $str = str_replace($value, '***', $str);
        }
        return $str;
    }

    /*当前登录管理员*/
    protected static function cookieName()
    {
        $cookieName = UserInfo();
        return $cookieName['name'];
    }
    //处理送车地址
    public function carADHandle($str){
        $strAry = [];
        array_push($strAry,explode(',',$str));
        return $strAry;
    }

    /**
     * 功能：根据身份证号，自动返回生日
     * @param stirng $IDCard 身份证号
     * @return Ambigous <string, NULL>
     */
    function getBrithday ($idCard)
    {
        if (strlen($idCard) == 18) {
            $birthday = substr($idCard, 6, 4) . '-' .
                substr($idCard, 10, 2) . '-' .
                substr($idCard, 12, 2);
        } elseif (strlen($idCard) == 15) {
            $birthday = "19" . substr($idCard, 6, 2) . '-' .
                substr($idCard, 8, 2) . '-' .
                substr($idCard, 10, 2);
        } else {
            $birthday = null;
        }
        return $birthday;
    }

}