<?php

namespace enterdev\metodo\models;

class MetodoTask extends \enterdev\metodo\models\base\BMetodoTask
{
    //TODO: should we really catch the exceptions quietly and just return false?
    public function execute()
    {
        if (!class_exists($this->exec_class) || !method_exists($this->exec_class, $this->method))
            return false;

        $data = $this->data;
        if ($data)
            $data = json_decode($data, true);

        try
        {
            $class  = $this->exec_class;
            $method = $this->method;
            $class::$method($data);
            return true;
        }
        catch (\Exception $e)
        {
            \Yii::error($e, 'MetodoTask');
        }

        return false;
    }

    /**
     * @param \DateTime $time
     *
     * @return bool
     */
    public function reschedule($time)
    {
        if (!$this->cron_id)
            return false;

        try
        {
            $newTask = $this->cron->createNextTask($time);
            if ($newTask && $newTask->save())
                return true;
        }
        catch (\Exception $e) { }

        return false;
    }
}
