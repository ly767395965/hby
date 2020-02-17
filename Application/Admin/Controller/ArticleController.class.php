<?php
namespace Admin\Controller;

use Common\Common\BaseController;
use Think\Controller;
use Think\Verify;

/**
 * Class CarController
 * @package Admin\Controller
 *文章管理控制器
 */
class ArticleController extends BaseController
{
    protected static $table = 'new_aticle';
    public function index()
    {
        $key = I('get.key');//接受模糊查询的条件
        $ary = [];//将条件传给数组
//        $startDate = I('get.start');//开始时间
//        $stopDate = I('get.stop');//结束时间
        $selectid = I('get.select');
//        $sql = "SELECT a.id,a.title,a.subtitle,a.author,b.classname,a.addtime FROM new_aticle a LEFT JOIN new_class b ON a.catid=b.id WHERE (a.addtime BETWEEN '%s' AND '%s') AND (a.isdel=0) AND ";
        $sql = "SELECT a.id,a.title,a.subtitle,a.author,b.classname,a.addtime FROM new_aticle a LEFT JOIN new_class b ON a.catid=b.id WHERE (a.isdel=0) AND ";
        $countSql = "SELECT COUNT(ID) FROM new_aticle WHERE (isdel=0) AND ";
        switch ($selectid){
            case 1:
                $sql = $sql . "(a.title LIKE '%%%s%%') ";
                $countSql = $countSql . "title LIKE '%%%s%%'";
                break;
            case 2:
                $sql = $sql . "(a.author LIKE '%%%s%%') ";
                $countSql = $countSql . "author LIKE '%%%s%%'";
                break;
            case 3:
                $sql = $sql . "(b.classname LIKE '%%%s%%') ";
                $countSql = "SELECT COUNT(a.id) FROM new_aticle a  LEFT JOIN new_class b ON a.catid=b.id WHERE (a.isdel=0) AND b.classname LIKE '%%%s%%'";
                break;
            default:
                $sql = "SELECT a.id,a.title,a.subtitle,a.author,b.classname,a.addtime FROM new_aticle a LEFT JOIN new_class b ON a.catid=b.id WHERE  a.isdel=0  ORDER BY a.ID DESC";
                $countSql = "SELECT COUNT(ID) FROM new_aticle WHERE isdel=0 ORDER BY id DESC";
        }
        //判断 $selectid 不为空时 追加id排序
        if ($selectid != null) {
            $sql = $sql . "ORDER BY a.id DESC";
            $ary = [$key];
            if ($selectid==3) {
                $this->pageDisplay($sql, $countSql, 16, $ary, 'count(a.id)', 'list', 'page', true);
            } else {
                $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
            }
        } else {
            $ary = [];
            $this->pageDisplay($sql, $countSql, 16, $ary, 'count(id)', 'list', 'page', true);
        }
        $this->display();


    }


    //文章添加方法 addArticle（）
    public function addArticle()
    {
        //查询文章分类表
        if (empty($_POST)) {
            $newclass = M('new_class');
            $data = $newclass->where('isdel=0')->select();
            $this->assign('list', $data);
            $this->display();
        }
        if ($_POST) {
            $addaticle = M(self::$table);
            // 实例化上传类
            $upload = new \Think\Upload();
            //限制图片大小为100字节
            $upload->maxSize = 102400;
            // 设置附件上传类型
            $upload->exts = array('jpg', 'png', 'jpeg');
            // 设置附件上传目录
            $upload->savePath = '/Uploads/AticleCover/';
            //设置子目录
            $upload->autoSub = true;
            //同名则替换
            $upload->uploadReplace = true;
            $info = $upload->upload();
            // 上传错误提示错误信息
//            if (!$info) {
//                $this->error($upload->getError());
//            }
            //验证页面表单内容
            $data1 = array(
                array('title', 'require', '<script>alert("文章标题不能为空");history.back(-1);</script>', 1),
                array('catid', 'require', '<script>alert("文章分类不能为空");history.back(-1);</script>', 1),
                array('content', 'require', '<script>alert("文章内容不能为空");history.back(-1);</script>', 1),
                array('source', 'require', '<script>alert("文章来源不能为空");history.back(-1);</script>', 1),
            );
            if (!$addaticle->validate($data1)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($addaticle->getError());
            } else {

                //判断文章来源
                $source = "华邦出行";
                if ($_POST['source'] == 1) {
                    $source = '原创:' . $source;
                } else {
                    $source = '转载自:' . $_POST['text'];
                }
                //获取保存路径
                $savepath = $info[0]['savepath'];
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                //定义数组来接收页面的值并用I（）方法过滤
                //处理文章内容图片
                $str = $_POST['content'];
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

                $content = array();
                $content['title'] = I('post.title');
                $content['subtitle'] = I('post.subtitle');
                $content['content'] = $_POST['content'];
                $content['imgs'] = $aryToStr;
                $content['author'] = $auserInfo['name'];
                $content['catid'] = I('post.catid');
                $content['source'] = $source;
                $content['describes'] = I('post.describes');
                $content['cover'] = $savepath . $info[0]['savename'];
                $content['addtime'] = Date('Y-m-d h:i:s');
                //记录操作日志
                if ($addaticle->add($content)) {
                    //获取添加成功返回的数据id
                    $returnid = M(self::$table)->order('id desc')->find();
                    $log = self::writeLog(self::$table, $returnid['id'], 'add', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        $this->success("添加成功!", U('Article/Index',1));
                    }
                } else {
                    $this->error("添加失败",'',1);
                }
            }
        }


    }

