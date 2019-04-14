<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/4/14 13:12
 * @title 时间来
 */
namespace pizepei\service\time;


use Overtrue\ChineseCalendar\Calendar;

class Time
{

    /**
     * 传入农历年月日以及传入的月份是否闰月获得详细的公历、农历信息.
     *
     * @param int  $year        lunar year
     * @param int  $month       lunar month
     * @param int  $day         lunar day
     * @param bool $isLeapMonth lunar month is leap or not.[如果是农历闰月第四个参数赋值true即可]
     * @param int  $hour        birth hour.[0~23]
     *
     * @return array
     */
    public static function lunar($year, $month, $day, $isLeapMonth = false, $hour = null)
    {
        $Calendar = new Calendar();
        return $Calendar->lunar($year, $month, $day, $isLeapMonth = false, $hour = null); // 阴历
    }

    /**
     * 传入阳历年月日获得详细的公历、农历信息.
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     *
     * @return array
     */
    public static function solar($year, $month, $day, $hour = null)
    {
        $Calendar = new Calendar();
        return $Calendar->solar($year, $month, $day, $hour = null); // 阴历
    }

}