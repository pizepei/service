<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/10/30
 * Time: 15:29
 */

namespace pizepei\service\webSocket;
use pizepei\service\webSocket\WebSocketClient;

class Client
{
    /**
     * 绑定的服务地址（可以是域名）
     * @var string
     */
    const host = '0.0.0.0';
    /**
     * 服务端口号
     * @var int
     */
    const port = 9501;
    /**
     * 服务的实例化
     * @var null
     */
    protected $Server = null;
    /**
     * json_encode options参数
     * @var int
     */
    const json_encode_options = JSON_UNESCAPED_UNICODE;
    /**
     * token  名
     */
    protected $Access_token_name = 'token';
    /**
     * JWT 解密 key
     */
    protected $JWT_secret = 'sdsikkljl68345f';

    /**
     * JWT 解密 key
     */
    protected $JWT_secret_base = 'sdsikkljl68345fd2f7s';

    /**
     * 是否使用redis
     */
    const Redis = true;
    /**
     * 缓存
     */
    const CacheType = 'swoole_table';

    protected $JWTstr = '/';
    /**
     * jwt Header
     */
    protected $jwtHeader = [
        'alg'=>'md5',
        'typ'=>'JWT',
    ];
    /**
     * 实例化的 WebSocketClient
     * @var null|\WebSocketClient
     */
    public $connect = null;
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
     * client 事件列表
     */
    const eventList = [
        'init'=>'初始化',
        'correlation'=>'关联',
        'clientExist'=>'判断client是否在线',
        'clientDisconnect'=>'关闭对应的客户端',
    ];

    /**
     * 需要发送的数据
     * @var string
     */
    protected $dataSend = '';
    /**
     * Client constructor.
     *
     * @param array $Payload
     * @param array $JWT_config
     */
    function __construct($Payload=[],$JWT_config=[])
    {
        /**
         * 处理配置
         */
        if($JWT_config != []){
            $this->JWT_secret = $JWT_config['JWT_secret']??$this->JWT_secret;
            $this->JWT_secret_base = $JWT_config['JWT_secret_base']??$this->JWT_secret_base;
            $this->Access_token_name = $JWT_config['Access_token_name']??$this->Access_token_name;

            $this->jwtHeader = isset($JWT_config['jwtHeader'])?array_merge($this->jwtHeader,$JWT_config['jwtHeader']):$this->jwtHeader;
        }
        $this->setJWT($Payload);
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        if(isset($this->$name)){
            return $this->$name;
        }
        return null;
    }

    /**
     * 开始连接
     * @return null|\WebSocketClient
     */
    public function connect()
    {
        $this->connect = new WebSocketClient(self::host, self::port,$this->JWT_param);
        $this->connect->connect();
        return $this->connect;
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
     * 判断是否在线
     * @param $fd
     * @return bool
     * @throws \Exception
     */
    public function exist($id,$type='uid')
    {
        $data = [
            'event'=>'clientExist',
            'data'=>[
                'id'=>$id,
                'type'=>$type,
            ],
        ];
        $this->connect->send(json_encode($data));
        //"{"success":2000,"msg":"ok","uid":1541057697,"fd":6,"serverTime":1541057697,"data":{"status":true,"type":"uid","id":1541042444}}
        $exist = $this->connect->recv(30,100);
        $exist = json_decode($exist->data,true);

        if(isset($exist['success'])){
            return $exist['data']['status'];
        }
        return false;
    }

    /**
     * 发送数据(注意：如接收回执但是对方没有回复回执会返回false)
     * @param      $id
     * @param      $data
     * @param bool $Receipt 默认不接收状态
     * @return bool
     * @throws \Exception
     */
    public function sendUser($id,$data,$Receipt=false)
    {
        if(!isset($data['type'])){ return false;}
        if(!isset($data['content'])){ return false;}

        if(!isset($id)){ return false;}

        $messageId = time().mt_rand(100,999).$id;
        $data['messageId'] = $messageId;
        $data['objectId'] = $id;

        $dataSend = [
            'event'=>'clientSendUser',
            'data'=>$data
        ];
        return $this->send($dataSend,$messageId,$Receipt);
    }
    /**
     * 统一发送方法
     * @param $id
     * @param $dataSend
     * @param $messageId
     * @param $Receipt
     * @return bool
     * @throws \Exception
     */
    private function send($dataSend,$messageId,$Receipt)
    {
        $this->dataSend = json_encode($dataSend,self::json_encode_options);

        if($Receipt){
            $this->connect->send($this->dataSend);
            $re = $this->connect->recv(4);
            if($re){
                $re = json_decode($re->data,true);
                if(isset($re['success']) &&  $re['messageId'] == $messageId){ return $re->data['status'];}
            }
            return false;
        }else{
            return $this->connect->send($this->dataSend);
        }
    }
    /**
     * 发送数据fd
     * @param      $id
     * @param      $data
     * @param bool $Receipt 默认不接收状态
     * @return bool
     * @throws \Exception
     */
    public function sendFd($id,$data,$Receipt=false)
    {
        if(!isset($data['type'])){ return false;}
        if(!isset($data['content'])){ return false;}
        if(!isset($id)){ return false;}

        $messageId = time().mt_rand(100,999).$id;
        $data['messageId'] = $messageId;
        $data['objectId'] = $id;
        $dataSend = [
            'event'=>'clientSendFd',
            'data'=>$data
        ];
        return $this->send($dataSend,$messageId,$Receipt);
    }

}