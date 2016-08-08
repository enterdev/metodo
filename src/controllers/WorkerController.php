<?
namespace enterdev\metodo\controllers;

use yii\console\Controller;

use enterdev\metodo\models\MetodoTask;

class WorkerController extends Controller
{
    public function actionWork($taskId)
    {
        /** @var MetodoTask $task */
        $task = MetodoTask::findOne($taskId);
        if (!$task->execute())
            return self::EXIT_CODE_ERROR;

        return self::EXIT_CODE_NORMAL;
    }
}