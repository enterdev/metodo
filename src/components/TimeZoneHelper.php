<?php
namespace enterdev\metodo\components;

class TimeZoneHelper
{
    public static function set($time_zone)
    {
        if (!$time_zone)
            return;

        date_default_timezone_set($time_zone);
        //Calculate timezone for Mysql
        $utcDiff  = date('Z') / 3600;
        $negative = false;
        if ($utcDiff < 0)
            $negative = true;
        $time_zone_diff = number_format(abs($utcDiff), 2, ':', '');
        if ($utcDiff > - 10 && $utcDiff < 10)
            $time_zone_diff = '0' . $time_zone_diff;
        $time_zone_diff = $negative ? '-' : '+' . $time_zone_diff;
        \Yii::$app->db->createCommand('SET time_zone = :timeZone', ['timeZone' => $time_zone_diff])->execute();
    }
}