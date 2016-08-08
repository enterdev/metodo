<?php

use yii\db\Migration;

class m160113_124042_create_metodo_structure extends Migration
{
    public function up()
    {
        $this->createTable('metodo_cron', [
            'id'                   => $this->primaryKey(),
            'tag'                  => $this->string(32),
            'week_day'             => $this->string(32),
            'day'                  => $this->string(32),
            'month'                => $this->string(32),
            'year'                 => $this->string(32),
            'hour'                 => $this->string(32),
            'minute'               => $this->string(32),
            'second'               => $this->string(32),
            'time_zone'            => $this->string(64),
            'exec_class'           => $this->string(128),
            'method'               => $this->string(32),
            'data'                 => 'longtext',
            'alt_exec_class'       => $this->string(128),
            'alt_method'           => $this->string(32),
            'alt_data'             => 'longtext',
            'reschedule_on'        => 'enum(\'never\',\'start\',\'success\',\'fail\',\'finish\')',
            'collision_resolution' => 'enum(\'ignore\',\'skip\',\'stop_previous\',\'run_alternative\')',
        ]);
        $this->createTable('metodo_task', [
            'id'             => $this->primaryKey(),
            'time'           => $this->dateTime(),
            'cron_id'        => $this->integer(),
            'exec_class'     => $this->string(128),
            'method'         => $this->string(32),
            'data'           => 'longtext',
            'alt_exec_class' => $this->string(128),
            'alt_method'     => $this->string(32),
            'alt_data'       => 'longtext',
            'status'         => 'enum(\'scheduled\',\'running\',\'failed\',\'success\')',
            'percentage'     => 'tinyint',
            'start_time'     => $this->dateTime(),
            'end_time'       => $this->dateTime(),
        ]);
        $this->addForeignKey('metodo_task_ibfk_1', 'metodo_task', 'cron_id', 'metodo_cron', 'id', 'SET NULL', 'CASCADE');
    }

    public function down()
    {
        $this->dropForeignKey('metodo_task_ibfk_1', 'metodo_task');
        $this->dropTable('metodo_cron');
        $this->dropTable('metodo_task');
    }
}
