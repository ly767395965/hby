<?php
namespace Admin\Controller;

use Common\Common\BaseController;

/**
 * Class PublishEventController
 * @package Admin\Controller
 * 活动管理控制器
 */
class PublishEventController extends BaseController
{
    //对应模型表名
    protected static $table = 'publish_event';

    /*活动首页*/
    public function index()
    {
        $sql = "SELECT id,theme,create_user,content,start_time FROM ".self::$table." WHERE is_del=%d  ORDER BY ID DESC";
        //查询SQL语句，因为是字符串，可以进行.连接，注意空格
        $countSql = "SELECT COUNT(id) FROM ".self::$table." WHERE is_del=%d";
        //参数数组，按顺序传递你要传递的参数值
        $ary = array(0);
        //显示分页，最后一个true为分页开关，false则不显示分页，只显示数据
        $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        $this->display();
    }

    /*增加活动页面、内容*/
    public function addPublishEvent()
    {
        $m = M(self::$table);
        if (!empty($_POST)) {
            // 实例化上传类
            $upload = new \Think\Upload();
            //限制图片大小为100字节
            $upload->maxSize = 307200;
            // 设置附件上传类型
            $upload->exts = array('jpg', 'png', 'jpeg');
            // 设置附件上传目录
            $upload->savePath = '/Uploads/Activity/';
            //设置子目录
            $upload->autoSub = true;
            //同名则替换
            $upload->uploadReplace = true;
            $info = $upload->upload();
            $rules = array(
                array('theme', 'require', '<script>alert("活动主题不能为空！");history.back(-1);</script>', 0),
                array('content', 'require', '<script>alert("活动内容不能为空！");history.back(-1);</script>', 0),
                array('start_time', 'require', '<script>alert("活动开始时间不能为空！");history.back(-1);</script>', 0),
                array('end_time', 'require', '<script>alert("活动结束时间不能为空！");history.back(-1);</script>', 0),
            );

            if (!$m->validate($rules)->create()) {
                exit($m->getError());
            } else {
                //处理文章内容图片
                $str = self::filterStr($_POST['content']);
                //建立空数组存储图片路径
                $strAry = [];
                //循环条件，默认为true，可以进行循环
                $is_true = true;
                //查找图片标签开始字符串
                $s = '<img';
                $s1 = 'src="';
                //查找图片标签结束字符串
                $e = '"';
                //默认开始和结束的位置int型
                $snum = 0;
                $enum = 0;
                //循环遍历html中的img标签,用循环再判断的方式
                do {
                    //查找img开始位置，要加上img标记自身的长度
                    $snum = strpos($str,$s)+strlen($s);
                    //从找到img字符串位置向后截取所有字符，再从该字符中查找结束位置，即第一个双引号出现的位置
                    $str = $this->str_substr($snum,strlen($str),$str);
                    //查找img开始位置，要加上img标记自身的长度
                    $snum = strpos($str,$s1)+strlen($s1);
                    //从找到img字符串位置向后截取所有字符，再从该字符中查找结束位置，即第一个双引号出现的位置
                    $str = $this->str_substr($snum,strlen($str),$str);

                    //查找结束位置
                    $enum = strpos($str,$e);
                    //从0位置开始取，取字符长度为结束字符出现的位置，并添加到数组中
                    array_push($strAry,$this->str_substr(0,$enum,$str));
                    //从结束位置开始向后取所有字符，继续循环遍历下一个img标签
                    $str = $this->str_substr($enum,strlen($str),$str);
                    //判断是否在剩余字符串中找到img标签,找到为true
                    $is_true = strpos($str,$s);

                    //如果在剩余字符中找到img标签则继续遍历，反之停止遍历
                } while ($is_true != false);

                $aryToStr = implode(',',$strAry);

                for ($i=0;$i<=4;$i++) {
                    if ($info[$i]) {
                        switch ($i) {
                            case 0 :
                                $data['cover'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 1 :
                                $data['splashimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                        }
                    }

                }
                $data['theme'] = I('post.theme');
                $data['describetxt'] = I('post.describetxt');
                $data['create_time'] = date('Y-m-d H:i:s', time());
                $data['content'] = self::filterStr($_POST['content']);
                $data['imgs'] = $aryToStr;
                $data['end_time'] = I('post.end_time');
                $data['create_user'] = self::cookieName();
                $res = $m->add($data);
                if ($res) {
                    /*记录操作日志*/
                    //获取添加成功返回的数据id
                    $returnId = $m->order('id desc')->find();
                    $log = self::writeLog(self::$table, $returnId['id'], 'add', date('Y-m-d H:i:sA'), self::cookieName());
                    if ($log) {
                        $this->success('添加成功', U('PublishEvent/Index'), 1);
                    } else {
                        $this->error('添加失败', '', 1);
                    }
                }
            }
        } else {
            $this->display();
        }
    }

    /*修改活动*/
    public function editPublishEvent()
    {
        $m = M(self::$table);
        $cid = '';
        //全局变量
        $isUp = false;
        //文件路径
        $path = "";
        if (!empty($_GET)) {
            $where['id'] = I('get.id');
            cookie('id', I('get.id'));
            $cid = cookie('id');
            $res = $m->where($where)->field('id,theme,describetxt,cover,content,splashimg,create_time,start_time,end_time,imgs')->find();
            $this->assign('res', $res);
            cookie('imgname',$res['cover']) ;
            cookie('imgs',$res['imgs']) ;
        }
        if (!empty($_POST)) {
            $cid = cookie('id');
            $rules = array(
                array('theme', 'require', '<script>alert("活动主题不能为空！");history.back(-1);</script>', 0),
                array('content', 'require', '<script>alert("活动内容不能为空！");history.back(-1);</script>', 0),
                array('start_time', 'require', '<script>alert("活动开始时间不能为空！");history.back(-1);</script>', 0),
                array('end_time', 'require', '<script>alert("活动结束时间不能为空！");history.back(-1);</script>', 0),
            );
            // 实例化上传类
            $upload = new \Think\Upload();
            //限制图片大小为100字节
            $upload->maxSize = 307200;
            // 设置附件上传类型
            $upload->exts = array('jpg', 'png', 'jpeg');
            // 设置附件上传目录
            $upload->savePath = '/Uploads/Activity/';
            //设置子目录
            $upload->autoSub = true;
            //同名则替换
            $upload->uploadReplace = true;
            //获取上传的文件
            $info = $upload->upload();
            // 上传错误提示错误信息
            if ($info) {
                //通过img下标，得到上传图片字段，通过该字段读取原数据字段的文件路径并删除
                $selimgurl = "SELECT cover,splashimg FROM ".self::$table." WHERE id=%d";
                $arr = [$cid];
                $path = $m->query($selimgurl, $arr);
                $isUp = true;
            } else {
                $isUp = false;
            }
            if (!$m->validate($rules)->create()) {
                exit($m->getError());
            } else {
                //处理文章内容图片
                $str = self::filterStr($_POST['content']);
                //建立空数组存储图片路径
                $strAry = [];
                //循环条件，默认为true，可以进行循环
                $is_true = true;
                //查找图片标签开始字符串
                $s = '<img';
                $s1 = '<src="';
                //查找图片标签结束字符串
                $e = '"';
                //默认开始和结束的位置int型
                $snum = 0;
                $enum = 0;
                //循环遍历html中的img标签,用循环再判断的方式
                do {
                    //查找img开始位置，要加上img标记自身的长度
                    $snum = strpos($str,$s)+strlen($s);
                    //从找到img字符串位置向后截取所有字符，再从该字符中查找结束位置，即第一个双引号出现的位置
                    $str = $this->str_substr($snum,strlen($str),$str);
                    //查找img开始位置，要加上img标记自身的长度
                    $snum = strpos($str,$s1)+strlen($s1);
                    //从找到img字符串位置向后截取所有字符，再从该字符中查找结束位置，即第一个双引号出现的位置
                    $str = $this->str_substr($snum,strlen($str),$str);
                    //查找结束位置
                    $enum = strpos($str,$e);
                    //从0位置开始取，取字符长度为结束字符出现的位置，并添加到数组中
                    array_push($strAry,$this->str_substr(0,$enum,$str));
                    //从结束位置开始向后取所有字符，继续循环遍历下一个img标签
                    $str = $this->str_substr($enum,strlen($str),$str);
                    //判断是否在剩余字符串中找到img标签,找到为true
                    $is_true = strpos($str,$s);

                    //如果在剩余字符中找到img标签则继续遍历，反之停止遍历
                } while ($is_true != false);

                //把字符串转数组
                $old = explode(',',cookie('imgs'));

                //遍历从表中取出的imgs进行循环
                for ($i=0;$i<count($old);$i++) {
                    //判断数据库中的图片是否存在于现有文章中，如果存在则不处理，如果不存在表示当前文章删除或替换该图片
                    if (in_array($old[$i],$strAry)) {

                    } else {
                        //表中没有该图片，删除图片
                        $url = './'.$old[$i];
                        unlink($url);
                    }
                }

                $aryToStr = implode(',',$strAry);

                $data['theme'] = I('post.theme');
                $data['content'] = self::filterStr($_POST['content']);
                $data['imgs'] = $aryToStr;
                $data['describetxt'] = I('post.describetxt');
                $data['start_time'] = I('post.start_time');
                $data['end_time'] = I('post.end_time');
                $data['create_user'] = self::cookieName();

                for ($i=0;$i<=4;$i++) {
                    if (!empty($info[$i])) {
                        switch ($i) {
                            case 0 :
                                $data['cover'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                            case 1 :
                                $data['splashimg'] = $info[$i]['savepath'].$info[$i]['savename'];
                                break;
                        }
                    }
                }

                //通过img数组，得到上传图片字段，通过该字段读取原数据字段的文件路径并删除
                foreach ($data as $key => $value) {
                    //遍历删除原有的文件
                    unlink('Public/'.$path[0][$key]);
                }
                $where['id'] = I('post.id');
                $res = $m->where($where)->save($data);
                if ($res) {
                    /*记录操作日志*/
                    $log = self::writeLog(self::$table, I('post.id'), 'edit', date('Y-m-d H:i:sA'), self::cookieName());
                    if ($log) {
                        $this->success('修改成功', U('PublishEvent/Index'));
                    } else {
                        $this->error('修改失败');
                    }
                }
            }
        } else {
            $this->display();
        }
    }

    /*删除活动*/
    public function delPublishEvent()
    {
        if (!empty($_GET)) {
            $m = M(self::$table);
            $id = I('get.id');
            $arr = [];
            $arr['is_del'] = 1;
//            $date['splashimg'] = "";
//            $date['cover'] = "";

            $article = "SELECT cover,imgs,splashimg FROM ". self::$table ." WHERE (id=%d)";
            $arrs = $id;
            $coverimg = $m->query($article,$arrs);

            //遍历删除文章内容图片
            $fileStr = $coverimg[0]['imgs'];
            //把imgs图片路径字符串转为数组进行遍历
            $fileStr = explode(',',$fileStr);
            //遍历图片路径数组
            for ($j=0;$j<count($fileStr);$j++) {
                //组装成图片路径删除
                $url = './'.$fileStr[$j];
                unlink($url);
            }
            //删除原图片
            $arr = $this->delPic($coverimg[0],$arr);
            //逻辑删除数据
            $result = $m->where(array('id='.$id))->save($arr);
            //记录操作日志
//            $res = $m->where($where)->save($data);
            if ($result) {
                /*记录操作日志*/
                $log = self::writeLog(self::$table, I('get.id'), 'del', date('Y-m-d H:i:sA'), self::cookieName());
                if ($log) {
                    $this->success('删除成功', U('PublishEvent/Index'));
                } else {
                    $this->error('删除失败');
                }
            }
        }
    }

    /*删除图片;返回需要清空的数据数组
  @ary      删除图片路径数组
  @dataArr  是清空的数据库字段数组
*/
    public function delPic($ary,$dataArr)
    {
        foreach ($ary as $key => $val) {
            if (!empty($val)) {
                unlink('Public/'.$val);
                $dataArr[$key] = '';
            }
        }
//        $dataArr['isdel'] = 1;
        return $dataArr;
    }

    /*批量删除*/
    public function delAll()
    {
        if (!empty($_POST)) {
            $ids = I('post.ids');
            $m = M(self::$table);
            $where['id'] = array('IN', $ids);
            $data = [];
            $data['is_del'] = 1;
            $arr1 = explode(",",$ids);
            foreach ($arr1 as $key){
                $article = "SELECT cover,imgs,splashimg FROM ". self::$table ." WHERE (id=%d)";
                $arr = $key;
                $coverimg = $m->query($article,$arr);

                //遍历删除文章内容图片
                $fileStr = $coverimg[0]['imgs'];
                //把imgs图片路径字符串转为数组进行遍历
                $fileStr = explode(',',$fileStr);
                //遍历图片路径数组
                for ($j=0;$j<count($fileStr);$j++) {
                    //组装成图片路径删除
                    $url = './'.$fileStr[$j];
                    unlink($url);
                }
//                //循环删除图片
                $data = $this->delPic($coverimg[0],$data);
            }
            //逻辑删除数据
            $result = $m->where($where)->save($data);
            if ($result) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
            }
        }
    }

    /*异步获取活动内容*/
    public function getContent()
    {
        if (!empty($_POST)) {
            $m = M(self::$table);
            $id = I('post.id');
            $where['id'] = $id;
            $data = $m->where($where)->field('content')->find();
            if ($data) {
                $this->ajaxReturn(array(
                    'state' => 1,
                    'data' => $data
                ));
            }
        }
    }

    /**字符串截取函数
     * @param $snum  字符串开始出现位置
     * @param $enum  截取的字符串长度
     * @param $str   待截取的字符串
     * @return string 返回截取后的字符串
     */
    function str_substr($snum,$enum,$str) // 字符串截取函数
    {
        $minNum = $snum;
        $maxNum = $enum;
        $str = substr($str, $minNum,$maxNum);
        return $str;
    }

}