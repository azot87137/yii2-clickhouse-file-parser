<?php

namespace app\models\forms;

use yii\base\Model;

class LogsSearchForm extends Model
{
    public $startDate;
    public $finishDate;

    public function rules()
    {
        return [
            [['startDate', 'finishDate'], 'required'],
            [['startDate', 'finishDate'], 'datetime', 'format' => 'yyyy-MM-dd HH:mm:ss'],
        ];
    }
}
