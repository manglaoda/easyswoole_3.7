<?php
namespace App\Crontab;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;

class TestTask extends AbstractCronTask
{

    public static function getRule(): string
    {
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return  'TestTask';
    }

    function run(int $taskId, int $workerIndex)
    {
        var_dump('c');
        TaskManager::getInstance()->async(function (){
            var_dump(date('YmdHis'));
        });
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable->getMessage();
    }
}