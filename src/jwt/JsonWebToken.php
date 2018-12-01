<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/12/1
 * Time: 10:03
 * JSON Web Token基础类
 */
namespace pizepei\service\webSocket;
class JsonWebToken
{

    /**
     *jwtPayload
     */
    const jwtPayload = [
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
    protected $jwtHeader = [
        'alg'=>'md5',
        'typ'=>'JWT',
    ];

    /**
     * JsonWebToken constructor.
     *
     * @param array $Payload
     * @param array $JWT_config
     * @param null  $name
     */
    function __construct($Payload=[],$JWT_config=[])
    {

        var_dump($JWT_config);
        exit;
        /**
         * 处理配置
         */
        if($JWT_config != []){
            $this->JWT_secret = $JWT_config['JWT_secret']??$this->JWT_secret;
            $this->JWT_secret_base = $JWT_config['JWT_secret_base']??$this->JWT_secret_base;
            $this->Access_token_name = $JWT_config['Access_token_name']??$this->Access_token_name;
            $this->jwtHeader = isset($JWT_config['jwtHeader'])?array_merge($this->jwtHeader,$JWT_config['jwtHeader']):$this->jwtHeader;
        }
        //$this->setJWT($Payload);
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
        $jwtHeader = base64_encode(json_encode($this->jwtHeader));
        /**
         * 合并数据
         */
        $Payload['nbf'] = $Payload['nbf']??time();
        $Payload['iat'] = $Payload['iat']??time();
        $Payload['jti'] = $Payload['jti']??time().mt_rand(100000,999999);
        $PayloadData = array_merge(self::jwtPayload,$Payload);

        $jwtPayload = base64_encode(json_encode($PayloadData));
        $str = $jwtHeader.'.'.$jwtPayload;
        if($this->jwtHeader['alg'] == 'md5'){
            $secretToken  = $PayloadData['aud'] == 'socketServer'?$this->JWT_secret_base:$this->JWT_secret;
            $str .= '.'.md5($str.'.'.$secretToken);
        }
        $this->JWTstr = $str;
        $this->JWT_param  = '/?'.$this->Access_token_name.'='.$str;

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