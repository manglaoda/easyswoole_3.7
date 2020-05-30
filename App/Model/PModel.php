<?php

namespace App\Model;


/**
 * Class PModel
 * Create With Automatic Generator
 */
class PModel extends BaseModel
{

    protected $tableName  = 'p';

    protected $primaryKey = 'id';

    const STATE_PROHIBIT = 0; // 禁用状态
    const STATE_NORMAL   = 1; // 正常状态

    function taskInsert()
    {
        $data = ['time_format'=>date('Y-m-d H:i:s')];
        return $this->insert($data);
    }




}