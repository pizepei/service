<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2018/10/31
 * Time: 17:42
 */

namespace pizepei\service\websocket;


class WebSocketServer
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
     * 是否过滤真实的clientId
     * @var bool
     */
    const clientIdFilter = true;
    /**
     * json_encode options参数
     * @var int
     */
    const json_encode_options = JSON_UNESCAPED_UNICODE;
    /**
     * token 在客户端连接 进行简单的处理
     * false 代表不开启
     */
    const Access_token = true;
    /**
     * token  名
     */
    const Access_token_name = 'token';

    /**
     * JWT 解密 key
     */
    const JWT_secret = 'sdsikkljl68345f';
    /**
     * JWT 解密 key
     */
    const JWT_secret_base = 'sdsikkljl68345fd2f7s';

    /**
     * JWT 权限保留时间
     */
    const JWT_control_time = 7200;
    /**
     * 缓存key
     */
    const JWT_control_key = 'JWT_control_';
    /**
     * 是否使用redis
     */
    const Redis = true;
    /**
     * 缓存
     */
    const CacheType = 'swoole_table';
    /**
     * client 事件列表
     */
    const eventList = [
        'init'=>['初始化','public'],
        'heartbeat'=>['心跳','public'],
        'returnReceiptFd'=>['fd回执','base'],
        'returnReceiptUser'=>['User回执','public'],
        'correlation'=>['关联','public'],
        'clientExist'=>['判断client是否在线','public'],
        'clientDisconnect'=>['关闭对应的客户端','public'],
        'clientMassAll'=>['向所有客户端群发信息','base'],
        'clientMassGroup'=>['向群组发信息','public'],
        'clientSendUser'=>['向uid发送数据','public'],
        'clientSendFd'=>['向fd发送数据','base'],
    ];
    /**
     * 配置
     */
    const config = [
        //'heartbeat_check_interval'=>15,//每5s发送一次心跳检测
        //'heartbeat_idle_time'      =>30,//10s没有回复断开
    ];
    /**
     * WebSocketServer constructor.
     */
    function __construct()
    {
        /**
         * 实例化WebSocketServer
         */
//        $this->Server = new \Swoole\WebSocket\Server(self::host, self::port);

        $this->Server = new \swoole_websocket_server(self::host, self::port);
        $this->Server->set(self::config);
        /**
         * 创建数据表
         */
        $this->table = new \Swoole\Table(5900);
        /**
         * 创建表结构
         */
        $this->table->column('fd', $this->table::TYPE_INT,8);
        $this->table->column('clientId', $this->table::TYPE_STRING,32);
        $this->table->column('userId', $this->table::TYPE_STRING,32);
        $this->table->column('exp', $this->table::TYPE_INT,10);
        $this->table->column('createTime', $this->table::TYPE_INT,10);
        $this->table->create();
        echo PHP_EOL.'创建数据表'.PHP_EOL;
        /**
         *回调函数 事件
         */
        $this->Server->on('open',[$this,'onOpen']);
        $this->Server->on('message',[$this,'onMessage']);
        $this->Server->on('close',[$this,'onClose']);
        $this->Server->on('task',[$this,'onTask']);
        $this->Server->on('finish',[$this,'onFinish']);

        echo PHP_EOL.'实例化成功'.PHP_EOL;
        echo PHP_EOL.'host:'.self::host.PHP_EOL;
        echo PHP_EOL.'port:'.self::port.PHP_EOL;
        /**
         * 启动服务
         */
        $this->Server->start();

    }

    /**
     *  异步任务Task
     * @param \swoole_server $serv
     * @param                $task_id
     * @param                $from_id
     * @param                $data
     */
    public function onTask(swoole_server  $serv, $task_id, $from_id, $data)
    {


    }
    /**
     * 当worker进程投递的任务在task_worker中完成时，task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程。
     * @param \swoole_server $serv
     * @param                $task_id
     * @param                $data
     */
    public function onFinish(swoole_server  $serv, $task_id, $data)
    {


    }
    /**
     * 客户端开始连接回调函数 事件
     * @param $Server
     * @param $request
     */
    public function onOpen($Server,$request)
    {
        /**
         * 获取url参数（get）
         */
        $query_string = null;
        if(isset($request->server['query_string'])){
            parse_str($request->server['query_string'],$query_string);
        }
        echo PHP_EOL.'************客户端开始连接回调**************'.PHP_EOL;
        var_dump($query_string);
        echo PHP_EOL.'**************************'.PHP_EOL;
        /**
         * 判断是否启用redis
         */
        if(self::Redis){
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $Server->Redis =$redis;
        }
        /**
         * JWT验证
         * JWT验证逻辑
         *      1、通过JWT_secret解密 判断请求中 Access_token 是否合法
         *      2、合法使用 Payload 中的信息注册绑定（fd 与Payload中的用户标识）
         * 要求开启 redis
         * try/catch
         */
        if(self::Access_token){
            /**
             * 判断参数是否为空
             */
            if(!isset($query_string[self::Access_token_name])){ $pushData = ['serverError'=>4001,'msg'=>'非法请求:Access_token必须的']; }
            /**
             * 切割
             */
            $JWT = explode('.',$query_string[self::Access_token_name]);
            if(count($JWT) != 3){
                $pushData = ['serverError'=>4002,'msg'=>'非法请求'];
            }else{
                /**
                 * 进行鉴权通过
                 */
                $Payload = json_decode(base64_decode($JWT[1]),true);
                $JWTmd5 = md5($JWT[0].'.'.$JWT[1].'.'.($Payload['aud']=='socketServer'?self::JWT_secret_base:self::JWT_secret));
                var_dump($Payload);
                if($JWTmd5 === $JWT[2]){
                    if(isset($Payload['data']['uid']) && !empty($Payload['data']['uid'])){
                        echo PHP_EOL.'************鉴权通过**************'.PHP_EOL;
                        /**
                         * 保存uid
                         */
                        $Server->clientId = $Payload['data']['uid'];
                        /**
                         *保存权限设置
                         */
                        $Server->clientAuthority = $Payload['aud']=='socketServer'?'base':'public';
                        //var_dump($Server->clientId);
                        $pushData = [
                            'success'=>2001,
                            'msg'=>'鉴权通过',
                            'fd'=> $request->fd,
                            'uid'=>$Server->clientId,
                        ];
                    }else{
                        $pushData = ['serverError'=>5005,'msg'=>'uid错误'];
                    }


                }else{
                    echo PHP_EOL.'************鉴权失败**************'.PHP_EOL;
                    $pushData = ['serverError'=>5003,'msg'=>'鉴权失败'];
                }
            }
        }
        $data =  json_encode($pushData,self::json_encode_options);
        if(isset($pushData['serverError'])){

        }else{

            //if(self::clientIdFilter)
            //self::clientIdFilter;
            $pushData['FD'] = $request->fd;
            /**
             * 缓存表
             * 逻辑 jwt 鉴权通过
             * 把jwt的数据缓存到表中
             */
            if(self::CacheType == 'Redis'){
                /**
                 * 判断是否启用Redis
                 */
                if(!self::Redis){ $pushData = ['serverError'=>4001,'msg'=>'服务器内部错误']; }
                $key =   self::JWT_control_key.md5($request->fd.$request->header['sec-websocket-key']);
                $Server->Redis->set($key,$data,self::JWT_control_time);
            }else if(self::CacheType == 'swoole_table'){

                echo PHP_EOL.'************写入绑定客户端数据**************'.PHP_EOL;

                $this->table->set($Payload['data']['uid'],
                    ['fd' => $request->fd,
                     'clientId' => 'base',
                     'userId' => $request->fd,
                     'createTime'=>time(),
                     'exp'=>$Payload['exp']??60,
                    ]
                );
            }
        }
        echo $request->fd;
        /**
         * 返回数据
         */
        $Server->push($request->fd,$data);
    }
    /**
     * 当服务器收到来自客户端的数据帧时会回调此函数
     * @param $Server
     * @param $frame
     */
    public function onMessage($Server,$frame)
    {
        echo PHP_EOL.'************接收客户端信息**************'.PHP_EOL;
        /**
         * 判断客户端合法性
         */
        $client = $this->table->get( $Server->clientId);

        if(!$client){return $Server->push($frame->fd,json_encode(['serverError'=>5003,'msg'=>'鉴权失败'],self::json_encode_options));}
        echo time() -($client['exp']+$client['createTime']);
        if($client['exp']+$client['createTime'] < time() ){ return $Server->push($frame->fd,json_encode(['serverError'=>5006,'msg'=>'鉴权过期'],self::json_encode_options)); }
        /**
         * 获取数据
         */
        $data = json_decode($frame->data,true);
        /**
         * 判断事件
         */
        if(!isset($data['event'])){ return $Server->push($frame->fd,json_encode(['serverError'=>7001,'msg'=>'无效的事件'],self::json_encode_options));  }
        /**
         * 判断是否是标准事件
         */
        if(!isset(self::eventList[$data['event']])){ return $Server->push($frame->fd,json_encode(['serverError'=>7002,'msg'=>'无效的事件'],self::json_encode_options));  }
        /**
         * 判断是否是有使用事件的权限
         */
        if($Server->clientAuthority != 'base'){
            if(self::eventList[$data['event']][1] != $Server->clientAuthority){
                return $Server->push($frame->fd,json_encode(['serverError'=>7301,'msg'=>'无事件权限'],self::json_encode_options));
            }
        }
        $push =[
            'success'=>2000,
            'msg'=>'ok',
            'uid'=>$Server->clientId,
            'fd'=>$frame->fd,
            'serverTime'=>time(),
        ];
        /**
         * 开始
         */
        switch($data['event']){
            case 'clientExist'://判断client是否在线
                /**
                 * 判断数据的准确性
                 */
                echo PHP_EOL.'************判断client是否在线**************'.PHP_EOL;
                if(!isset($data['data']['id']) ||  !isset($data['data']['type']) ){
                    echo PHP_EOL.'************id不能为空**************'.PHP_EOL;
                    return $Server->push($frame->fd,json_encode(['serverError'=>7412,'msg'=>'id\type不能为空'],self::json_encode_options));
                }
                /**
                 *判断是获取id  还是fd
                 */
                if($data['data']['type'] == 'uid'){
                    $clientEvent = $this->table->get($data['data']['id']);
                    if(!$clientEvent){ return $Server->push($frame->fd,json_encode(['serverError'=>7414,'msg'=>'client不存在'],self::json_encode_options)); };
                    $fd = $clientEvent['fd'];
                }
                $fd = $fd??$data['data']['id'];
                /**
                 * 进行判断并且返回数据
                 */
                $push['data'] = [
                    'status' => $Server->exist($fd),
                    'type'   => $data['data']['type'],
                    'id'     => $data['data']['id'],
                ];
                $Server->push($frame->fd,json_encode($push,self::json_encode_options));
                echo PHP_EOL."clientExist".PHP_EOL;
                break;
            case 'correlation'://关联
                echo "i correlation 0";
                break;
            case 'clientDisconnect'://关闭对应的客户端
                echo "i clientDisconnect 0";
                break;
            case 'clientMassAll'://向所有客户端群发信息
                echo "i clientMassAll 0";
                break;
            case 'clientMassGroup'://向群组发信息
                echo "i clientMassGroup 0";
                break;
            case 'clientSendUser'://向uid发送数据
                /**
                 * 获取对象数据
                 */
                if(!isset($data['data']['objectId']) || !isset($data['data']['type']) ||   !isset($data['data']['content'])  ){ return $Server->push($frame->fd,json_encode(['serverError'=>7614,'msg'=>'content/objectId/type 不能为空'],self::json_encode_options)); };
                $clientEvent = $this->table->get($data['data']['objectId']);
                var_dump($data);
                var_dump($clientEvent);

                if(!$clientEvent){ return $Server->push($frame->fd,json_encode(['serverError'=>7615,'msg'=>'objectId 不存在'],self::json_encode_options)); }


                if(!$Server->exist($clientEvent['fd'])){  return $Server->push($frame->fd,json_encode(['serverError'=>7621,'msg'=>'objectId 不存在/不在线'],self::json_encode_options)); }
                $push['data'] =[
                    'messageId'=>$data['data']['messageId'],
                    'type'=>$data['data']['type'],
                    'info'=>$data['data']['info']??'',
                    'title'=>$data['data']['title']??'普通信息',
                    'content'=>$data['data']['content'],
                ];
                $Server->push($clientEvent['fd'],json_encode($push,self::json_encode_options));
                $Server->push($frame->fd,json_encode($push,self::json_encode_options));

                break;
            case 'clientSendFd'://fd发信息
                /**
                 * 获取对象数据
                 */
                if(!isset($data['data']['objectId']) || !isset($data['data']['type']) ||   !isset($data['data']['content'])  ){ return $Server->push($frame->fd,json_encode(['serverError'=>7614,'msg'=>'content/objectId/type 不能为空'],self::json_encode_options)); };

                if(!$Server->exist($data['data']['objectId'])){  return $Server->push($frame->fd,json_encode(['serverError'=>7616,'msg'=>'objectId 不存在/不在线'],self::json_encode_options)); }

                $push['data'] =[
                    'messageId'=>$data['data']['messageId'],
                    'type'=>$data['data']['type'],
                    'info'=>$data['data']['info']??'',
                    'title'=>$data['data']['title']??'普通信息',
                    'content'=>$data['data']['content'],
                ];
                $Server->push($data['data']['objectId'],json_encode($push,self::json_encode_options));
                break;

            case 'returnReceiptFd'://fd回执
                /**
                 * 获取对象数据
                 */
                if(!isset($data['data']['objectId']) || !isset($data['data']['status']) ||   !isset($data['data']['messageId'])  ){ return $Server->push($frame->fd,json_encode(['serverError'=>7614,'msg'=>'status/objectId/messageId 不能为空'],self::json_encode_options)); };

                if(!$Server->exist($data['data']['objectId'])){  return $Server->push($frame->fd,json_encode(['serverError'=>7620,'msg'=>'objectId 不存在/不在线'],self::json_encode_options)); }

                $push['data'] =[
                    'messageId'=>$data['data']['messageId'],//消息id
                    'sender'=>$data['data']['sender'],//发送方
                    'recipients'=>$data['data']['recipients'],//接收方
                    'status'=>$data['data']['status'],//结果 false true
                    'remark'=>$data['data']['remark']??"",//备注信息
                ];

                $Server->push($data['data']['objectId'],json_encode($push,self::json_encode_options));
                break;

            case 'returnReceiptUser'://user回执

                if(!isset($data['data']['objectId']) || !isset($data['data']['status'])  ||  !isset($data['data']['messageId']) ){ return $Server->push($frame->fd,json_encode(['serverError'=>7614,'msg'=>'status/objectId/messageId 不能为空'],self::json_encode_options)); };
                $clientEvent = $this->table->get($data['data']['objectId']);

                if(!$clientEvent){  return $Server->push($frame->fd,json_encode(['serverError'=>7617,'msg'=>'objectId 不存在'],self::json_encode_options)); }

                $push['data'] =[
                    'messageId'=>$data['data']['messageId'],//消息id
                    'sender'=>$data['data']['sender'],//发送方
                    'recipients'=>$data['data']['recipients'],//接收方
                    'status'=>$data['data']['status'],//结果 false true
                    'remark'=>$data['data']['remark']??"",//备注信息
                ];
                $Server->push($clientEvent['fd'],json_encode($push,self::json_encode_options));
                break;
            case 'heartbeat'://心跳

                if(isset($data['data']['Receipt'])){
                    return $Server->push($frame->fd,$push,self::json_encode_options);
                };
                break;

            default:
                echo "不存在";
        }

    }
    /**
     * 客户端关闭链接
     * @param $Server
     * @param $fd
     */
    public function onClose($Server,$fd)
    {
        /**
         * 删除缓存的客户端数据
         */
        //$this->table->del( $Server->clientId);
        /**
         * 通知其他用户
         */
        //var_dump($fd);
        //exist
    }
}

new WebSocketServer();