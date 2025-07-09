<?
namespace enterdev\metodo\controllers;

use yii\console\Controller;
use yii\helpers\Json;
use yii\log\Logger;

use enterdev\metodo\components\TimeZoneHelper;
use enterdev\metodo\models\MetodoTask;

class SchedulerController extends Controller
{
    protected $loopLimit  = 0;
    protected $yiiBinPath = './yii';

    public function actionCleanUp()
    {
        $keepSeconds = (key_exists('taskKeepSeconds', \Yii::$app->modules['metodo'])) ?
            \Yii::$app->modules['metodo']['taskKeepSeconds'] : false;

        if (!$keepSeconds)
            return;

        $time = new \DateTime();
        $time->sub(new \DateInterval('PT' . $keepSeconds . 'S'));

        MetodoTask::deleteAll('time < :time AND `status` = "success"', ['time' => $time->format('Y-m-d H:i:s')]);
    }


    //FIXME: should this be explicitly run in UTC?
    public function actionDaemon($supportedJobs = null, $unsupportedJobs = null)
    {
        echo '[' . date('Y-m-d H:i:s') . '] Daemon Started.' . PHP_EOL;
        set_time_limit(0);
        $i = 0;
        TimeZoneHelper::set('UTC');

        if ($supportedJobs)
            $supportedJobs = Json::decode($supportedJobs);

        if ($unsupportedJobs)
            $unsupportedJobs = Json::decode($unsupportedJobs);

        while (true)
        {
            if (($this->loopLimit > 0) && ($i++ > $this->loopLimit))
                break;

            $transaction = \Yii::$app->db->beginTransaction();
            try
            {
                $time = new \DateTime();

                $where = 'time <= :time AND `status` = "scheduled"';
                if ($supportedJobs)
                {
                    $supported = '"' . implode('","', $supportedJobs) . '"';
                    $where .= " AND `method` IN ($supported)";
                }

                if ($unsupportedJobs)
                {
                    $unsupported = '"' . implode('","', $unsupportedJobs) . '"';
                    $where .= " AND `method` NOT IN ($unsupported)";
                }

                $where .= ' LIMIT 1 FOR UPDATE SKIP LOCKED';

                $taskFinder = MetodoTask::find()
                    ->select("id")
                    ->where(
                        $where,
                        ['time' => $time->format('Y-m-d H:i:s')]
                    );

                $foundTaskId = $taskFinder->scalar();

                // Release the lock because it probably holds more than one row
                $transaction->rollBack();

                if (!$foundTaskId)
                {
                    sleep(1);
                    continue;
                }

                $transaction = \Yii::$app->db->beginTransaction();

                // Acquire the lock for a particular row (use of primary key)
                /** @var MetodoTask $task */
                $task = MetodoTask::find()
                    ->with('cron')
                    ->where('id = :id LIMIT 1 FOR UPDATE SKIP LOCKED', ['id' => $foundTaskId])
                    ->one();

                if ($task)
                {
                    $out = '';
                    $task->updateAttributes([
                        'status'     => 'running',
                        'start_time' => date('Y-m-d H:i:s')
                    ]);

                    if ($task->shouldRescheduleOnStart())
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
                        \Yii::$app->log->logger->log('Failed to reschedule a task: #' . $task->id, Logger::LEVEL_WARNING);

                    $transaction->commit();
                }
                else
                {
                    // We found our row, but someone took the lock first, try again immediately
                    $transaction->rollBack();
                }
            }
            catch (\Exception $e)
            {
                $transaction->rollBack();
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
        $task->refresh();

        if (!$task->shouldRescheduleOnCompletion())
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
