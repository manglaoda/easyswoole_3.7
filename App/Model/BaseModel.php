<?php

namespace App\Model;

use EasySwoole\EasySwoole\Config;
use EasySwoole\Mysqli\QueryBuilder;



/**
 * Class BaseModel
 * Create With Automatic Generator
 */
class BaseModel
{

    protected $db;
    protected $client;
    protected $_tableNmae;


    function __construct()
    {
        if( empty($this->db) )
        {
            $this->createDb();
        }
        $this->table($this->tableName);
    }


    protected function createDb()
    {
        $config = new \EasySwoole\Mysqli\Config(Config::getInstance()->getConf('MYSQL'));
        $this->client = new \EasySwoole\Mysqli\Client($config);
        $this->db = $this->client->queryBuilder();
    }




    function table($tableName)
    {
        $this->_tableNmae = $tableName?:"";
        return $this;
    }



    function insert($data)
    {
        $this->_data = $data;
        $this->db->insert($this->_tableNmae, $this->_data);
        return $this->client->execBuilder();
    }




}