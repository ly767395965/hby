<?php
namespace Admin\Controller;
use Common\Common\BaseController;
use Admin\Controller\UserController;

//网约车报送控制器

class SubmittedController extends BaseController {

    //报送入口方法
    public function controlSub($name,$id){
        $data['path'] = $name;
        $data['id'] = $id;
        $this->postSub('/test',$data);
    }

    //报送请求入口
    public function postSub($path,$data){
        $url = C('NETCAR_SUBMIT_URL') . $path;
		ignore_user_abort(true); // 忽略客户端断开 
		set_time_limit(0);    // 设置执行不超时
        $list = $this->doRequest($url, $data);
        //$array=json_decode($list,true);
        //return 1;
    }


    /**
     * PHP发送Json对象数据 利用curl
     *
     * @param $url 请求url
     * @param $jsonStr 发送的json字符串
     * @return array
     */
    function http_post_json($url, $jsonStr){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
//                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($httpCode, $response);
    }

    /**
     * PHP发起请求 利用fsockopen
     *
     * @param $url 请求url
     * @param $jsonStr 发送的json字符串
     * @return array
     */
    function doRequest($url, $param=array()){
        $urlinfo = parse_url($url);

        $host = $urlinfo['host'];
        $path = $urlinfo['path'];
        $query = isset($param)? json_encode($param) : '';
        $port = $urlinfo['port'];
        $errno = 0;
        $errstr = '';
        $timeout = 10;

        $fp = fsockopen($host, $port, $errno, $errstr, $timeout);

        $out = "POST ".$path." HTTP/1.1\r\n";
        $out .= "host:".$host."\r\n";
        $out .= "content-length:".strlen($query)."\r\n";
        $out .= "content-type:application/json\r\n";
        $out .= "Accept-charset:UTF-8\r\n";
        //$out .= "Accept-Encoding:gzip\r\n";
        $out .= "Connection:keep-alive\r\n\r\n";
        $out .= $query;

        fputs($fp, $out);
        //fwrite($fp,$out);
        //检索HTTP状态码
        //$data = fgets($fp,128);
        fclose($fp);
        //返回状态码和类信息
        //list($response,$code) = explode(' ',$data);
        //if($code == 200){
        //    return array($code,'good');
        //}else{
        //    return array($code,'bad');//数组第二个元素作为css类名
        //}
    }
}