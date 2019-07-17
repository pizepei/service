<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/4/6 15:53
 * @title 文件上传类
 */
namespace pizepei\service\filesupload;

use pizepei\encryption\aes\Prpcrypt;
use pizepei\encryption\SHA1;
use pizepei\func\Func;
use pizepei\helper\Helper;

class FilesUpload
{
    /**
     * app 配置 app配置只是签名好过滤域名（真正过滤域名是文件服务器控制）
     * @var array
     */
    protected $config = [];
    /**
     *模式配置（域名、好文件大小是是文件服务器控制）
     * @var array
     */
    protected $configCsema = [];

    public function __construct($config,$configCsema=[])
    {
        $this->config = $config;
        $this->configCsema = $configCsema;
    }

    /**
     * @Author pizepei
     * @Created 2019/4/6 15:58
     * @param array $Request
     * @title  获取文件上传签名
     * @explain 注意签名是有有效期的，签名上传的文件是会根据域名在限制的
     */
    public function signature(array $Request,\pizepei\staging\Controller $Controller)
    {

        $referer = $_SERVER['HTTP_REFERER']??'*';
        if(!empty($this->config['domain'])){
            $i = 0;
            foreach($this->config['request_domain'] as $valueDomain)
            {
                preg_match($valueDomain,$referer, $result);
                /**
                 *匹配
                 */
                if(!empty($result)){
                    $domain = $result[0];
                    $i++;
                }
            }
            if(!$i){
                return $Controller->error($referer,'不允许的来源域名：'.$referer);
            }
        }
        if(!in_array($Request['show_domain'],$this->config['show_domain'])){
            return $Controller->error($referer,'不允许的显示源域名：'.$referer);
        }

        $timestamp = time();

        $nonce =    Helper::str()->str_rand(10);
        $encrypt_msg = [
                        'appid'=>$this->config['appid'],
                        'filesName'=>$Request['filesName'],
                        'show_domain'=>$Request['show_domain'],//请求显示图片或者文件的域名
                        'request_domain'=>$referer,//请求签名域名
                        'period'=>$this->config['period']
        ];//'[appid.域名,表单，有效期（分钟单位）]'.
        /**
         * 加密Prpcrypt
         */
        $Prpcrypt = new Prpcrypt($this->config['token']);
        $encrypt_msg = $Prpcrypt->encrypt(json_encode($encrypt_msg),$this->config['appid']);
        $SHA1 = new SHA1();
        $msgSignature = $SHA1->getSHA1($this->config['token'],$timestamp,$nonce,$encrypt_msg);
        return [
            'timestamp'=>$timestamp,
            'nonce'=>$nonce,
            'encrypt_msg'=>$encrypt_msg,
            'msgSignature'=>$msgSignature,
            'appid'=>$this->config['appid'],
        ];
    }

    /**
     * @Author pizepei
     * @Created 2019/4/6 16:08
     * @title  方法标题（一般是方法的简称）
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     *
     */
    public function verifySignature(array $Request,\pizepei\staging\Controller $Controller)
    {
        if(empty($_FILES)){
            return $Controller->error($Request,'FILES不能为[]');
        }

        if($this->configCsema === [])
        {
            return $Controller->error($Request,'configCsema配置不能为[]');
        }
        $SHA1 = new SHA1();
        $msgSignature = $SHA1->getSHA1($this->config['token'],$Request['timestamp'],$Request['nonce'],$Request['encrypt_msg']);
        if($Request['msgSignature'] !=$msgSignature){
            return $Controller->error($Request,'签名错误：'.$referer);
        }

        /**
         * 判断签名有效期
         */
        if( ((time()-$Request['timestamp'])/60) >  $this->config['period']){
            return $Controller->error($Request,'签名过期');
        }
        /**
         * 解密
         */
        $Prpcrypt = new Prpcrypt($this->config['token']);
        $encrypt_msg = $Prpcrypt->decrypt($Request['encrypt_msg']);

        /**
         * 判断解密是否与实际请求对应
         */
        if($encrypt_msg[2] != $this->config['appid']){
            return $Controller->error($Request,'解密信息错误');
        }

        $array = json_decode($encrypt_msg[1],true);
        if(empty($array)){
            return $Controller->error($Request,'解密信息错误');
        }

        $referer = $_SERVER['HTTP_REFERER']??'*';

        if($referer != $array['request_domain'])
        {
            return $Controller->error($Request,'request_domain错误');
        }
        var_dump($array);

        /**
         * 上传
         */
        return $this->filesUpload($encrypt_msg,$array['show_domain'],$Controller);

    }

    /**
     * @Author pizepei
     * @Created 2019/4/6 18:47
     *
     * @param $msg
     * @return mixed
     * @title  文件上传
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     *
     */
    public function filesUpload($msg,$show_domain,\pizepei\staging\Controller $Controller)
    {

        foreach($_FILES as $key=>$value){
            /**
             * 相同key的数组文件上传
             */
            if(is_array($value['name'])){

            }else{
                /**
                 * 判断文件大小
                 */
                if($value['size'] > $this->configCsema['size']){
                    return $this->error(['name'=>$key,'data'=>$value],'文件大小超过：'.($this->configCsema['size']/1024).'kb');
                }
                /**
                 * 判断文件类型
                 */
                if(!in_array($value['type'],$this->configCsema['type'])){
                    return $this->error(['name'=>$key,'data'=>$value],'不允许的文件类型：'.$value['type']);
                }
                $temporary = $value['tmp_name'];
                $expandedName = explode("/",$value['type']);
                $expandedName = end($expandedName);
            }
        }
        /**
         * 判断是否限制域名
         */
        if(isset($Request['referer']) || empty($Request['referer'])){
            $referer = $_SERVER['HTTP_REFERER']??'*';
        }
        if(!empty($this->config['domain'])){
            $i = 0;
            foreach($this->config['domain'] as $valueDomain)
            {
                preg_match($valueDomain,$referer, $result);
                /**
                 *匹配
                 */
                if(!empty($result)){
                    $domain = $result[0];
                    $i++;
                }
            }
            if(!$i){
                return $Controller->error($referer,'不允许的来源域名：'.$referer);
            }
        }
        /**
         * 进行文件上传
         */
        //确定目录
        $Y_m_d = date('Y_m_d');
        $H = date('H');
        $targetDir = $this->config['catalogue'].DIRECTORY_SEPARATOR.$this->config['appid'].DIRECTORY_SEPARATOR.$Y_m_d.DIRECTORY_SEPARATOR.$H.DIRECTORY_SEPARATOR;
        $targetDirUrl  = '/'.$Y_m_d.'/'.$H.'/';
        Func:: M('file') ::createDir($targetDir);
        /**
         * 确定文件名
         */
        $filesName =    Helper::str()->str_rand(32).'.'.$expandedName;
        move_uploaded_file($temporary,$targetDir.$filesName);
        /**
         * 写入文件
         */
        return $show_domain.$targetDirUrl.$filesName;


    }


}