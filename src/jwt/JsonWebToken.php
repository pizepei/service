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
     * JsonWebToken constructor.
     *
     * @param array $Payload
     * @param null  $JWT_config
     */
    function __construct($Payload=[],$config=null)
    {
        if(!$config) {return false;}

        if(is_array($config)){
            $this->secret = $config['secret']??$this->secret;
            $this->token_name = $config['token_name']??$this->token_name;
            $this->Header = isset($config['Header'])?array_merge($this->jwtHeader,$config['jwtHeader']):$this->Header;
        }else{
            $this->init($config);
        }
        //exit;
        $this->setJWT($Payload);
    }
    /**
     * 设置JWT签名
     * @param $Payload
     * @param $Type
     */
    protected function setJWT($Payload)
    {
        /**
         * 合并数据
         */
        $Header = base64_encode(json_encode($this->Header));
        /**
         * 合并数据
         */
        $Payload['nbf'] = $Payload['nbf']??time();
        $Payload['iat'] = $Payload['iat']??time();
        $Payload['jti'] = $Payload['jti']??time().mt_rand(100000,999999);
        $PayloadData = array_merge(self::Payload,$Payload);

        $Payload = base64_encode(json_encode($PayloadData));
        $str = $Header.'.'.$Payload;
        if($this->Header['alg'] == 'md5'){
            $str .= '.'.md5($str.'.'.$this->secret);
        }
        $this->JWTstr = $str;
        $this->JWT_param  = '/?'.$this->token_name.'='.$str;
        //var_dump($this->JWT_param );
        //
        //var_dump($this->JWTstr);
    }

    /**
     * 获取配置
     * @param $name
     */
    protected  function init($name)
    {
        $secretData = JsonWebTokenConfig::secret[$name];
        /**
         * Header
         */
        $this->Header = JsonWebTokenConfig::Header;
        $this->Header['alg'] = $secretData['alg'];
        /**
         * Payload
         */

        $this->Payload = JsonWebTokenConfig::Payload[$secretData['Payload']];
        /**
         * secret
         */
        $this->secret = $secretData['value'];
        //var_dump($this->Payload);
        //var_dump($this->Header);
        //var_dump($this->secret);
    }


    /**
     * 包括 加 解密
     *
     */


    /**
     * 可选择
     *  加密方式
     *  缓存类型  redis 或者mysql
     *  不同权限的签名
     */




}