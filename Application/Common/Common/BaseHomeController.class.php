<?php
namespace Common\Common;
use Think\Controller;
use Think\Page;

class BaseHomeController extends Controller {

    function __construct() {
        parent::__construct();
    }
    /**
     * pageDisplay 分页显示方法，
     * @param string $sql SQL查询语句
     * @param string $countSql 获取分页数据总条数 select count(id) from 表名 where 条件
     * @param string $pageNum 每页显示几条数据
     * @param array $ary 参数数组，用于条件查询参数传递，防止SQL注入的预处理
     * @param string $keyName 读取总记录条数的参数 要与countSql中的count(id)语句一样
     * @param string $listName 模板name名，传递数据到模板关联
     * @param string $showPage 模板分页绑定的输出参数，显示分页导航
     * @param bool $isPage 分页开关，True显示分页，False不显示分页
     * @return void
     */
    function pageDisplay($sql, $countSql, $pageNum, $ary, $keyName, $listName, $showPage, $ajaxHtml, $isPage)
    {

        $m = M();
        if ($isPage == true) {
            $countList = $m->query($countSql, $ary);
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
            array_push($ary,$page->firstRow,$page->listRows);
            $list = $m->query($sql, $ary);
//            var_dump($show);
            if (IS_AJAX) {
                $this->assign($listName, $list);
                $this->assign($showPage, $show);
                $html = $this->fetch($ajaxHtml);
                $this->ajaxReturn($html);
            }
            $this->assign($listName, $list);
            $this->assign($showPage, $show);
        } else {
            $list = $m->query($sql, $ary);
            $this->assign($listName, $list);
        }


    }

    //判断车型图片
    function pageDisplayimg($sql, $countSql, $pageNum, $ary, $keyName, $listName, $showPage, $ajaxHtml, $isPage)
    {

        $m = M();
        if ($isPage == true) {
            $countList = $m->query($countSql, $ary);
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
            array_push($ary,$page->firstRow,$page->listRows);
            $list = $m->query($sql, $ary);
            foreach ($list as $key =>$data){
//判断图片文件是否存在
                if (file_exists("./Public".$data['frontimg'])){
//            检测图片文件是否可读
                    if (is_readable("./Public".$data['frontimg'])){
                        $frontimg = $data['frontimg'];
                    } else {
                        $frontimg = '';
                    }

                } else {
                    $frontimg = '';
                }
                if (file_exists("./Public".$data['leftanterior'])){
//            检测图片文件是否可读
                    if (is_readable("./Public".$data['leftanterior'])){
                        $leftanterior = $data['leftanterior'];
                    } else {
                        $leftanterior = '';
                    }
                } else {
                    $leftanterior = '';
                }

                if (file_exists("./Public".$data['rightfront'])){
                    // 检测图片文件是否可读
                    if (is_readable("./Public".$data['rightfront'])){
                        $rightfront = $data['rightfront'];
                    } else {
                        $rightfront = '';
                    }
                } else {
                    $rightfront = '';
                }

                if (file_exists("./Public".$data['rightimg'])){
                    // 检测图片文件是否可读
                    if (is_readable("./Public".$data['rightimg'])){
                        $rightimg = $data['rightimg'];
                    } else {
                        $rightimg = '';
                    }
                } else {
                    $rightimg = '';
                }

                if (file_exists("./Public".$data['behindimg'])){

                    // 检测图片文件是否可读
                    if (is_readable("./Public".$data['behindimg'])){
                        $behindimg = $data['behindimg'];
                    } else {
                        $behindimg = '';
                    }
                } else {
                    $behindimg = '';
                }

                //判断图片文件是否存在
                if (file_exists("./Public".$data['cover'])){
//            检测图片文件是否可读
                    if (is_readable("./Public".$data['cover'])){
                        $cover = $data['cover'];
                    } else {
                        $cover = '';
                    }

                } else {
                    $cover = '';
                }
                $list[$key]['cover'] = $cover;
                $list[$key]['frontimg'] = $frontimg;
                $list[$key]['leftanterior'] = $leftanterior;
                $list[$key]['rightfront'] = $rightfront;
                $list[$key]['rightimg'] = $rightimg;
                $list[$key]['behindimg'] = $behindimg;
            }

            if (IS_AJAX) {
                $this->assign($listName, $list);
                $this->assign($showPage, $show);
                $html = $this->fetch($ajaxHtml);
                $this->ajaxReturn($html);
            }
            $this->assign($listName, $list);
            $this->assign($showPage, $show);
        } else {
            $list = $m->query($sql, $ary);
            foreach ($list as $key =>$data){
//判断图片文件是否存在
                if (file_exists("./Public".$data['frontimg'])){
//            检测图片文件是否可读
                    if (is_readable("./Public".$data['frontimg'])){
                        $frontimg = $data['frontimg'];
                    } else {
                        $frontimg = '';
                    }

                } else {
                    $frontimg = '';
                }
                if (file_exists("./Public".$data['leftanterior'])){
//            检测图片文件是否可读
                    if (is_readable("./Public".$data['leftanterior'])){
                        $leftanterior = $data['leftanterior'];
                    } else {
                        $leftanterior = '';
                    }
                } else {
                    $leftanterior = '';
                }

                if (file_exists("./Public".$data['rightfront'])){
                    // 检测图片文件是否可读
                    if (is_readable("./Public".$data['rightfront'])){
                        $rightfront = $data['rightfront'];
                    } else {
                        $rightfront = '';
                    }
                } else {
                    $rightfront = '';
                }

                if (file_exists("./Public".$data['rightimg'])){
                    // 检测图片文件是否可读
                    if (is_readable("./Public".$data['rightimg'])){
                        $rightimg = $data['rightimg'];
                    } else {
                        $rightimg = '';
                    }
                } else {
                    $rightimg = '';
                }

                if (file_exists("./Public".$data['behindimg'])){

                    // 检测图片文件是否可读
                    if (is_readable("./Public".$data['behindimg'])){
                        $behindimg = $data['behindimg'];
                    } else {
                        $behindimg = '';
                    }
                } else {
                    $behindimg = '';
                }

                //判断图片文件是否存在
                if (file_exists("./Public".$data['cover'])){
//            检测图片文件是否可读
                    if (is_readable("./Public".$data['cover'])){
                        $cover = $data['cover'];
                    } else {
                        $cover = '';
                    }

                } else {
                    $cover = '';
                }
                $list[$key]['cover'] = $cover;
                $list[$key]['frontimg'] = $frontimg;
                $list[$key]['leftanterior'] = $leftanterior;
                $list[$key]['rightfront'] = $rightfront;
                $list[$key]['rightimg'] = $rightimg;
                $list[$key]['behindimg'] = $behindimg;
            }
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

//处理送车地址
    public function carADHandle($str){
        $strAry = [];
        array_push($strAry,explode(',',$str));
        return $strAry;
    }


    /**
     * 判断订单号类型：网约车,租车
     */
    public function isOrderCode($str)
    {
        if (preg_match('/[a-zA-Z]/',$str)){
            $callBack = 'carRental';
        } else {
            $callBack = 'aboutCar';
        }

        return $callBack;
    }

}