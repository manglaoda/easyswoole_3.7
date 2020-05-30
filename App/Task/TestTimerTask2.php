<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2019/11/20 0020
 * Time: 10:44
 */

namespace App\Task;

use App\Model\BModel;
use EasySwoole\Component\CoroutineRunner\Runner;
use EasySwoole\Component\CoroutineRunner\Task;
use EasySwoole\ORM\DbManager;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use Swoole\Table;


/**
 * 定时任务中 执行协程 （控制）
 * Class TestTimerTask2
 * @package App\Task
 */
class TestTimerTask2 implements TaskInterface
{
    protected $data;
    //通过构造函数,传入数据,获取该次任务的数据
    public function __construct($data=[])
    {
        $this->data = $data;
    }

    function run(int $taskId, int $workerIndex)
    {

        // 1最大协程数量， 2可投递的task队列长度
        $runner = new Runner(5);

        // 异常处理
        $runner->setOnException(function(\Throwable  $e, $task){
            echo "getMessage:".$e->getMessage();
            echo PHP_EOL;
        });
        $max = 6;
        $allTask = [];
        while($max>0){
            $task = new Task(function() use ($max) {
                echo $max .PHP_EOL;



                \co::sleep(5);
                $mod = new BModel();
                //DbManager::getInstance()->startTransaction();

                $mod->taskInsert();
                //DbManager::getInstance()->rollback();
                //DbManager::getInstance()->commit();

                // 将设这是一个curl爬取任务 return 爬取结果 可以在外部获取

                return 'ok';
            });
            $runner->addTask($task);
            $allTask[] = $task;
            $max--;
        }

        // 最长执行1秒  总共投递了30个 最大并发10个 需要3秒执行完，所以会有一部分将被丢弃  看下方参数说明列表
        $runner->start(6);

        // 获取协程执行结果
        foreach($allTask as $key => $task){
            var_dump("getResult:".$task->getResult());
        }


        //只有同步调用才能返回数据
        return date('YmdHis');

        // TODO: Implement run() method.
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // TODO: Implement onException() method.
    }
}