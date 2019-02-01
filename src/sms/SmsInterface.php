<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/21
 * Time: 17:47
 */
namespace pizepei\service\sms;


interface  SmsInterface
{
    /**
     * SmsInterface constructor.
     *
     * @param $config 配置(验证码的有效期是在验证的时候检测，配置里面的period 是配置验证码缓存保存时间建议24h)
     */
    public function __construct($config);

    /**
     * @Author: pizepei
     * @Created: 2019/1/21 22:35
     * @param $pattern 模式
     * @param $PhoneNumbers 手机号码
     * @param $data  内容
     * @return mixed
     *
     * @title  发送验证方法
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public  function SendSms($pattern,$PhoneNumbers,$data);

}