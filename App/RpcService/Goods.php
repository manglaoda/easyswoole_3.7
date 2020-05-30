<?php
namespace App\RpcService;

use EasySwoole\Rpc\AbstractService;

class Goods extends AbstractService
{

    public function serviceName(): string
    {
        return 'goods';
    }

    // 每秒执行一次，请自己实现间隔需求
    function onTick(){
        echo 'onTick'.PHP_EOL;
    }


    public function list()
    {

        $data = $this->request()->getArg();

        var_dump($data);


        $this->response()->setResult([
            [
                'goodsId'=>'1'
            ],
            [
                'goodsId'=>'2'
            ]
        ]);
        $this->response()->setMsg('get goods list success');
    }
}