<?php

use bashkarev\clickhouse\Migration;

class m231011_153420_create_logs extends Migration
{
    public function up()
    {
        $sql = "
            CREATE TABLE nginx_logs
            (
                remote_addr String,
                remote_user String,
                time_local DateTime,
                request String,
                status UInt16,
                body_bytes_sent UInt64,
                http_referer String,
                http_user_agent String,
                http_x_forwarded_for String
            )
            ENGINE = MergeTree()
            ORDER BY (time_local);
        ";

        Yii::$app->clickhouse->createCommand($sql)->execute();
    }

    public function down()
    {
        $sql = "DROP TABLE nginx_logs";
        Yii::$app->clickhouse->createCommand($sql)->execute();
    }
}
