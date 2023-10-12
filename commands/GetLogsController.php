<?php

namespace app\commands;

use app\models\forms\LogsSearchForm;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Get logs from db.
 */
class GetLogsController extends Controller
{
    /**
     * @param $startDate
     * @param $finishDate
     * @return int|void
     */
    public function actionIndex($startDate, $finishDate)
    {
        $form = new LogsSearchForm(['startDate' => $startDate, 'finishDate' => $finishDate]);

        if (!$form->validate()) {
            $this->stdout(json_encode($form->getErrors()));

            return ExitCode::DATAERR;
        }

        $logs = $this->findAllLogs($startDate, $finishDate);

        foreach ($logs as $log) {
            $this->outputLog($log);
        }

        $this->stdout('Total for request: ' . count($logs));
    }

    /**
     * @param $startDate
     * @param $finishDate
     * @return void
     */
    public function actionCount($startDate, $finishDate)
    {
        $count = $this->getAllLogsCount($startDate, $finishDate);

        $this->stdout('Total: ' . $count);
    }

    /**
     * @param $log
     * @return void
     */
    protected function outputLog($log): void
    {
        echo "IP: " . $log['remote_addr'] . "\n";
        echo "User: " . $log['remote_user'] . "\n";
        echo "Datetime: " . $log['time_local'] . "\n";
        echo "Request: " . $log['request'] . "\n";
        echo "Status: " . $log['status'] . "\n";
        echo "Body bytes sent: " . $log['body_bytes_sent'] . "\n";
        echo "Http referer: " . $log['http_referer'] . "\n";
        echo "Http user agent: " . $log['http_user_agent'] . "\n";
        echo "Http x forwarded for: " . $log['http_x_forwarded_for'] . "\n";
        echo "\n";
    }

    /**
     * @param $startDate
     * @param $finishDate
     * @return mixed
     */
    protected function findAllLogs($startDate, $finishDate)
    {
        return Yii::$app->clickhouse->createCommand(
            "SELECT * FROM nginx_logs WHERE time_local BETWEEN :startDate AND :finishDate ORDER BY time_local ASC",
            [
                'startDate' => $startDate,
                'finishDate' => $finishDate
            ]
        )->queryAll();
    }

    /**
     * @param $startDate
     * @param $finishDate
     * @return mixed
     */
    protected function getAllLogsCount($startDate, $finishDate)
    {
        return Yii::$app->clickhouse->createCommand(
            "SELECT COUNT(*) FROM nginx_logs WHERE time_local BETWEEN :startDate AND :finishDate ",
            [
                'startDate' => $startDate,
                'finishDate' => $finishDate
            ]
        )->queryScalar();
    }
}