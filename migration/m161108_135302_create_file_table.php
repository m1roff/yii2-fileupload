<?php

use app\components\Migration;

/**
 * Handles the creation of table `file`.
 */
class m161108_135302_create_file_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('{{%file}}', [
            'id' => $this->bigPrimaryKey()->unsigned()->notNull(),
            'entity' => $this->string()->defaultValue(null)->comment('Связанняа модель'),
            'entity_id' => $this->bigInteger()->unsigned()->defaultValue(null)->comment('ID связанной модели'),
            'entity_attribute' => $this->string()->defaultValue(null)->comment('Атрибут связанной модели'),
            'hash' => $this->string(32)->notNull()->comment('Идентификатор в файловой системе'),
            'file_name' => $this->string()->defaultValue(null)->comment('Реальное название файла'),
            'file_size' => $this->integer()->defaultValue(null)->comment('Размер файла'),
            'file_mime' => $this->string()->defaultValue(null)->comment('Тип файла'),
            'file_extension' => $this->string(10)->defaultValue(null)->comment('Расширение файла'),
            'params_data' => $this->text()->defaultValue(null)->comment('Json Data'),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultValue(null),
            'updated_at' => $this->timestamp()->defaultValue(null),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('{{%file}}');
    }
}
