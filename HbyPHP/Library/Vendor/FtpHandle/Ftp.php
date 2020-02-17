<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/26
 * Time: 13:31
 */
    /**
     * ftp上传文件类
     */
    class Ftp {

        /**
         * 测试服务器
         *
         * @var array
         */
        private $testServer = array(
            'host' => '202.98.195.234',
            'port' => 21,
            'user' => 'hby',
            'pwd' => '123'
        );

        /**
         * 打开并登录服务器
         *
         * @param string $flag 服务器标识test
         * @return mixed
         *              0：服务器连接失败
         *              1：服务器登录失败
         *              resource 连接标识
         */
        public function openServer($flag = 'netcar'){
            //选择服务器
            $config = $this->getServerConfig($flag);

            //连接服务器
            $connect = ftp_connect($config['host'], $config['port']);
            if($connect == false) return 0;

            //登录服务器
            if(!ftp_login($connect, $config['user'], $config['pwd'])) return 1;

            //打开被动模式，数据的传送由客户机启动，而不是由服务器开始(打开此模式后，无法上传文件)
            ftp_pasv($connect, false);
            //返回连接标识
            return $connect;
        }

        /**
         * 创建目录并将目录定位到当请目录
         *
         * @param resource $connect 连接标识
         * @param string $dirPath 目录路径
         * @return mixed
         *              2：创建目录失败
         *              true：创建目录成功
         */
        public function makeDir($connect, $dirPath){
            //处理目录
            $dirPath = '/' . trim($dirPath, '/');
            $dirPath = explode('/', $dirPath);
            foreach ($dirPath as $dir){
                if($dir == '') $dir = '/';
                //判断目录是否存在
                if(@ftp_chdir($connect, $dir) == false){
                    //判断目录是否创建成功
                    if(@ftp_mkDir($connect, $dir) == false){
                        return 2;
                    }
                    @ftp_chdir($connect, $dir);
                }
            }
            return true;
        }

        /**
         * 关闭服务器
         *
         * @param resource $connect 连接标识
         */
        public function closeServer($connect){
            if(!empty($connect)) ftp_close($connect);
        }

        /**
         * 上传文件
         *
         * @param string $flag 服务器标识
         * @param string $local 上传文件的本地路径
         * @param string $remote 上传文件的远程路径
         * @return int
         *              0：服务器连接失败
         *              1：服务器登录失败
         *              2：创建目录失败
         *              3：上传文件失败
         *              4：上传成功
         */
        public function upload($local, $remote, $flag = 'netcar'){
            //连接并登录服务器
            $connect = $this->openServer($flag);
            if(($connect === 0) || ($connect === 1)) return $connect;

            //上传文件目录处理
            $mdr = $this->makeDir($connect, dirname($remote));
            if($mdr === 2) return 2;

            //上传文件
            $result = ftp_put($connect,  basename($remote), $local, FTP_BINARY);

            //关闭服务器
            $this->closeServer($connect);

            //返回结果
            return (!$result) ? 3 : 4;
        }

        /**
         * 删除文件
         *
         * @param string $flag 服务器标识
         * @param string $remote 文件的远程路径
         * @return int
         *              0：服务器连接失败
         *              1：服务器登录失败
         *              2：删除失败
         *              3：删除成功
         */
        public function delete($remote, $flag = 'netcar'){
            //连接并登录服务器
            $connect = $this->openServer($flag);
            if(($connect === 0) || ($connect === 1)) return $connect;

            //删除
            $result = ftp_delete($connect, $remote);

            //关闭服务器
            $this->closeServer($connect);

            //返回结果
            return (!$result) ? 2 : 3;
        }

        /**
         * 读取文件
         *
         * @param string $flag 服务器标识
         * @param string $remote 文件的远程路径
         * @return mixed
         *              0：服务器连接失败
         *              1：服务器登录失败
         */
        public function read($remote, $flag='netcar'){
            //连接并登录服务器
            $connect = $this->openServer($flag);
            if(($connect === 0) || ($connect === 1)) return $connect;

            //读取
            $result = ftp_nlist($connect, $remote);

            //关闭服务器
            $this->closeServer($connect);

            //返回结果
            foreach ($result as $key => $value){
                if(in_array($value, array('.', '..'))) unset($result[$key]);
            }
            return array_values($result);
        }

        /**
         * 获取ftp服务器配置
         *
         * @param string $flag 服务器标识netcar
         * @return array ftp服务器连接配置
         */
        private function getServerConfig($flag = 'netcar'){
            $flag = strtolower($flag);
            //测试服务器
//            if($flag == 'netcar') return $this->testServer;
            switch ($flag){
                case 'netcar':
                    $conf['host'] = C('NETCAR_FTP_URL');    //在配置文件中那配置信息
                    $conf['port'] = C('NETCAR_FTP_PORT');
                    $conf['user'] = C('NETCAR_FTP_USER');
                    $conf['pwd'] = C('NETCAR_FTP_PWD');
                    break;
                default:
                    $conf = $this->testServer;
            }

            return $conf;//返回配置信息
        }
    }

/*
$conn = ftp_connect("202.98.195.234",21) or die("Could not connect");

ftp_login($conn,"hby","123");

echo ftp_put($conn,"target.txt","test.txt",FTP_ASCII);

ftp_close($conn);


//$conn = ftp_connect("202.98.195.234") or die("Could not connect");
//ftp_login($conn,"hby","123");

//var_dump(ftp_nlist($conn,"hby"));
//ftp_get($conn, "longyu.txt", "hby/test6.txt", FTP_BINARY);
//
//ftp_close($conn);

exit;

    $test_ftp = new Ftp();
//    $res = $test_ftp->openServer();
//read($flag, $remote)
    $msg = $test_ftp->read('test','test6.txt');
//upload($flag = 'test', $local, $remote){
//    $msg = $test_ftp->upload('test','test.txt','ftp_test.txt');
//    $msg = $test_ftp->delete('test','test.txt');
echo $msg;
//echo $res;
//var_dump($res);
//echo "666";

//Resource id #2
//Resource id #2
*/
