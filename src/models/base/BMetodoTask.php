<?php

namespace enterdev\metodo\models\base;

use \enterdev\metodo\models\MetodoCron;

/**
 * This is the model class for table "metodo_task".
 *
 * @property string     $id
 * @property string     $tag
 * @property string     $time
 * @property integer    $cron_id
 * @property string     $exec_class
 * @property string     $method
 * @property string     $data
 * @property string     $alt_exec_class
 * @property string     $alt_method
 * @property string     $alt_data
 * @property string     $status
 * @property int        $percentage
 * @property string     $start_time
 * @property string     $end_time
 *
 * @property MetodoCron $cron
 */
abstract class BMetodoTask extends \yii\db\ActiveRecord
{
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILED = 'failed';
    const STATUS_SUCCESS = 'success';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'metodo_task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['time', 'start_time', 'end_time'], 'safe'],
            [['cron_id', 'percentage'], 'integer'],
            [['data', 'alt_data', 'status'], 'string'],
            [['exec_class', 'alt_exec_class'], 'string', 'max' => 128],
            [['tag', 'method', 'alt_method'], 'string', 'max' => 32],
            [['cron_id'], 'exist', 'skipOnError' => true, 'targetClass' => \enterdev\metodo\models\MetodoCron::class, 'targetAttribute' => ['cron_id' => 'id']],
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
            'time' => 'Time',
            'cron_id' => 'Cron ID',
            'exec_class' => 'Exec Class',
            'method' => 'Method',
            'data' => 'Data',
            'alt_exec_class' => 'Alt Exec Class',
            'alt_method' => 'Alt Method',
            'alt_data' => 'Alt Data',
            'status' => 'Status',
            'percentage' => 'Percentage',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCron()
    {
        return $this->hasOne(\enterdev\metodo\models\MetodoCron::class, ['id' => 'cron_id']);
    }

    public function getTextColumns()
    {
        return [];
    }
}
