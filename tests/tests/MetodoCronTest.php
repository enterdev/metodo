<?php

use enterdev\metodo\models\MetodoCron;
use enterdev\metodo\models\MetodoTask;

class MetodoCronTest extends \PHPUnit_Extensions_Database_TestCase
{
    static private $pdo  = null;
    private        $conn = null;

    final public function getConnection()
    {
        if ($this->conn === null)
        {
            if (self::$pdo == null)
                self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

        return $this->conn;
    }

    protected function getDataSet()
    {
        return $this->createXmlDataSet(__DIR__ . '/../fixtures/metodo_dataset.xml');
    }

    public function setUp()
    {
        parent::setUp();

        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../yii.php';
        require_once __DIR__ . '/../MockJob.php';
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testUtcFlow()
    {
        $dailyJob1      = new MetodoCron();
        $dailyJob1->tag = 'System';

        $dailyJob1->hour   = '1';
        $dailyJob1->minute = '0';
        $dailyJob1->second = '0';

        $dailyJob1->exec_class = 'MockJob';
        $dailyJob1->method     = 'job1';
        $dailyJob1->data       = json_encode(['data' => true]);

        $this->assertEquals(true, $dailyJob1->save());

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->setTime($dailyJob1->hour, $dailyJob1->minute, $dailyJob1->second);
        $date->add(new DateInterval('P1D'));

        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $dailyJob1->id,
            'exec_class' => $dailyJob1->exec_class,
            'method'     => $dailyJob1->method,
            'data'       => $dailyJob1->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('P1D'));

        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $dailyJob1->id,
            'exec_class' => $dailyJob1->exec_class,
            'method'     => $dailyJob1->method,
            'data'       => $dailyJob1->data,
        ])->all();
        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testTimeZonedFlow()
    {
        $dailyJob1      = new MetodoCron();
        $dailyJob1->tag = 'System';

        $dailyJob1->hour   = '0';
        $dailyJob1->minute = '0';
        $dailyJob1->second = '0';

        $dailyJob1->exec_class = 'MockJob';
        $dailyJob1->method     = 'job1';
        $dailyJob1->data       = json_encode(['data' => true]);
        $dailyJob1->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $dailyJob1->save());

