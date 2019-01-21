<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/21
 * Time: 17:47
 */
namespace pizepei\service\sms;


interface  Sms
{

    /**
     * 发送短信
     * @param $PhoneNumbers 手机号码
     * @param $data  内容
     * @param $period 有效期（验证码验证）
     * @param $config 配置
     * @return mixed
     */
    public static function SendSms($PhoneNumbers,$data,$period,$config=null);

    /**
     * 短信验证码验证
     * @param $PhoneNumbers 手机号码
     * @param $code 验证码
     * @return mixed
     */
    public static function CodeVerification($PhoneNumbers,$code);

}