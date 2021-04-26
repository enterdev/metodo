<?php
namespace enterdev\metodo\controllers;

use yii\console\Controller;
use yii\log\Logger;

use enterdev\metodo\models\MetodoTask;

class TaskController extends Controller
{
    protected $yiiBinPath = './yii';

    public function actionExecute($id = null)
    {
        if (!$id)
            return self::EXIT_CODE_ERROR;

        echo '[' . date('Y-m-d H:i:s') . '] Start execute ID task: ' . $id . PHP_EOL;

        $time = new \DateTime();

        try {
            /** @var MetodoTask $task */
            $task = MetodoTask::find()->where(
                'id = :id LIMIT 1 FOR UPDATE SKIP LOCKED',
                ['id' => $id]
            )->one();

            if (!$task)
                return self::EXIT_CODE_ERROR;

            $out = '';
            $task->updateAttributes([
                'status'     => MetodoTask::STATUS_RUNNING,
                'start_time' => date('Y-m-d H:i:s')
            ]);

            if ($task->cron && $task->cron->reschedule_on == 'start')
                $task->reschedule($time);

            $cmd = $this->yiiBinPath . ' metodo/worker/work ' . (int)$task->id;
            exec(escapeshellcmd($cmd), $out, $taskResult);
            $task->updateAttributes([
                'status'   => ($taskResult == 0) ? MetodoTask::STATUS_SUCCESS : MetodoTask::STATUS_FAILED,
                'end_time' => date('Y-m-d H:i:s')
            ]);

            if (!$task->rescheduleIfNeeded($taskResult, $time))
                throw new \Exception('Failed to reschedule a task: #' . $task->id);
        }
        catch (\Exception $e)
        {
            \Yii::$app->log->logger->log($e->getMessage(), Logger::LEVEL_ERROR);
        }

        echo '[' . date('Y-m-d H:i:s') . '] Task ID ' . $id . ' completed successfully.' . PHP_EOL;

        return self::EXIT_CODE_NORMAL;
    }
}