        $date = new DateTime('now', new DateTimeZone($dailyJob1->time_zone));
        $date->setTime($dailyJob1->hour, $dailyJob1->minute, $dailyJob1->second);
        $date->add(new DateInterval('P1D'));
        $date->setTimezone(new DateTimeZone('UTC'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $dailyJob1->id,
            'exec_class' => $dailyJob1->exec_class,
            'method'     => $dailyJob1->method,
            'data'       => $dailyJob1->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('P1D'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $dailyJob1->id,
            'exec_class' => $dailyJob1->exec_class,
            'method'     => $dailyJob1->method,
            'data'       => $dailyJob1->data,
        ])->all();
        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEverySecond()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);
        $job->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone($job->time_zone));
        $date->setTimezone(new DateTimeZone('UTC'));
        $date->add(new DateInterval('PT1S'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('PT1S'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();
        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEveryMinute()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);
        $job->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone($job->time_zone));
        $date->setTime($date->format('H'), $date->format('i'), $job->second);
        $date->setTimezone(new DateTimeZone('UTC'));
        $date->add(new DateInterval('PT1M'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('PT1M'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();
        $this->assertEquals(1, sizeof($metodoTasks));

        //every minute at 30 seconds
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->second     = '30';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);
        $job->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone($job->time_zone));
        $date->setTime($date->format('H'), $date->format('i'), $job->second);
        $date->setTimezone(new DateTimeZone('UTC'));
        if (date('s') >= 30)
            $date->add(new DateInterval('PT1M'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('PT1M'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();
        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEveryHour()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->minute     = '0';
        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);
        $job->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone($job->time_zone));
        $date->setTimezone(new DateTimeZone('UTC'));
        $date->setTime($date->format('H'), $job->minute, $job->second);
        $date->add(new DateInterval('PT1H'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('PT1H'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();
        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEveryDay()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->hour       = '0';
        $job->minute     = '0';
        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);
        $job->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone($job->time_zone));
        $date->setTime($job->hour, $job->minute, $job->second);
        $date->setTimezone(new DateTimeZone('UTC'));
        $date->add(new DateInterval('P1D'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('P1D'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();
        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEveryMonth()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->day        = '15';
        $job->hour       = '0';
        $job->minute     = '0';
        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);
        $job->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone($job->time_zone));
        $date->setTime($job->hour, $job->minute, $job->second);
        $date->setDate($date->format('Y'), $date->format('m'), $job->day);
        $date->setTimezone(new DateTimeZone('UTC'));
        if (date('m') >= 15)
            $date->add(new DateInterval('P1M'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('P1M'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEveryYear()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->month      = '1';
        $job->day        = '1';
        $job->hour       = '0';
        $job->minute     = '0';
        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);
        $job->time_zone  = 'Europe/Riga';

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone($job->time_zone));
        $date->setTime($job->hour, $job->minute, $job->second);
        $date->setDate($date->format('Y'), $job->month, $job->day);
        $date->setTimezone(new DateTimeZone('UTC'));
        $date->add(new DateInterval('P1Y'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('P1Y'));
        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEveryFirstOfTheMonthThisYear()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $y = date('Y');
        $job->year       = $y;
        $job->day        = '1';
        $job->hour       = '0';
        $job->minute     = '0';
        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->setTime($job->hour, $job->minute, $job->second);
        $date->setDate($date->format('Y'), $date->format('m'), $job->day);
        //FIXME: wtf kind of a test is this? now go and mock those dates ya lazy bum
        if ($date->format('m') < 12)
        {
            $date->add(new DateInterval('P1M'));
            /** @var MetodoTask[] $metodoTasks */
            $metodoTasks = MetodoTask::find()->where([
                'time'       => $date->format('Y-m-d H:i:s'),
                'cron_id'    => $job->id,
                'exec_class' => $job->exec_class,
                'method'     => $job->method,
                'data'       => $job->data,
            ])->all();

            $this->assertEquals(1, sizeof($metodoTasks));
            $this->assertEquals(true, $metodoTasks[0]->execute());
            $this->assertEquals(true, $metodoTasks[0]->delete());
            $this->assertEquals(true, $metodoTasks[0]->reschedule($date));
        }
        else
        {
            $date->add(new DateInterval('P1M'));
            /** @var MetodoTask[] $metodoTasks */
            $metodoTasks = MetodoTask::find()->where([
                'time'       => $date->format('Y-m-d H:i:s'),
                'cron_id'    => $job->id,
                'exec_class' => $job->exec_class,
                'method'     => $job->method,
                'data'       => $job->data,
            ])->all();

            $this->assertEquals(0, sizeof($metodoTasks));
        }
    }

    public function testSchedulingEveryFridayTheThirteens()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->week_day   = '5';
        $job->day        = '13';
        $job->hour       = '0';
        $job->minute     = '0';
        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);

        $this->assertEquals(true, $job->save());

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $date->setTime(0, 0, 0);
        if ($date->format('w') != 4)
            $date->setTimestamp(strtotime('next friday', $date->getTimestamp()));

        while ($date->format('d') != 13)
        {
            /** @var MetodoTask[] $metodoTasks */
            $metodoTasks = MetodoTask::find()->where([
                'time'       => $date->format('Y-m-d H:i:s'),
                'cron_id'    => $job->id,
                'exec_class' => $job->exec_class,
                'method'     => $job->method,
                'data'       => $job->data,
            ])->all();
            $this->assertEquals(0, sizeof($metodoTasks));
            $date->setTimestamp(strtotime('next friday', $date->getTimestamp()));
        }

        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->setTimestamp(strtotime('next friday', $date->getTimestamp()));
        while ($date->format('d') != 13)
        {
            /** @var MetodoTask[] $metodoTasks */
            $metodoTasks = MetodoTask::find()->where([
                'time'       => $date->format('Y-m-d H:i:s'),
                'cron_id'    => $job->id,
                'exec_class' => $job->exec_class,
                'method'     => $job->method,
                'data'       => $job->data,
            ])->all();
            $this->assertEquals(0, sizeof($metodoTasks));
            $date->setTimestamp(strtotime('next friday', $date->getTimestamp()));
        }

        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
    }

    public function testSchedulingEvery15Minutes()
    {
        $job      = new MetodoCron();
        $job->tag = 'System';

        $job->minute     = '*/15';
        $job->second     = '0';
        $job->exec_class = 'MockJob';
        $job->method     = 'job1';
        $job->data       = json_encode(['data' => true]);

        $this->assertEquals(true, $job->save());
        $date = new DateTime('now', new DateTimeZone('UTC'));
        $m = floor($date->format('i') / 15);
        $date->setTime($date->format('H'), $m * 15, 0);
        $date->add(new DateInterval('PT15M'));

        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
        $this->assertEquals(true, $metodoTasks[0]->execute());
        $this->assertEquals(true, $metodoTasks[0]->delete());
        $this->assertEquals(true, $metodoTasks[0]->reschedule($date));

        $date->add(new DateInterval('PT15M'));

        /** @var MetodoTask[] $metodoTasks */
        $metodoTasks = MetodoTask::find()->where([
            'time'       => $date->format('Y-m-d H:i:s'),
            'cron_id'    => $job->id,
            'exec_class' => $job->exec_class,
            'method'     => $job->method,
            'data'       => $job->data,
        ])->all();

        $this->assertEquals(1, sizeof($metodoTasks));
    }

}
