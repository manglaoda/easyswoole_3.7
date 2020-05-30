<?php
namespace App\Db;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\Redis\Redis as RedisClient;

class RedisPool
{
    protected $redis;       // redis实例
    protected $select=0;    // rredis库号

    function __construct($select=0)
    {
        $this->select = $select;

        // 配置redis实例
        $redis = Redis::getInstance()->get('redis');
        if( empty($redis) )
        {
            $config = \App\Config\RedisConfig::get();
            $config['serialize'] = RedisConfig::SERIALIZE_NONE;
            $redisConfig = new RedisConfig($config);
            Redis::getInstance()->register('redis',$redisConfig);
        }

        // 拿到redis操作实例
        Redis::invoke('redis', function (RedisClient $redis){
            $this->redis = $redis;
        });
    }

    public function __call($name, $arguments)
    {
        $this->redis->select($this->select);
        // TODO: Implement __call() method.
        return $this->redis->$name(...$arguments);
    }


    public function __destruct(){
        //var_dump('__destruct');
    }


}