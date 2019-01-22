<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/21
 * Time: 17:55
 * @title 阿里云短信通道
 */

namespace pizepei\service\sms;

use pizepei\service\sms\SmsInterface;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class Aliyun implements SmsInterface
{
    /**
     * SmsInterface constructor.
     *
     * @param $config 配置(验证码的有效期是在验证的时候检测，配置里面的period 是配置验证码缓存保存时间建议24h)
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @Author: pizepei
     * @Created: 2019/1/21 22:35
     *
     * @param $PhoneNumbers 手机号码
     * @param $data  内容
     * @return mixed
     *
     * @title  发送验证方法
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public  function SendSms($PhoneNumbers,$data)
    {


    }


    /**
     * @title        发送方法
     * @param        $PhoneNumbers 手机
     * @param        $SignName 签名
     * @param        $TemplateCode 模板
     * @param        $TemplateParam 参数
     * @param string $RegionId 地区
     */
    public function Send($PhoneNumbers,$SignName,$TemplateCode,$TemplateParam,$RegionId='cn-hangzhou')
    {
        // 设置一个全局客户端
        AlibabaCloud::accessKeyClient($this->config['accessKeyId'], $this->config['accessKeySecret'])
            ->regionId($RegionId)// 请替换为自己的 Region ID
            ->asGlobalClient();
        try {
            $result = AlibabaCloud::rpcRequest()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'RegionId' => $RegionId,
                        'PhoneNumbers' => $PhoneNumbers,
                        'SignName' => $SignName,//签名
                        'TemplateCode' => $TemplateCode,//模板id
                        'TemplateParam' => $TemplateParam,//模板参数
                    ],
                ])
                ->request();
            return $result->toArray();
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;

        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;

        }


    }





}