    //文章修改方法 editArticle()
    public function editArticle()
    {
        //查询文章列表
        $selAticle = M(self::$table);
        //全局变量
        $isUp = false;
        //文件路径
        $path = "";
        //判断操作数据的id的提交方式并接收
        if (empty($_POST)) {
            $id = I('get.id');
            cookie('id', $id);
            $cid = cookie('id');
            $sql = "SELECT a.title,a.subtitle,a.content,a.imgs,a.source,a.cover,a.describes,b.classname,b.id as bid  FROM ".self::$table. " a LEFT JOIN new_class b ON a.catid=b.id WHERE (a.isdel=0) AND (a.id=%d)";
            $arr = [$cid];
            $list = $selAticle->query($sql,$arr);
            cookie('imgs',$list[0]['imgs']);
            cookie('imgname', $list[0]['cover']);
            //查询文章分类表
            $newclass = M('new_class');
            $data1 = $newclass->where('isdel=0')->select();

            //截取文章来源,strpos()查找‘：’的位置;substr()截取‘：’之后的字符串;substr()截取‘：’之前的字符串。
            $i = strpos($list[0]['source'], ':');
            $str = substr($list[0]['source'], $i + 1);
            $str1 = substr($list[0]['source'], 0, $i);
            $this->assign('str', $str);
            $this->assign('str1', $str1);

            $this->assign('list', $data1);
            $this->assign('aticle', $list);
            $this->display();
        }
        if ($_POST) {
            $cid = cookie('id');
            $data1 = array(
                array('title', 'require', '<script>alert("文章标题不能为空");history.back(-1);</script>', 1),
                array('catid', 'require', '<script>alert("文章分类不能为空");history.back(-1);</script>', 1),
                array('content', 'require', '<script>alert("文章内容不能为空");history.back(-1);</script>', 1),
                array('source', 'require', '<script>alert("文章来源不能为空");history.back(-1);</script>', 1),
            );
            // 实例化上传类
            $upload = new \Think\Upload();
            //限制图片大小为100字节
            $upload->maxSize = 102400;
            // 设置附件上传类型
            $upload->exts = array('jpg', 'png', 'jpeg');
            // 设置附件上传目录
            $upload->savePath = '/Uploads/AticleCover/';
            //设置子目录
            $upload->autoSub = true;
            //同名则替换
            $upload->uploadReplace = true;
            //获取上传的文件
            $info = $upload->upload();
            // 上传错误提示错误信息
            if ($info) {
                //通过img下标，得到上传图片字段，通过该字段读取原数据字段的文件路径并删除
                $selimgurl = "SELECT cover FROM new_aticle WHERE id=%d";
                $arr = [$cid];
                $path = $selAticle->query($selimgurl, $arr);
                $isUp = true;
            } else {
                $isUp = false;
            }
            if (!$selAticle->validate($data1)->create()) {
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                exit($selAticle->getError());
            } else {
                //判断文章来源
                $source = "华邦出行";
                if ($_POST['source'] == 1) {
                    $source = '原创:' . $source;
                } else {
                    $source = '转载自:' . $_POST['text'];
                }
                //获取保存路径
                $savepath = $info[0]['savepath'];
                //接收当前管理员登陆名
                $auserInfo = UserInfo();
                //定义数组来接收页面的值并用I（）方法过滤

                //处理文章内容图片
                $str = $_POST['content'];
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

                $content = array();
                $content['title'] = I('post.title');
                $content['subtitle'] = I('post.subtitle');
                $content['content'] = $_POST['content'];
                $content['imgs'] = $aryToStr;
                $content['author'] = $auserInfo['name'];
                $content['catid'] = I('post.catid');
                $content['source'] = $source;
                $content['describes'] = I('post.describes');
                if ($isUp) {
                    unlink('Public/'.$path[0]['cover']);//删除原图片
                    $content['cover'] = $savepath . $info[0]['savename'];
                } else {
                    $content['imgurl'] = cookie('imgname');
                }
                //记录操作日志
                if ($selAticle->where(array('id=' . $cid))->save($content)) {
                    $log = self::writeLog(self::$table, $cid, 'edit', Date('Y-m-d H:i:sA'), $auserInfo['name']);
                    if ($log) {
                        cookie('id', null);
                        $this->success("修改成功!", U('Article/Index',1));
                    }
                } else {
                    $this->error("修改失败",'',1);
                }
            }
        }

    }


    //删除文章的方法 del（）
    public function del()
    {
        $deldate = M(self::$table);
        $id = I('get.id');//获取页面数据id
        $date['isdel'] = 1;
        $date['cover'] = "";
        $article = "SELECT cover,imgs FROM ". self::$table ." WHERE (id=%d)";
        $arr = $id;
        $coverimg = $deldate->query($article,$arr);

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
        unlink('Public/'.$coverimg[0]['cover']);
        //记录操作日志
        if ($deldate->where(array('id=' . $id))->save($date)) {
            $auserInfo = UserInfo();//接收当前管理员登陆名
            $log = self::writeLog(self::$table, $id, 'del', Date('Y-m-d H:i:sA'), $auserInfo['name']);
            if ($log) {
                cookie('id', null);
                $this->success("删除成功!", U('Article/Index',1));
            }
        } else {
            $this->error("删除失败!",'',1);
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
            $data['cover'] = "";
            $arr1 = explode(",",$ids);
            foreach ($arr1 as $key){
                $article = "SELECT cover,imgs FROM ". self::$table ." WHERE (id=%d)";
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
                //循环删除图片
                unlink('Public/'.$coverimg[0]['cover']);
            }

            //执行数据逻辑批量删除
            $res = $m->where($where)->save($data);
            if ($res) {
                $ids = explode(',', $ids);
                foreach ($ids as $id) {
                    self::writeLog(self::$table, $id, 'delAll', date('Y-m-d H:i:sA'), self::cookieName());
                }
                $this->ajaxReturn(array('state' => 1));
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


