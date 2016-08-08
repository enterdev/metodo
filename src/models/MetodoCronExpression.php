<?php

namespace enterdev\metodo\models;

use \Cron\FieldFactory;

class MetodoCronExpression extends \Cron\CronExpression
{
    public function __construct(MetodoCron $cronJob)
    {
        $expression = [];
        foreach (['second', 'minute', 'hour', 'day', 'month', 'week_day', 'year'] as $param)
            $expression[] = is_null($cronJob->$param) ? '* ' : $cronJob->$param;

        parent::__construct(implode(' ', $expression), new FieldFactory(), $cronJob->time_zone);
    }

}
