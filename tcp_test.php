<?php
//try{
    /**
     * Created by PhpStorm.
     * User: Tioncico
     * Date: 2019/3/6 0006
     * Time: 16:22
     */
    include "./vendor/autoload.php";
    define('EASYSWOOLE_ROOT', realpath(getcwd()));
    //\EasySwoole\EasySwoole\Core::getInstance()->initialize();
    EasySwoole\EasySwoole\Core::getInstance()->initialize()->globalInitialize();

//::: warning
//在3.3.7版本后,initialize事件调用改为:`EasySwoole\EasySwoole\Core::getInstance()->initialize()->globalInitialize();`
//:::
    /**
     * tcp 客户端2,验证数据包,并处理粘包
     */
    go(function () {
        $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
        $client->set(
            [
                'open_length_check'     => true,
                'package_max_length'    => 81920,
                'package_length_type'   => 'N',
                'package_length_offset' => 0,
                'package_body_offset'   => 4,
            ]
        );
        if (!$client->connect('127.0.0.1', 9601, 0.5)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        $str = '{"controller":"IndexTcp","action":"args","param":{"name":"\u4ed9\u58eb\u53ef"}}';
        $client->send(encode($str));
        $data = $client->recv();//服务器已经做了pack处理
        var_dump($data);//未处理数据,前面有4 (因为pack 类型为N)个字节的pack
        $data = decode($data);//需要自己剪切解析数据
        var_dump($data);
        //$client->close();
    });
//}catch (\Throwable $e){
//    var_dump('error:'.$e->getMessage());
//}




/**
 * 数据包 pack处理
 * encode
 * @param $str
 * @return string
 * @author Tioncico
 * Time: 9:50
 */
function encode($str)
{
    return pack('N', strlen($str)) . $str;
}

function decode($str)
{
    $data = substr($str, '4');
    return $data;
}