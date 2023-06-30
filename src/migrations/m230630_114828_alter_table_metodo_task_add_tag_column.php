<?php

use yii\db\Migration;

class m230630_114828_alter_table_metodo_task_add_tag_column extends Migration
{
    public function up()
    {
        $this->addColumn('metodo_task', 'tag', $this->string(32)->null()->after('id'));
    }

    public function down()
    {
        $this->dropColumn('metodo_task', 'tag');
    }
}
