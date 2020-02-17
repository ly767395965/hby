<?php
/**
+----------------------------------------------------------
 * 获取登录用户信息
+----------------------------------------------------------
+
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */

//封装登录传过来的Cookie
function UserInfo($field='') {
	$userLoginInfo=cookie('auserInfo');
    $userLoginInfo = aryAuthcode($userLoginInfo,'DECODE','hbykj');//解密数组

	if($userLoginInfo){
		return $userLoginInfo;
	}else{
		return false;
	}
}


function checkAuth($authName){
	$auserInfo = UserInfo();
	//超级管理员判断
	if($auserInfo['type'] == 1){
		return true;
	}
	//设置Index/index为公共访问的方法
	$noAuth = array(
		'Index/Index',
	);
	if(in_array($authName,$noAuth)){
		return true;
	}
    $sql = "SELECT * FROM auth_group_access a LEFT JOIN auth_group b ON a.group_id = b.id WHERE (a.uid = {$auserInfo['id']})";
    $authModel = M()->query($sql);
    $authModel = $authModel[0]['rules'];
//    echo "<pre>";
//    var_dump($authModel)

//	//获取用户组明细表
//	$rule = D('auth_group_access')->where(array('id'=>$auserInfo['id']))->getField('group_id');
//	//根据用户组明细表获取  部门权限表
//	$authModel = D('AuthGroup')->where(array('id'=>$rule))->getField('rules');
//
//    $rule = M('auth_group_access')->
	//获取到的数据   修改成字符串   用，分割
	$pieces = explode(",", $authModel);
	//用IN 查询节点值
	$groupModel=D('auth_rule')->field('name')->where(array('id'=>array('IN',$pieces)))->select();
	foreach ($groupModel as $k=>$v){
		$ruleArray[] = $v['name'];
	}
	//判断是否存在素组
	if($ruleArray){
		if(in_array($authName,$ruleArray)){
			return true;
		}
	}

}


function delfile($file) {
	
	unlink($file);
}


