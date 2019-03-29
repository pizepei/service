<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/3/24 14:08
 * @title 密码类
 */
namespace pizepei\service\encryption;

class PasswordHash
{
    public function __construct($config=[])
    {
        //password_hash
    }

    public function password_get_info()
    {

    }

    /**
     * @Author pizepei
     * @Created 2019/3/24 14:15
     *
     * @param string $password
     * @param int    $algo
     * @param array  $options
     * @return bool|string
     * @title  创建一个密码hash
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public function password_hash(string $password,int $algo = PASSWORD_BCRYPT,array $options=['cost'=>10] )
    {
        return password_hash($password,$algo,$options);
    }

    /**
     * @Author pizepei
     * @Created 2019/3/24 15:59
     *
     * @param string $password
     * @param string $hash
     * @param int    $algo
     * @param array  $options
     * @return array|bool|string
     *
     * @title  方法标题（一般是方法的简称）
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     *
     */
    public function password_verify(string $password,string $hash,int $algo=PASSWORD_BCRYPT,array $options=[])
    {

        // 当硬件性能得到改善时，cost 参数可以再修改
        // 根据明文密码验证储存的散列
        if (password_verify($password, $hash)) {
            // 检测是否有更新的可用散列算法
            // 或者 cost 发生变化
            if(empty($options)){
                if (password_needs_rehash($hash, PASSWORD_DEFAULT, $options)) {

                    // 如果是这样，则创建新散列，替换旧散列
                    return $newHash = password_hash($password, PASSWORD_DEFAULT, $options);
                }
            }
            return ['result'=>true,'newHash'=>$newHash??false];
            // 使用户登录
        }
        return ['result'=>false];

    }
    /**
     * 过滤密码
     * @param string $pattern
     * @param string $password
     * @return mixed
     */
    public function password_match(string$pattern,string $password)
    {
        preg_match($pattern,$password,$matches);
        return$matches;
    }


}