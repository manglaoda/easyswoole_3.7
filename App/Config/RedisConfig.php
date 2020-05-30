<?php
namespace App\Config;
// redis配置
class RedisConfig
{
    // 单例
    static public function get(){
        return [
            'host' => '127.0.0.1',
            'port' => '6379',
            'auth' => 'w65251842',
        ];
    }

    // 集群
    static public function getCluster(){
        return [

        ];
    }
}




