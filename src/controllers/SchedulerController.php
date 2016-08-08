<?
namespace enterdev\metodo\controllers;

use yii\console\Controller;
use yii\log\Logger;

use enterdev\metodo\components\TimeZoneHelper;
use enterdev\metodo\models\MetodoTask;

class SchedulerController extends Controller
{
    protected $loopLimit = 0;
    protected $yiiBinPath = 'yii';

    public function actionCleanUp()
    {
        $keepSeconds = (key_exists('taskKeepSeconds', \Yii::$app->modules['metodo'])) ?
            \Yii::$app->modules['metodo']['taskKeepSeconds'] : false;

        if (!$keepSeconds)
            return;

        /** @var MetodoTask[] $tasks */
        $time  = new \DateTime();
        $time->sub(new \DateInterval('PT' . $keepSeconds . 'S'));

        MetodoTask::deleteAll('time < :time AND `status` = "success"', ['time' => $time->format('Y-m-d H:i:s')]);
    }


    //FIXME: should this be explicitly run in UTC?
    public function actionDaemon()
    {
        echo '[' . date('Y-m-d H:i:s') . '] Daemon Started.' . PHP_EOL;
        set_time_limit(0);
        $i = 0;
        TimeZoneHelper::set('UTC');
        while (true)
        {
            if (($this->loopLimit > 0) && ($i++ > $this->loopLimit))
                break;

            sleep(1);
            try
            {
                /** @var MetodoTask[] $tasks */
                $time  = new \DateTime();
                $tasks = MetodoTask::find()
                    ->with('cron')
                    ->where('time <= :time AND `status` = "scheduled"', ['time' => $time->format('Y-m-d H:i:s')])
                    ->all();

                foreach ($tasks as $task)
                {
                    $task->updateAttributes([
                        'status'     => 'running',
                        'start_time' => date('Y-m-d H:i:s')
                    ]);

                    if ($task->cron && $task->cron->reschedule_on == 'start')
                        $task->reschedule($time);

                    $cmd = $this->yiiBinPath . ' metodo/worker/work ' . (int)$task->id;
                    //TODO: think about multithreading here, maybe gearman, or just plain old nohup?
                    //TODO: implement collision resolution
                    exec(escapeshellcmd($cmd), $out, $taskResult);
                    $task->updateAttributes([
                        'status'   => ($taskResult == 0) ? 'success' : 'failed',
                        'end_time' => date('Y-m-d H:i:s')
                    ]);

                    if (!$this->rescheduleIfNeeded($task, $taskResult, $time))
                        throw new \Exception('Failed to reschedule a task: #' . $task->id);
                }
            }
            catch (\Exception $e)
            {
                \Yii::$app->log->logger->log($e->getMessage(), Logger::LEVEL_ERROR);
            }
        }

        echo '[' . date('Y-m-d H:i:s') . '] Daemon Stopped.' . PHP_EOL;

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * @param MetodoTask $task
     *
     * @param int        $taskResult
     * @param \DateTime  $time
     *
     * @return bool
     */
    private function rescheduleIfNeeded($task, $taskResult, $time)
    {
        if (!$task->cron)
            return true;

        $rescheduleNeeded =
            ($task->cron->reschedule_on == 'finish') ||
            (($task->cron->reschedule_on == 'success') && ($taskResult == 0)) ||
            (($task->cron->reschedule_on == 'fail') && ($taskResult != 0));

        if ($rescheduleNeeded)
            return $task->reschedule($time);
        else
            return true;
    }
}