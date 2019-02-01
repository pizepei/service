<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/1/21 22:30
 * @title 短信通道
 */

namespace pizepei\service\sms;


use pizepei\config\Service;
use pizepei\func\Func;

class Sms
{

    /**
     * @var null 通道对象
     */
    protected $aisle = null;
    /**
     * @var null 通道对象
     */
    protected $aisleName = null;
    /**
     * @var null 配置
     */
    protected $config = null;

    /**
     * @Author: pizepei
     * @Created: 2019/1/21 22:47
     *
     *
     * @title  方法标题（一般是方法的简称）
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public function __construct($config=null)
    {
        if($config){
            if(is_array($config)){
                $this->config = $config;
            }else{
                $this->config = Service::SMS[$config];
            }
        }else{
            /**
             * 使用配置
             */
            $this->config = Service::SMS[Service::SMS['default']];
        }
        /**
         * 获取配置
         * 通过配置确定需要实例化的通道对象
         */
        $namespace = "pizepei\service\sms\\".$this->config['aisleName'];
        $this->aisle = new $namespace($this->config);
    }

    /**
     * @Author: pizepei
     * @Created: 2019/1/21 23:03
     *
     * @param $PhoneNumbers
     * @param $pattern 模式
     * @throws \Exception
     *
     * @title  发送验证码
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public  function SendCode($pattern,$PhoneNumbers)
    {
        if(empty($PhoneNumbers)){
            throw new \Exception('非法的号码');
        }
        /**
         * 使用函数获取随机数字
         */
        $code = Func::M('str')::int_rand(6);
        $parameterData['code'] = $code;
        /**
         * 发送
         */
        $data = $this->aisle->SendSms($pattern,$PhoneNumbers,$parameterData);
        $data['code'] = $code;

        return $data;
    }
    /**
     * 验证类型
     */
    const CodeType = [
        'register'  =>1,
        'password'  =>2,
        'verify'    =>3
    ];

    /**
     * @Author: pizepei
     * @Created: 2019/1/21 22:37
     *
     * @param $PhoneNumbers 手机号码
     * @param $code 验证码
     * @param $type 验证码类型
     * @param int  $period 有效期默认600s
     * @return mixed
     *
     * @title  短信验证码验证方法
     * @explain 自动化验证方法（验证码的有效期不是SendSms决定是在验证的时候自己设置）
     */
    public  function CodeVerification($PhoneNumbers,$code,$type,$period=600)
    {

    }



}