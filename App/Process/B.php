<?php
namespace App\Process;
use App\Db\RedisPool;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\Redis\Config\RedisConfig;
use Swoole\Process;

Class B extends AbstractProcess
{
    private $taskData;
    protected function run($arg)
    {
        $this->getConfig()->setMaxExitWaitTime(10);
        var_dump(getmypid());
        go(function (){
            while (1){
                \co::sleep(0.1);

                try{
                    // 队列逻辑代码
//                    $redis = new RedisPool();
//                    $rs = $redis->rPush('rPush', date('Y-m-d H:i:s'));
//                    $rs = $redis->lPop('rPush');
//                    var_dump($rs);
//                    if( $rs != NULL ){
//
//
//                    }
                }catch (\Throwable $e){}
            }
        });
    }

    /**
     * 该回调可选
     * 当有主进程对子进程发送消息的时候，会触发的回调，触发后，务必使用
     * $process->read()来读取消息
     */
    protected function onPipeReadable(Process $process)
    {

    }

    /**
     * 该回调可选
     * 当该进程退出的时候，会执行该回调
     */
    protected function onShutDown()
    {
        var_dump('start onShutDown at '.time());
        if($this->taskData){
            var_dump('there is task left ,wait save exit');
            var_dump('start save task data at '.time());
            //模拟task data 保存现场
            co::sleep(3);
            var_dump('exit and  save with task data '.$this->taskData);
        }else{
            var_dump('exit without task data at'.time());
        }
    }


    /**
     * 该回调可选
     * 当该进程出现异常的时候，会执行该回调
     */
    protected function onException(\Throwable $throwable, ...$args)
    {

    }

}