//验证码判断
function check_code($code, $id = ""){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 图片上传方法
 *
 * @param array $files 文件上传信息
 * @param array $info_ary 上传文件的配置信息[savePath=>存储路径,maxSize=>文件大小,exts=>上传类型,autoSub=>是否设置子目录,uploadReplace=>是否同名替换,class=>上传至部级平台的文件夹名称]
 * @param string/array 需要删除的旧的文件路径(数据库中存储的路径)
 * @param bool $ftp_on 是否需要将此文件同时上传到部级平台
 * @return int
 *              0：删除失败
 *              1:路径为空
 *              2：删除成功
 *              3：文件不存在
 */
function photoUpdate($files, $info_ary,$old_url='', $ftp_on=2){
    if ($ftp_on == 2){
        $ftp_on = C('NETCAR_FTP_ON');
    }
    if ($info_ary['class'] != ''){
        $class = $info_ary['class'];
    }else{
        $class = 'tmp';
    }

    if ($info_ary['savePath'] == '' || $files == ''){
        $res['err'] = 1;
    }else{
        $info_ary['maxSize'] = $info_ary['maxSize']=='' ? 5120000 : $info_ary['maxSize'];//判断是否设置了限制大小,如果未限制则启用默认最大限制500KB;
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
        // 上传文件
        $info = $upload->upload($files);
        // 上传错误提示错误信息

        if ($info){
            foreach ($info as $key => $val){    //对上传成功的文件进行处理,及上传到ftp服务器
                $raw_url = $val['savepath'].$val['savename'];//拼接本地存储路径
                $res[$key] = $raw_url.',wycftp142/'.$class.'/'.$val['savename'];
                if ($ftp_on){
                    photoSub($raw_url,'add',$class);
                }
            }

            if (!$res['err']){
                $res['err'] = 0;
            }

            if ($old_url != ''){    //删除旧文件
                $ck = is_array($old_url);
                if (!$ck){
                    deleteFile($old_url);
                    if ($ftp_on) {
                        photoSub($old_url, 'delete', $class);
                    }
                }else{
                    foreach ($old_url as $key => $val){
                        deleteFile($val['url']);
                        if ($ftp_on) {
                            photoSub($val, 'delete', $class);
                        }
                    }
                }
            }


        }else{
            $res['err'] = 2;
        }
    }
    return $res;
}

/**
 * 网约车上传文件至部级平台的ftp
 *
 * @param string $url 文件上传时保存的路径
 * @return int
 *              0：服务器连接失败
 *              1：服务器登录失败
 *              2：创建目录失败
 *              3：上传文件失败
 *              4：上传成功
 */
function ftpHandle($url){
    vendor('FtpHandle.Ftp');
    $ftp_handle = new \Ftp();
    $local = './Public'.$url;
    $name = basename($url);//basename获取路径中的文件名
    $name = explode('.',$name);
    $remote = C('NETCAR_FTP_REMOTE').$name[0].'.jpg';
    $res = $ftp_handle->upload($local,$remote,'netcar');
    return $res;
}

/**
 * 删除网约车部级平台上的旧文件
 *
 * @param string $url 数据库中本件保存的路径
 * @return int
 *              0：服务器连接失败
 *              1：服务器登录失败
 *              2：删除失败
 *              3：删除成功
 */
function ftpDel($url){
    vendor('FtpHandle.Ftp');
    $ftp_handle = new \Ftp();
    $name = basename($url);//basename获取路径中的文件名
    $name = explode('.',$name);
    $remote = C('NETCAR_FTP_REMOTE').$name[0].'.jpg';
    $res = $ftp_handle->delete($remote,'netcar');
    return $res;
}

/**
 * 上传新图时删除原图
 *
 * @param string $url 文件上传时保存的路径
 * @return int
 *              0：删除失败
 *              1:路径为空
 *              2：删除成功
 *              3：文件不存在
 */
function deleteFile($url){
    if ($url){
        $file = './Public/'.$url;
        if (is_readable($file)){
            $result = @unlink ($file);
            $res = $result ? 2 : 0;
        }else{
            $res = 3;
        }
    }else{
        $res = 1;
    }
    return $res;
}

/**
 *将需上传至部级平台的图片传给报送服务器
 */
function photoSub($path,$act,$class,$old_path=''){
    if (!empty($path) && !empty($act)){
        if ($old_path){
            $content['old_path'] = $old_path;
        }
        $content['path'] = $path;
        $content['act'] = $act;
        $content['class'] = $class;

        $post_url = C('NETCAR_LAN_URL');
        $res = sock_post($post_url,$content);//发起请求
    }else{
        $res = 0;
    }

    if ($res != 200){
        $time = date('Y-m-d H:i:s',time());
        M()->execute("INSERT INTO `sub_photo_error` (`path`,act,addtime) VALUES ('{$path}','{$act}','{$time}')");
        if ($old_path){
            M()->execute("INSERT INTO `sub_photo_error` (`path`,act,addtime) VALUES ('{$old_path}','delete','{$time}')");
        }
    }

}

//发起post请求
function sock_post($url, $query){
	ignore_user_abort(true); // 忽略客户端断开 
	set_time_limit(0);    // 设置执行不超时
    $query = http_build_query($query);
    $info = parse_url($url);
    $fp = fsockopen($info["host"], 80, $errno, $errstr, 0.1);
    $head = "POST ".$info['path']."?".$info["query"]." HTTP/1.0\r\n";
    $head .= "Host: ".$info['host']."\r\n";
    $head .= "Referer: http://".$info['host'].$info['path']."\r\n";
    $head .= "Content-type: application/x-www-form-urlencoded\r\n";
    $head .= "Content-Length: ".strlen(trim($query))."\r\n";
    $head .= "\r\n";
    $head .= trim($query);
    fputs($fp, $head);
//    $data = fgets($fp,128);
//    list($response,$code) = explode(' ',$data);
    $code = 200;
    fclose($fp);

    return $code;


}





