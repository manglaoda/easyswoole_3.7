<?php
namespace EasySwoole\EasySwoole;
use App\Crontab\TestTask;
use App\Exception\ExceptionHandler;
use App\Process\P;
use App\Process\B;

use App\Task\TestTimerTask2;
use App\TcpController\Parser;
use App\WebSocket\WebSocketParser;
use EasySwoole\Component\Di;
use EasySwoole\Component\Process\Manager;
use EasySwoole\EasySwoole\Crontab\Crontab;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Http\GlobalParamHook;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\Session\Session;
use EasySwoole\Session\SessionFileHandler;
use Swoole\Coroutine\Scheduler;
use EasySwoole\Queue\Driver\Redis;

use EasySwoole\RedisPool\RedisPool;
use EasySwoole\Rpc\NodeManager\RedisManager;
use EasySwoole\Rpc\Config as RpcConfig;
use EasySwoole\Rpc\Rpc;

use App\RpcService\Goods;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');


        /** 自定义事件 - 调用示例：Event::getInstance()->hook('test') */
        \App\Event\Event::getInstance()->set('test', function () {
            $s =  'test event';
            echo $s;
            return $s;
        });

        /** @var $config 数据库 */
        $config = new \EasySwoole\ORM\Db\Config(Config::getInstance()->getConf('MYSQL'));
        $config->setMaxObjectNum(100);//配置连接池最大数量
        $config->setMaxIdleTime(10); // mysql一定时间没有操作，自动断开
        $config->setAutoPing(5);    // 防止自动断开，定时做一次检测，保持mysql活跃
        DbManager::getInstance()->addConnection(new Connection($config));

        // 创建一个协程调度器（连接池隔离）
        $scheduler = new Scheduler();
        $scheduler->add(function () {

            # mysql
            $builder = new QueryBuilder();
            $builder->raw('select version()');
            DbManager::getInstance()->query($builder, true);
            //这边重置ORM连接池的pool,避免链接被克隆岛子进程，造成链接跨进程公用。
            //DbManager如果有注册多库链接，请记得一并getConnection($name)获取全部的pool去执行reset
            //其他的连接池请获取到对应的pool，然后执行reset()方法
            DbManager::getInstance()->getConnection()->getClientPool()->reset();

            # redis

            # mongo

        });
        //执行调度器内注册的全部回调
        $scheduler->start();
        //清理调度器内可能注册的定时器，不要影响到swoole server 的event loop
        \Swoole\Timer::clearAll();


        /** @var $config 注入mysqli配置 */
        $config = new \EasySwoole\Mysqli\Config(Config::getInstance()->getConf('MYSQL'));
        Di::getInstance()->set('mysqli', new \EasySwoole\Mysqli\Client($config));



    }

    public static function mainServerCreate(EventRegister $register)
    {
        // 获取配置
        $pattern = Config::getInstance()->getConf('DEBUG');

//        if( $pattern ){ // 开发模式
//            // 热启动
//            $hotReloadOptions = new \EasySwoole\HotReload\HotReloadOptions;
//            $hotReload = new \EasySwoole\HotReload\HotReload($hotReloadOptions);
//            $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App']);
//            $server = ServerManager::getInstance()->getSwooleServer();
//            $hotReload->attachToServer($server);
//        }else{   // 生产模式
//
//        }

        /** SQL 链接预热 （当分发任务时提前准备好数据库接入）*/
        $register->add($register::onWorkerStart,function (){
            DbManager::getInstance()->getConnection()->getClientPool()->keepMin();
        });



        /** 注册进程任务 （时间粒度 - 不间断循环） */
        // P
        $processConfig = new \EasySwoole\Component\Process\Config();
        $processConfig->setProcessName('P');//设置进程名称
        $processConfig->setProcessGroup('Test');//设置进程组
        $processConfig->setEnableCoroutine(true);//是否自动开启协程
        Manager::getInstance()->addProcess(new P($processConfig));
//        // B
//        $processConfig = new \EasySwoole\Component\Process\Config();
//        $processConfig->setProcessName('B');//设置进程名称
//        $processConfig->setProcessGroup('Test');//设置进程组
//        $processConfig->setEnableCoroutine(true);//是否自动开启协程
//        Manager::getInstance()->addProcess(new B($processConfig));


        /** 定时器 （时间粒度 - 毫秒）*/
//        $register->add(EventRegister::onWorkerStart, function (\swoole_server $server, $workerId) {
//            //如何避免定时器因为进程重启而丢失
//            if ($workerId == 0) {
//                //例如在第一个进程 添加一个N秒的定时器
//                \EasySwoole\Component\Timer::getInstance()->loop(3 * 1000, function () {
//                    // 从数据库，或者是redis中，去获取下个就近N秒内需要执行的任务
//                    \EasySwoole\Component\Timer::getInstance()->after(1 * 1000, function () {
//
//                        // 调用异步任务（暂不确定这行代码的规范性）
//                        TaskManager::getInstance()->async(new \App\Task\TestTimerTask2());
//
//                        //为了防止因为任务阻塞，引起定时器不准确，把任务给异步进程处理
//                        Logger::getInstance()->console("time 2", false);
//                    });
//                });
//            }
//        });


        /** 定时任务计划 （时间粒度 - 分） */
//        Crontab::getInstance()->addTask(TestTask::class);




        /** session自定义 */
        //可以自己实现一个标准的session handler
        $handler = new SessionFileHandler(EASYSWOOLE_TEMP_DIR);
        //表示cookie name   还有save path
        Session::getInstance($handler,'easy_session','session_dir');


        /** 注册全局变量  -  $_GET | $_SESSION | $_POST | $_COOKIE */
        $handler = new SessionFileHandler(EASYSWOOLE_TEMP_DIR);
        GlobalParamHook::getInstance()->hookDefault();
        //如果不需要session请勿注册
        GlobalParamHook::getInstance()->hookSession($handler,'easy_session','session_dir');



        /** 开启一个单独端口 TCP 服务 */
        $alone_tcp = $pattern = Config::getInstance()->getConf('ALONE_TCP');
        if( isset($alone_tcp['default']) )
        {
            $server = ServerManager::getInstance()->getSwooleServer();
            $subPort3 = $server->addListener($alone_tcp['default']['ip'], $alone_tcp['default']['port'], SWOOLE_TCP);

            $socketConfig = new \EasySwoole\Socket\Config();
            $socketConfig->setType($socketConfig::TCP);
            // 解包
            $socketConfig->setParser(new Parser());
            // 设置解析异常时的回调,默认将抛出异常到服务器
            $socketConfig->setOnExceptionHandler(function ($server, $throwable, $raw, $client, $response) {

                echo '报错：'.$throwable->getMessage().PHP_EOL;
                echo "tcp服务  fd:{$client->getFd()} 发送数据异常 \n";
                $server->close($client->getFd());
            });
            $dispatch = new \EasySwoole\Socket\Dispatcher($socketConfig);

            $subPort3->on('receive', function (\swoole_server $server, int $fd, int $reactor_id, string $data) use ($dispatch) {
                echo "tcp服务  fd:{$fd} 发送消息:{$data}\n";
                $dispatch->dispatch($server, $data, $fd, $reactor_id);
            });
            $subPort3->set(
                [
                    'open_length_check'     => true,    // 是否验证数据包
                    'package_max_length'    => 81920,   // 最大包长度
                    'package_length_type'   => 'N',     // 打包方式 pack('N')
                    'package_length_offset' => 0,
                    'package_body_offset'   => 4,
                ]
            );
            $subPort3->on('connect', function (\swoole_server $server, int $fd, int $reactor_id) {
                echo "tcp服务  fd:{$fd} 已连接\n";
            });
            $subPort3->on('close', function (\swoole_server $server, int $fd, int $reactor_id) {
                echo "tcp服务  fd:{$fd} 已关闭\n";
            });

            /** 客户端请求测试代码 */
//            //请求文件
//            set_time_limit(0);
//            //IP
//            $host = "127.0.0.1";
//            //端口
//            $port = 9601;
//            //发送内容
//            $data = '{"controller":"IndexTcp","action":"index","param":{"name":"\u4ed9\u58eb\u53ef"}}';
//            $data = pack('N', strlen($data)) . $data;
//            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)or die("Could not create  socket\n"); // 创建一个Socket
//            $connection = socket_connect($socket, $host, $port) or die("Could not connet server\n");  // 连接
//            socket_write($socket, $data) or die("fasongyichang\n"); // 数据传送 向服务器发送消息
//            while ($buff = @socket_read($socket, 1024, PHP_NORMAL_READ)) {
//                echo("收到返回:" . $buff . "\n");
//            }
//            socket_close($socket);
        }

        /** 开启一个单独端口 UDP 服务 */

        $server = ServerManager::getInstance()->getSwooleServer();
        $subPort = $server->addListener('0.0.0.0','9602',SWOOLE_UDP);
        $subPort->on('packet',function (\swoole_server $server, string $data, array $client_info){
            var_dump($data);
        });

        /** 测试swoole table */
        $table = new \swoole_table(1024*64, 0.2);
        $table->column('id', $table::TYPE_INT,10);
        $table->column('name', $table::TYPE_STRING,32);
        $table->column('age', $table::TYPE_INT,3);
        $table->create();
        Di::getInstance()->set('zryTable', $table);


        /** RPC服务 */

        //定义节点Redis管理器
        $redisConfig = \App\Config\RedisConfig::get();
        $redisPool = new RedisPool(new RedisConfig([
            'host'  =>  $redisConfig['host'],
            'port'  =>  $redisConfig['port'],
            'auth'  =>  $redisConfig['auth'],
        ]));

        $manager = new RedisManager($redisPool);
        //配置Rpc实例
        $config = new RpcConfig();
        //这边用于指定当前服务节点ip，如果不指定，则默认用UDP广播得到的地址
        $config->setServerIp('0.0.0.0');
        $config->setListenPort(9901);
        $config->setNodeManager($manager);
        //配置初始化
        Rpc::getInstance($config);
        //添加服务
        Rpc::getInstance()->add(new Goods());
        Rpc::getInstance()->attachToServer(ServerManager::getInstance()->getSwooleServer());


        // TODO: Implement mainServerCreate() method.
    }

    public static function onRequest(Request $request, Response $response): bool
    {

        /** session自定义 - 响应cookie */
        $cookie = $request->getCookieParams('easy_session');
        if(empty($cookie)){
            $sid = Session::getInstance()->sessionId();
            $response->setCookie('easy_session',$sid);
        }else{
            Session::getInstance()->sessionId($cookie);
        }

        /** 注册全局变量 - 加入请求与响应数据 */
        GlobalParamHook::getInstance()->onRequest($request,$response);


        /** 测试zryTable */
        Di::getInstance()->get('zryTable')->set('zry', ['id'=>date('YmdHis'), 'name'=>'zry', 'age'=>31]);
        Di::getInstance()->get('zryTable')->set('zry2', ['id'=>date('YmdHis'), 'name'=>'zry2', 'age'=>31]);
        Di::getInstance()->get('zryTable')->set('zry3', ['id'=>date('YmdHis'), 'name'=>'zry3', 'age'=>31]);
        Di::getInstance()->get('zryTable')->set('zry4', ['id'=>date('YmdHis'), 'name'=>'zry4', 'age'=>31]);
        Di::getInstance()->get('zryTable')->set('zry5', ['id'=>date('YmdHis'), 'name'=>'zry5', 'age'=>31]);










        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }


    /**
     * 注册进程任务
     * @param string $pName
     * @param string $gName
     * @param $mod
     */


}