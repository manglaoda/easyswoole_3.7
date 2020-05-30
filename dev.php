<?php
return [
    'DEBUG'   =>  TRUE,     //标明当前为调试模式
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9501,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8,
            'reload_async' => true,
            'max_wait_time'=>3,
            //'max_connection'    =>  10000, // 允许的最大链接数，超过将被拒绝
        ],
        'TASK'=>[
            'workerNum'=>4,
            'maxRunningNum'=>128,
            'timeout'=>15
        ]
    ],
    'TEMP_DIR' => null,
    'LOG_DIR' => null,

    // 单独端口TCP
    'ALONE_TCP'   =>  [
        'default'   =>  [
            'ip'    =>  '0.0.0.0',
            'port'  =>  9601,
        ],
    ],



    // 静态文件访问（需先在nginx中指向此目录）

    /**
     * nginx 配置示例
         server {
            root /home/wwwroot/default/easyswoole/Static/;
            server_name zry.life;
            location / {
                proxy_http_version 1.1;
                proxy_set_header Connection "keep-alive";
                proxy_set_header X-Real-IP $remote_addr;
                if (!-f $request_filename) {
                    proxy_pass http://127.0.0.1:9501;
                }
            }
         }
     **/
    'document_root' => EASYSWOOLE_ROOT.'/Static/',
    'enable_static_handler' => true,


    // mysql
    'MYSQL'  => [
        'host'          => '127.0.0.1',
        'port'          => 3306,
        'user'          => 'root',
        'password'      => 'root',
        'database'      => 'zry_blogs',
        'timeout'       => 5,
        'charset'       => 'utf8mb4',
    ],
];
