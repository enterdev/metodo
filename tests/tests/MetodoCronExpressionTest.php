<?php

use enterdev\metodo\models\MetodoCron;

//TODO: more tests, especially with timezones
class MetodoCronExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../yii.php';
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testEverySecond()
    {
        $dailyJob1      = new MetodoCron();
        $expr = new \enterdev\metodo\models\MetodoCronExpression($dailyJob1);

        $time = new DateTime('2016-01-01 00:00:00');
        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT1S'));
        $this->assertEquals($time, $nextRun);

        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT1S'));
        $this->assertEquals($time, $nextRun);
    }

    public function testEvery30Seconds()
    {
        $dailyJob1      = new MetodoCron();
        $dailyJob1->second = '*/30';
        $expr = new \enterdev\metodo\models\MetodoCronExpression($dailyJob1);

        $time = new DateTime('2016-01-01 00:00:00');
        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT30S'));
        $this->assertEquals($time, $nextRun);

        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT30S'));
        $this->assertEquals($time, $nextRun);
    }
    public function testEveryMinute()
    {
        $dailyJob1      = new MetodoCron();
        $dailyJob1->second = '15';
        $expr = new \enterdev\metodo\models\MetodoCronExpression($dailyJob1);

        $time = new DateTime('2016-01-01 00:00:15');
        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT1M'));
        $this->assertEquals($time, $nextRun);

        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT1M'));
        $this->assertEquals($time, $nextRun);
    }
    public function testHourly()
    {
        $dailyJob1         = new MetodoCron();
        $dailyJob1->second = 0;
        $dailyJob1->minute = 0;
        $expr = new \enterdev\metodo\models\MetodoCronExpression($dailyJob1);

        $time = new DateTime('2016-01-01 00:00:00');
        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT1H'));
        $this->assertEquals($time, $nextRun);

        $nextRun = $expr->getNextRunDate($time);
        $time->add(new DateInterval('PT1H'));
        $this->assertEquals($time, $nextRun);
    }
}
