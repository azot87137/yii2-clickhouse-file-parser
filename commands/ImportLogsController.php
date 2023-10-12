<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\ArrayHelper;

/**
 * Import logs from file to DB.
 */
class ImportLogsController extends Controller
{
    /**
     * @return int
     */
    public function actionIndex($filename = null)
    {
        if ($filename === null) {
            $filename = \Yii::getAlias('@runtime') . '/nginx_access.log';
        }

        if (!file_exists($filename)) {
            $this->stdout('Can\'t find access logs file.');
            return ExitCode::DATAERR;
        }

        $handle = fopen($filename, 'r');

        $positionFromDB = $this->getLastPositionFromDB($filename);

        $lastPosition = $positionFromDB['last_position'] ?? 0;

        $importedCount = 0;

        while (true) {
            fseek($handle, $lastPosition);

            while ($line = fgets($handle)) {
                $parts = explode(' ', $line);

                $data = $this->parseData($parts);

                Yii::$app->clickhouse->createCommand()->insert('nginx_logs', $data)->execute();

                $lastPosition = ftell($handle);

                $this->stdout(++$importedCount);
            }

            $this->updatePositionInDB($positionFromDB, $filename, $lastPosition);

            sleep(10);
        }
    }

    /**
     * @param $parts
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function parseData($parts): array
    {
        $data = [
            'remote_addr' => $this->getValueFromArray($parts, 0),
            'time_local' => Yii::$app->formatter->asDatetime(
                trim($this->getValueFromArray($parts, 3) . ' ' . $this->getValueFromArray($parts, 4), '[]'),
                'yyyy-MM-dd HH:mm:ss'
            ),
            'request' => trim($this->getValueFromArray($parts, 5), '"') . ' ' . $this->getValueFromArray(
                    $parts,
                    6
                ) . ' ' . trim($this->getValueFromArray($parts, 7), '"'),
            'status' => (int)$this->getValueFromArray($parts, 8),
            'body_bytes_sent' => (int)$this->getValueFromArray($parts, 9),
            'http_referer' => trim($this->getValueFromArray($parts, 10), '"'),
            'http_user_agent' => trim($this->getValueFromArray($parts, 11), '"') . ' ' . $this->getValueFromArray(
                    $parts,
                    12
                ) . ' ' . $this->getValueFromArray($parts, 13) . ' ' . trim(
                    $this->getValueFromArray($parts, 14),
                    '"'
                ) . ' ' . $this->getValueFromArray($parts, 15) . ' ' . $this->getValueFromArray($parts, 16)
        ];
        return $data;
    }

    protected function getValueFromArray(array $parts, int $key)
    {
        return ArrayHelper::getValue($parts, $key, '');
    }

    /**
     * @param $positionFromDB
     * @param string $filename
     * @param $lastPosition
     * @return void
     */
    protected function updatePositionInDB($positionFromDB, string $filename, $lastPosition): void
    {
        if ($positionFromDB) {
            $sql = "ALTER TABLE file_positions DELETE WHERE file_name = :filename";
            Yii::$app->clickhouse->createCommand($sql, [':filename' => $filename])->execute();
        }

        Yii::$app->clickhouse->createCommand()->insert('file_positions', [
            'file_name' => $filename,
            'last_position' => $lastPosition,
        ])->execute();
    }

    public function actionReset()
    {
        Yii::$app->clickhouse->createCommand("TRUNCATE TABLE nginx_logs")->execute();
        Yii::$app->clickhouse->createCommand("TRUNCATE TABLE file_positions")->execute();
    }

    /**
     * @param string $filename
     * @return mixed
     */
    protected function getLastPositionFromDB(string $filename)
    {
        return Yii::$app->clickhouse->createCommand(
            "SELECT last_position FROM file_positions WHERE file_name = :filename",
            [':filename' => $filename]
        )->queryOne();
    }
}
