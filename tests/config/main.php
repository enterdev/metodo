<?php
use yii\helpers\ArrayHelper;

$phpUnitConfigXml = simplexml_load_file(__DIR__ . '/../phpunit.xml');
$phpUnitConfig = ['DB_DSN' => '', 'DB_USER' => '', 'DB_PASSWD' => '', 'DB_DBNAME' => ''];
foreach ($phpUnitConfigXml->php->var as $var)
{
    /** @var SimpleXMLElement $var */
    $phpUnitConfig[(string)$var['name']] = (string)$var['value'];
}
$localConfig = (is_file(__DIR__ . '/main.local.php')) ? require_once(__DIR__ . '/main.local.php') : [];
$config      = [
    'id'                  => 'metodo-test',
    'basePath'            => dirname(dirname(__DIR__)),
    'controllerNamespace' => 'enterdev\metodo\controllers',
    'components'          => [
        'db' => [
            'class'    => \yii\db\Connection::class,
            'charset'  => 'utf8',
            'dsn'      => $phpUnitConfig['DB_DSN'] ?: 'mysql:host=;dbname=',
            'username' => $phpUnitConfig['DB_USER'],
            'password' => $phpUnitConfig['DB_PASSWD'],
        ]
    ],
];

return ArrayHelper::merge($config, $localConfig);
