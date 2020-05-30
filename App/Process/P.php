<?php
namespace App\Process;
use App\Model\PModel;
use EasySwoole\Component\Process\AbstractProcess;
use Swoole\Atomic;
use Swoole\Process;

Class P extends AbstractProcess
{
    private $taskData;
    protected function run($arg)
    {
        $this->getConfig()->setMaxExitWaitTime(10);


        // 共享内存计数器
        $atomic = new Atomic(1);
        $is = 1;
        go(function () use ($atomic, $is){
            while (1){
                \co::sleep(0.1);


                if( $atomic->get() == 0 ){
                    $is = 1;
                }elseif( $atomic->get() >= 100 ){
                    $is = 0;
                }

                if( $is ){
                    var_dump($atomic->add(1));
                }else{
                    var_dump($atomic->sub(1));
                }




                $this->taskData = null;
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
     * @return $this->taskData
     */
    protected function onShutDown()
    {
        if( $this->taskData ){

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