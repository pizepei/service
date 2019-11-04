<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/12/1
 * Time: 10:03
 * JSON Web Token基础类
 */
namespace pizepei\service\jwt;
use pizepei\config\JsonWebTokenConfig;
use pizepei\encryption\aes\Prpcrypt;
use pizepei\func\Func;
use pizepei\helper\ArrayList;
use pizepei\helper\Helper;
use pizepei\model\redis\Redis;

class JsonWebToken
{

    /**
     *jwtPayload
     */
    const Payload = [
        'iss'=>'server',//签发人
        'exp'=>7200,//过期时间s
        'sub'=>'phpServer--socketServer',//主题
        'aud'=>'socketServer',//受众
        'nbf'=>0,//生效时间
        'iat'=>0,//签发时间
        'jti'=>'',//编号(随机数)
    ];
    /**
     * jwt Header
     */
    protected $Header = [
        'alg'=>'md5',
        'typ'=>'JWT',
    ];
    /**
     * @var mixed|string
     */
    protected $secret = 'sda564545464546464';

    /**
     * @var string
     */
    protected $token_name = 'token';
    /**
     * @var array
     */
    protected $Payload = [];

    /**
     * JsonWebToken constructor.
     *
     * @param array $Payload
     * @param array  $config
     * @throws \Exception
     */
    function __construct()
    {
    }

    /**
     * 获取配置
     * @param $config
     * @param $Payload
     */
    protected  function init($config,$Payload)
    {
        /**
         * Header
         */
        $this->Header = $config['Header'];
        /**
         * Payload
         * 合并数据
         */
        $Payload['nbf'] = $Payload['nbf']??time();//生效时间
        $Payload['iat'] = $Payload['iat']??time();//签发时间
        $Payload['jti'] = $Payload['jti']??Helper::str()->int_rand(32);//随机数
        $this->Payload = array_merge(self::Payload,$config['Payload'],$Payload);//合并数据
        /**
         * secret
         */
        $this->secret = $config['secret'];
        $this->token_name = $config['token_name']??$this->token_name;
    }

    /**
     * @Author: pizepei
     * @Created: 2018/12/2 22:20
     * @return array
     * @title  设置JWT签名
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public function setJWT(array $Payload,array $config,$TokenSalt,$TokenSaltName='number')
    {
        $this->init($config,$Payload);
        $this->Header[$TokenSaltName] = $Payload[$TokenSaltName];
        $Header = base64_encode(json_encode($this->Header));
        /**
         * 判断加密方法
         */
        $exp = $this->Payload['exp'];
        if($this->Header['alg'] == 'base64_encode'){
            $this->Payload = base64_encode(json_encode($this->Payload));

        }else if($this->Header['alg'] == 'aes'){
            if(empty($config['secret_key'])){
                throw new \Exception('secret_key是必须的');
            }
            if(empty($this->Header['appid'])){
                throw new \Exception('appid是必须的');
            }
            $Prpcrypt = new Prpcrypt($config['secret_key']);
            $this->Payload = $Prpcrypt->encrypt(json_encode($this->Payload,JSON_FORCE_OBJECT ),$this->Header['appid'] );
            if(!$this->Payload){
                throw new \Exception('Payload加密错误');
            }
            $this->Payload = base64_encode($this->Payload);
        }
        $str = $Header.'.'.$this->Payload;
        /**
         * 判断签名方法
         */
        if($this->Header['sig'] == 'md5'){
            $signature = md5($str.'.'.$this->secret.$TokenSalt);
            $str .= '.'.$signature;
        }elseif($this->Header['sig'] == 'sha1')
        {
            $signature = sha1($str.'.'.$this->secret.$TokenSalt);
            $str .= '.'.$signature;
        }
        $this->JWTstr = $str;
        $this->JWT_param  = '/?'.$this->token_name.'='.$str;
        return ['str'=>$this->JWTstr,'param'=>$this->JWT_param,'signature'=>$signature,'exp'=>$exp];

    }

    /**
     * 解密jwt
     * @param string $jwtString
     * @param array  $config
     * @param string $TokenSalt
     * @return array|mixed|null
     * @throws \Exception
     */
    public function decodeJWT(string $jwtString,array $config,\Redis $Redis=null,$TokenSaltName='number')
    {
        # 切割主体
        $explode = explode('.',$jwtString);
        if(count($explode)  !== 3){throw new \Exception('Payload加密错误',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        # 验证签名
        $Header = json_decode(base64_decode($explode[0]),true);
        if((!isset($Header['sig'])) || (!isset($Header['sig']))){throw new \Exception('非法数据[Header]',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        $str = $explode[0].'.'.$explode[1];
        # 判断是否有用户自己的TokenSalt
        $TokenSalt = '';
        if($Redis){
            $TokenSalt = $Redis->get('user-logon-jwt-tokenSalt:'.$Header['number']);
        }
        # 判断签名方法
        if($Header['sig'] == 'md5')
        {
            $signature = md5($str.'.'.$config['secret'].$TokenSalt);
        }else if($Header['sig'] == 'sha1')
        {
            $signature = sha1($str.'.'.$config['secret'].$TokenSalt);
        }
        if($signature != $explode[2] ){throw new \Exception('非法数据[signature]',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        /**
         * 解密
         */
        if($Header['alg'] == 'base64_encode')
        {
            $Payload = json_decode(base64_decode($explode[1]),true);

        }else if($Header['alg'] == 'aes')
        {
            $Prpcrypt = new Prpcrypt($config['secret_key']);
            $Payload = $Prpcrypt->decrypt(base64_decode($explode[1]));
            if(!isset($Payload[2])){throw new \Exception('非法数据',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
            # 判断appid是否正确
            if($Payload[2]  !== $Header['appid']){throw new \Exception('非法数据',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
            $Payload = json_decode($Payload[1],true);
            if(empty($Payload)){throw new \Exception('非法数据',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        }
        # 判断是否过期
        if(!isset($Payload['nbf'])  ||  !isset($Payload['iat'])  || !isset($Payload['exp'])){error('非法操作[数据错误]',\ErrorOrLog::NOT_LOGGOD_IN_CODE);}
        $Payload['nbf'] = $Payload['nbf']??time();//生效时间
        $Payload['iat'] = $Payload['iat']??time();//签发时间
        static::is_time($Payload);
        return $Payload;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/4 15:21
     * @param $Payload
     * @title  验证是否在有效期内
     * @throws \Exception
     */
    public static function is_time($Payload)
    {
        if(time() < $Payload['nbf']){
            error('登录未生效',\ErrorOrLog::NOT_LOGGOD_IN_CODE);
        }
        if(time() > ($Payload['exp']+$Payload['nbf']) ){
            error('登录过期',\ErrorOrLog::NOT_LOGGOD_IN_CODE);
        }

    }


    /**
     * @Author 皮泽培
     * @Created 2019/10/17 13:55
     * @param array $userInfo
     * @param string $token
     * @return array
     * @title  缓存用户信息
     * @explain 缓存用户信息
     * @throws \Exception
     */
    public function cacheUserInfo(array $userInfo,string $token,int $period)
    {
        # 基础用户信息
            # 用户名、头像、权限信息

    }
}