<?php
namespace App\HttpController;
use App\Db\Redis;
use App\Db\RedisPool;
use App\Event\Event;
use App\HttpController\Api\ApiBase;
use App\Model\PModel;
use App\Model\BModel;
use App\Model\TestModel;
use App\Task\TestTask;
use EasySwoole\Annotation\Annotation;
use EasySwoole\Component\Context\ContextManager;
use EasySwoole\Component\CoroutineRunner\Runner;
use EasySwoole\Component\CoroutineRunner\Task;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Task\TaskManager;
use EasySwoole\Session\Session;
use EasySwoole\Validate\Validate;
use Swoole\Exception;


/**
 * easyswoole 功能演示
 * Class TestHttp
 * @package App\HttpController
 */
class TestHttp extends ApiBase
{

    /**
     * 回收资源（私有属性需要手动回收）
     * @var
     */
    private $keyID;
    protected function gc()
    {
        parent::gc();
        $this->keyID = null;
    }


    /**
     * 请求到达执行方法（用此方法代替__construct）
     * @param string $action
     * @return bool|null
     */
    public function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action); // TODO: Change the autogenerated stub
    }



    /**
     * 测试gc（新手禁止使用静态变量、静态方法）
     */
    function test_gc()
    {
        echo $this->keyID.PHP_EOL;
        $this->keyID = time();
    }

    /**
     * 测试class gc （新手类里面禁止使用静态变量、静态方法）
     */
    function test_class_gc()
    {
        $mod = new TestModel();
        echo $mod::$keyID.PHP_EOL;
        $mod::$keyID = time();
    }


    /**
     * 输出文本
     */
    function index(){
        $file = EASYSWOOLE_ROOT.'/Public/Http/welcome.html';
        $this->response()->write(file_get_contents($file));
    }


    /**
     * 并发测试 mysqli
     */
    function concurrency_mysqli(){
        $mod = new PModel();
        for ($i=0;$i<100;$i++){
            $mod->taskInsert();
        }
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("并发测试 mysqli");
    }

    /**
     * 并发测试 mysql orm
     */
    function concurrency_orm(){
        for ($i=0;$i<100;$i++){
            BModel::create()->taskInsert();
        }
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write('并发测试 mysql orm');
    }

    /**
     * 自定义事件
     */
    function event(){
        $result = Event::getInstance()->hook('自定义事件');
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write($result);
    }

    /**
     * 异步任务示例
     */
    function task_async(){
        $task = TaskManager::getInstance();
        $task->async(new TestTask());
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("异步任务示例");
    }

    /**
     * 同步任务示例
     */
    function task_sync(){
        $task = TaskManager::getInstance();
        $task->sync(new TestTask());
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("同步任务示例");
    }


    /**
     * 服务开启时注入mysqli，此处获取
     */
    function di_mysqli()
    {
        $client = Di::getInstance()->get('mysqli');
        $db = $client->queryBuilder();
        $db->insert('b', ['time_format'=>'xxxxxxxxxxxxxxxxxxxx']);
        $client->execBuilder();

        /** 清空指定注入 （结果：第一请求正常，第2次就找不到这个对象了） */
        //Di::getInstance()->delete('mysqli');

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("服务开启时注入mysqli，此处获取");
    }


    /**
     * 协程上下文隔离
     */
    function coroutines_text(){

        go(function (){
            ContextManager::getInstance()->set('key','key in parent');
            go(function (){
                ContextManager::getInstance()->set('key','key in sub');
                var_dump(ContextManager::getInstance()->get('key')." in");
            });
            \co::sleep(1);
            var_dump(ContextManager::getInstance()->get('key')." out");
        });
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("协程上下文隔离");
    }

    /**
     * 协程租等待 （如：2个程序，1个2秒，1个5秒，已最长的执行完成结束 | 结果：耗时5秒）
     */
    function coroutines_WaitGroup()
    {
        $ret = [];
        $wait = new \EasySwoole\Component\WaitGroup();

        $wait->add();
        go(function ()use($wait,&$ret){
            \co::sleep(5);
            $ret[] = time();
            $wait->done();
        });

        $wait->add();
        go(function ()use($wait,&$ret){
            \co::sleep(2);
            $ret[] = time();
            $wait->done();
        });

        $wait->wait();
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("协程租等待");
    }


    /**
     * 并发 cap & 超时
     */
    function coroutines_concurrency_csp(){

        $csp = new \EasySwoole\Component\Csp();
        $csp->add('t1',function (){
            \co::sleep(0.1);
            var_dump('t1 result');
            return 't1 result';
        });
        $csp->add('t2',function (){
            \co::sleep(10);
            var_dump('t2 result');
            return 't2 result';
        });

        // 设置超时（默认5秒） - 以数组形式返回超时前闭包中的返回值
        var_dump($csp->exec());
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("并发 cap & 超时");
    }


    public function moveTo($targetPath)
    {
        // TODO: Implement moveTo() method.
        return file_put_contents($targetPath,$this->stream) ? true :false;
    }

    /**
     * 上传文件
     */
    function upload_file(){

        $request=  $this->request();
        $img_file = $request->getUploadedFile('img');//获取一个上传文件,返回的是一个\EasySwoole\Http\Message\UploadFile的对象

        //var_dump($img_file->getStream());
        var_dump($img_file->getClientMediaType());

        $extArr = explode('/',$img_file->getClientMediaType());
        $ext = end($extArr);
        var_dump($ext);
        $filePath = uniqid('img_').'.'.$ext;
        var_dump($filePath);
        $result = file_put_contents($filePath, $img_file->getStream()) ? true :false;

        var_dump($result);

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("上传文件");
    }


    /**
     * 验证器 - 内部实例化写法
     */
    function validates(){
        $data = [
            'name' => 'blank',
            'age'  => 25
        ];
        $valitor = new Validate();
        $valitor->addColumn('name', '名字不为空')->required('名字不为空')->lengthMin(3,'最小长度不小于10位');
        $bool = $valitor->validate($data);
        var_dump($bool?"true":$valitor->getError()->__toString());
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("验证器");
    }


    /**
     * 验证器 - 注释写法
     * @Param(name="account",from={GET,POST},notEmpty="不能为空")
     */
    public function validate_annotation()
    {
        // 不存在报 验证器 错误
        var_dump($this->input('account', ""));
        // 对比500错误
        $mod = new BModel();
        $mod->exception_test();
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("验证器 - 注释写法");
    }


    /**
     * 验证器 - 注入参数上下文
     * @InjectParamsContext(key="data")
     * @Param(name="account",from={GET,POST},notEmpty="不能为空")
     * @Param(name="account2",from={GET,POST},notEmpty="不能为空")
     */
    public function validate_inject()
    {
        //这里获取的参数,一定是在Param中先声明好的,不会出现其他传输的参数.
        $data = ContextManager::getInstance()->get('data');
        var_dump($data);
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("验证器 - 注入参数上下文");
    }


    /**
     * 验证器 - 熔断注解
     * 设定了超时时间和超时之后的方法,还传输了一个自定义的超时时间用于测试
     * @CircuitBreaker(timeout="1.5",failAction="validate_circuitBreakerFail")
     * @Param(name="timeout",required="",between={1,5})
     */
    public function validate_circuitBreaker($timeout)
    {
        var_dump($timeout);
        \co::sleep(3);
        $this->writeJson(200,null,'success call');
    }

    public function validate_circuitBreakerFail()
    {
        $this->writeJson(200,null,'this is fail call');
    }
    /** 验证器 - 熔断注解 END */



    /**
     * easyswoole的session实现
     */
    function sessions()
    {
        if( Session::getInstance()->get('a') ){
            var_dump("a: ".Session::getInstance()->get('a'));
            var_dump(Session::getInstance()->all());
            //Session::getInstance()->writeClose();
        }else{
            Session::getInstance()->set('a',time());
            Session::getInstance()->set('b',time());
            Session::getInstance()->set('c',time());
            Session::getInstance()->set('d',time());
            Session::getInstance()->set('e',time());
            Session::getInstance()->set('s', ['a'=>1, 'b'=>2]);
        }

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("easyswoole的session实现");
    }


    /**
     * 全局变量的实现
     */
    function glb()
    {
        var_dump($_GET['a']);
        var_dump($_GET['b']);
        var_dump($_SESSION['b']);

        $_SESSION['b'] = time();
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write("全局变量的实现");
    }


    /**
     * 获取请求的服务信息
     */
    function getServerParams(){
        $data = $this->request()->getServerParams();
        var_dump($data);
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write(json_encode($data));
    }


    /**
     * redis的使用
     */
    function redis(){

        $redis = new Redis();

        $r = $redis->setEx('setEx', 10, 123);
        var_dump($r);
        $r = $redis->get('setEx');
        var_dump($r);
        var_dump($redis->clusterKeySlot('setEx'));

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write('redis的使用');
    }

    /**
     * redis-pool的使用
     */
    function redispool(){

        $redis = new RedisPool(5);

//        $r = $redis->setEx('setEx', 10, 123444);
//        var_dump($r);
//        $r = $redis->get('setEx');
//        var_dump($r);

        // 自定义命令
//        $data = $redis->rawCommand(['set','rawCommand','1']);
//        var_dump($data);
//        $data = $redis->rawCommand(['get','rawCommand']);
//        var_dump($data);


        // 开启事务
        $redis->multi();
        $redis->hset('ha1', 'a', '1');
        $redis->hset('ha2', 'b', '2');
        // 提交
        $data = $redis->exec();
        // 回滚
        //$data = $redis->discard();

        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->write('redis-pool的使用');
    }


    /**
     * 协程执行器
     */
    function CoroutineRunner(){



        $runner = new Runner(10);
        $runner->setOnException(function(\Throwable  $e, $task){
            echo "getMessage:".$e->getMessage();
            echo PHP_EOL;
        });
        $max = 10;
        $allTask = [];
        while($max>0){
            $task = new Task(function() use ($max) {
                echo $max .PHP_EOL;
                \co::sleep(1);
                // 将设这是一个curl爬取任务 return 爬取结果 可以在外部获取
                return 'ok';
            });
            $runner->addTask($task);
            $allTask[] = $task;
            $max--;
        }

        $runner->start(1);// 最长执行1秒  总共投递了30个 最大并发10个 需要3秒执行完，所以会有一部分将被丢弃  看下方参数说明列表

        foreach($allTask as $key => $task){
            var_dump("getResult:".$task->getResult());
        }
    }

    /**
     * 测试swoole_table
     * @throws \Throwable
     */
    function swooleTable()
    {
        $table = Di::getInstance()->get('zryTable');


        var_dump(count($table));
        foreach ($table as $val){
            var_dump($val);
        }

        $table->set('zry6', ['id'=>date('YmdHis'), 'name'=>'zry', 'age'=>31]);

//        // 获取指定key
//        $zryTable = $table->get('zry');
//
//        foreach ($table as $val){
//            var_dump($val);
//        }
//
//        var_dump($zryTable);
//
//        var_dump(count($table));


    }






}