<?php

namespace enterdev\metodo\models;

class MetodoCron extends \enterdev\metodo\models\base\BMetodoCron
{
    public function afterSave($insert, $oldAttributes)
    {
        parent::afterSave($insert, $oldAttributes);
        $this->reloadTasks($insert);
    }

    public function reloadTasks($retainOld = false)
    {
        if (!$retainOld)
            MetodoTask::deleteAll(['cron_id' => $this->id]);

        $task = new MetodoTask();
        $task->setAttributes($this->getExecutionAttributes());
        $time = new \DateTime();
        $time = $this->makeNextTime($time);
        if (!$time)
            return;

        $time = $time->format('Y-m-d H:i:s');

        if ($time)
        {
            $task->time = $time;
            $task->save();
        }
    }

    public function createNextTask($time = null)
    {
        if (!$time)
            $time = new \DateTime();
        $time = $this->makeNextTime($time);
        if (!$time)
            return null;

        $task = new MetodoTask();
        $task->setAttributes($this->getExecutionAttributes());
        $task->time = $time->format('Y-m-d H:i:s');

        return $task;
    }

    public function getExecutionAttributes()
    {
        return [
            'cron_id'        => $this->id,
            'exec_class'     => $this->exec_class,
            'method'         => $this->method,
            'data'           => $this->data,
            'alt_exec_class' => $this->alt_exec_class,
            'alt_method'     => $this->alt_method,
            'alt_data'       => $this->alt_data,
            'status'         => 'scheduled',
        ];
    }

    public function makeNextTime(\DateTime $time)
    {
        try
        {
            $cron = new MetodoCronExpression($this);
            $time = $cron->getNextRunDate($time);
            $time->setTimezone(new \DateTimeZone('UTC'));
        }
        catch (\Exception $e)
        {
            $time = null;
        }

        return $time;
    }

    public static function reloadAll()
    {
        /** @var MetodoCron[] $crons */
        $crons = self::find()->all();
        foreach ($crons as $cron)
            $cron->reloadTasks();
    }
}
