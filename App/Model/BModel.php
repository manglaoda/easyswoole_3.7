<?php
namespace App\Model;

/**
 * Class PModel
 * Create With Automatic Generator
 */
class BModel extends BaseOrmModel
{

    protected $tableName  = 'b';

    protected $primaryKey = 'id';

    const STATE_PROHIBIT = 0; // 禁用状态
    const STATE_NORMAL   = 1; // 正常状态

    function taskInsert($tag="")
    {
        $data = ['time_format'=>date('Y-m-d H:i:s')."_".$tag];
        $result = $this->data($data)->save();
        return $result;
    }


    function exception_test(){
        throw new \Exception('XXXX');
    }


    /**
     * @Param(name="a",from={GET,POST},notEmpty="不能为空1")
     * @Param(name="b",from={GET,POST},notEmpty="不能为空2")
     * @throws \EasySwoole\ORM\Exception\Exception
     */
    function exception_test2($a,$b){


    }


}