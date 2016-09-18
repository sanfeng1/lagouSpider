<?php

use yii\db\Migration;

/**
 * Handles the creation for table `careers_table`.
 */
class m160902_075312_create_careers_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('careers_table', [
            'id' => $this->primaryKey(),
            'positionName' => 'VARCHAR(20) NOT NULL COMMENT "职位名称"',
            'city' => 'VARCHAR(20) NOT NULL COMMENT "城市"',
            'salary' => 'VARCHAR(20) NOT NULL COMMENT "薪资"',
            'education' => 'VARCHAR(20) NOT NULL COMMENT "学历"',
            'workYear' => 'VARCHAR(20) NOT NULL COMMENT "经验"'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('careers_table');
    }
}
