<?php

use app\components\Migration;

/**
 * Handles the creation of table `file_cache`.
 */
class m161108_140514_create_file_cache_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('{{%file_cache}}', [
            'id' => $this->bigPrimaryKey(),
            'file_id' => $this->bigInteger()->unsigned()->notNull(),
            'hash' => $this->string(32)->notNull(),
            'url' => $this->string()->notNull(),
            'created_at' => $this->timestamp()->defaultValue(null),
            'updated_at' => $this->timestamp()->defaultValue(null),
        ]);

        // creates index for column `file_id`
        $this->createIndex(
            'idx-file_cache-file_id',
            'file_cache',
            'file_id'
        );

        // add foreign key for table `file`
        $this->addForeignKey(
            'fk-file_cache-file_id',
            'file_cache',
            'file_id',
            'file',
            'id',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        // drops foreign key for table `file`
        $this->dropForeignKey(
            'fk-file_cache-file_id',
            'file_cache'
        );

        // drops index for column `file_id`
        $this->dropIndex(
            'idx-file_cache-file_id',
            'file_cache'
        );

        $this->dropTable('{{%file_cache}}');
    }
}
