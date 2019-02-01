<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/28
 * Time: 10:59
 * @title 短信日志数据模型
 */
namespace pizepei\service\sms;

use pizepei\model\db\Model;

class SmsLogModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'aisle'=>[
            'TYPE'=>'varchar(15)', 'DEFAULT'=>false, 'COMMENT'=>'使用的通道',
        ],
        'parameter'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'参数',
        ],
        'mobile'=>[
            'TYPE'=>'varchar(11)', 'DEFAULT'=>'', 'COMMENT'=>'手机号码',
        ],
        'type'=>[
            'TYPE'=>"ENUM('code','remind','inform','else','no')", 'DEFAULT'=>'no', 'COMMENT'=>'code验证码、remind提醒、inform通知、else其他 、no 没有',
        ],
        'pattern'=>[
            'TYPE'=>"ENUM('code','remind','inform','else','no')", 'DEFAULT'=>'no', 'COMMENT'=>'模式 code验证码、remind提醒、inform通知、else其他 、no 没有',
        ],

        'extend'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'详细参数',
        ],
        'code'=>[
            'TYPE'=>'varchar(10)', 'DEFAULT'=>'no', 'COMMENT'=>'验证码',
        ],
        'verify_time'=>[
            'TYPE'=>'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP', 'DEFAULT'=>false, 'COMMENT'=>'验证时间（被使用时间）',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5','6')", 'DEFAULT'=>'1', 'COMMENT'=>'状态1、发送失败、2等待验证、3验证验证过4、无效的验证5、保留6、保留',
        ],
        'request_id'=>[
            'TYPE'=>"uuid", 'DEFAULT'=>false, 'COMMENT'=>'请求id',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[
            //  NORMAL KEY `create_time` (`create_time`) USING BTREE COMMENT '参数'
            ['TYPE'=>'KEY','FIELD'=>'mobile','NAME'=>'mobile','USING'=>'BTREE','COMMENT'=>'手机号码'],
        ],//索引 KEY `ip` (`ip`) COMMENT 'sss '

        'PRIMARY'=>'id',//主键
    ];
}