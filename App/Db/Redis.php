<?php
namespace App\Db;
class Redis extends \EasySwoole\Redis\Redis
{
    function __construct()
    {
        parent::__construct(new \EasySwoole\Redis\Config\RedisConfig([
            'host' => '127.0.0.1',
            'port' => '6379',
            'auth' => 'w65251842',
            'serialize' => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_NONE
        ]));
    }

}