<?php

use bashkarev\clickhouse\Migration;

class m231012_065539_create_file_last_positions extends Migration
{

    public function up()
    {
        Yii::$app->clickhouse->createCommand(
            "
            CREATE TABLE file_positions
            (
                file_name String,
                last_position UInt64,
                updated_at DateTime DEFAULT now()
            ) ENGINE = MergeTree() ORDER BY (file_name)
                "
        )->execute();
    }

    public function down()
    {
        $sql = "DROP TABLE file_positions";
        Yii::$app->clickhouse->createCommand($sql)->execute();
    }
}