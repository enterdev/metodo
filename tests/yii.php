<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
define('CONSOLE_APP', 1);

defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/config/main.php');

require(__DIR__ . '/config/bootstrap.php');

$application = new yii\console\Application($config);
//$exitCode    = $application->run();
//exit($exitCode);