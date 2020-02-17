<?php
/**
 *日志记录，按照"Ymd.log"生成当天日志文件
 * 日志路径为：入口文件所在目录/logs/$type/当天日期.log.php，例如 /logs/error/20120105.log.php
 * @param string $type 日志类型，对应logs目录下的子文件夹名
 * @param string $content 日志内容
 * @return bool true/false 写入成功则返回true
 */

class Log  {

    function writelog($type="",$content=""){
        if(!$content || !$type){
            return FALSE;
        }
        $dir=getcwd().DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.$type;
        if(!is_dir($dir)){
            if(!mkdir($dir)){
                return false;
            }
        }
        $filename=$dir.DIRECTORY_SEPARATOR.date("Ymd",time()).'.log.php';
        $logs=include $filename;
        if($logs && !is_array($logs)){
            unlink($filename);
            return false;
        }
        $logs[]=array("time"=>date("Y-m-d H:i:s"),"content"=>$content);
        $str="<?php \r\n return ".var_export($logs, true).";";
        if(!$fp=@fopen($filename,"wb")){
            return false;
        }
        if(!fwrite($fp, $str))return false;
        fclose($fp);
        return true;
    }
}
