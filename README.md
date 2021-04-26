Metodo scheduled jobs extension for Yii 2
=========================================

This extension provides scheduled task capability [Yii framework 2.0](http://www.yiiframework.com).

The extension allows creating repeatable tasks or one-time tasks in the future.

Metodo is released under the MIT License. See the bundled [LICENSE.md](LICENSE.md) file.

Installation
============
1. composer require enterdev/metodo
2. composer install
3. yii migrate --migrationPath=@vendor/enterdev/metodo/src/migrations


Usage
=====
Metodo consists of two parts: crons and tasks.
Cron is a rule to create tasks, they are used to schedule tasks.
Tasks, however, are specific instructions, jobs to be performed. Tasks may or may not have a cron.

Metodo offers a daemon that runs tasks, but you can also run them manually if you know you're doing.

Configuration
-------------
Add metodo to your config modules
```php
    'modules' => [
        'metodo' => [
            'class' => 'enterdev\\metodo\\Module'
        ],
    ]
```

Running the daemon
------------------
run on supervisor or similar type of software:
```shell
yii metodo/scheduler/daemon
```

Running single task
------------------
where `123` it`s this ID task
```shell
yii metodo/task/execute 123
```

Creating a cron
---------------
The simplest cron that creates a task that will run every day:

```php
$dailyJob1      = new MetodoCron();
$dailyJob1->tag = 'System';

$dailyJob1->hour   = '0';
$dailyJob1->minute = '0';
$dailyJob1->second = '0';

$dailyJob1->exec_class = 'MockJob';
$dailyJob1->method     = 'job1';
$dailyJob1->save();
```

see MetodoCronTest.php for more examples


