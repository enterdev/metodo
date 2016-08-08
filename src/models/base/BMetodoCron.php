<?php

namespace enterdev\metodo\models\base;

use \enterdev\metodo\models\MetodoTask;

/**
 * This is the model class for table "metodo_cron".
 *
 * @property integer      $id
 * @property string       $tag
 * @property string       $week_day
 * @property string       $day
 * @property string       $month
 * @property string       $year
 * @property string       $hour
 * @property string       $minute
 * @property string       $second
 * @property string       $time_zone
 * @property string       $exec_class
 * @property string       $method
 * @property string       $data
 * @property string       $alt_exec_class
 * @property string       $alt_method
 * @property string       $alt_data
 * @property string       $reschedule_on
 * @property string       $collision_resolution
 *
 * @property MetodoTask[] $metodoTasks
 */
abstract class BMetodoCron extends \yii\db\ActiveRecord
{
    const RESCHEDULE_ON_NEVER = 'never';
    const RESCHEDULE_ON_START = 'start';
    const RESCHEDULE_ON_SUCCESS = 'success';
    const RESCHEDULE_ON_FAIL = 'fail';
    const RESCHEDULE_ON_FINISH = 'finish';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'metodo_cron';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        //FIXME: 'week_day', 'day', 'month', 'year', 'hour', 'minute', 'second' these should be int|"*"|"*/" . int|null
        return [
            [['data', 'alt_data', 'reschedule_on', 'collision_resolution'], 'string'],
            [['tag', 'week_day', 'day', 'month', 'year', 'minute', 'second', 'method', 'alt_method'], 'string', 'max' => 32],
            [['hour', 'time_zone'], 'string', 'max' => 64],
            [['exec_class', 'alt_exec_class'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag' => 'Tag',
            'week_day' => 'Week Day',
            'day' => 'Day',
            'month' => 'Month',
            'year' => 'Year',
            'hour' => 'Hour',
            'minute' => 'Minute',
            'second' => 'Second',
            'exec_class' => 'Exec Class',
            'method' => 'Method',
            'data' => 'Data',
            'time_zone' => 'Time Zone',
            'alt_exec_class' => 'Alt Exec Class',
            'alt_method' => 'Alt Method',
            'alt_data' => 'Alt Data',
            'reschedule_on' => 'Reschedule On',
            'collision_resolution' => 'Collision Resolution',
        ];
    }

    /**
     * @return MetodoTask[]
     */
    public function getMetodoTasks()
    {
        return $this->hasMany(\enterdev\metodo\models\MetodoTask::class, ['cron_id' => 'id']);
    }
}
