<?php

namespace App\Model;

use EasySwoole\ORM\AbstractModel;
use function PHPSTORM_META\elementType;

/**
 * Class UserModel
 * Create With Automatic Generator
 * @property $userId
 * @property $userName
 * @property $userAccount
 * @property $userPassword
 * @property $phone
 * @property $money
 * @property $addTime
 * @property $lastLoginIp
 * @property $lastLoginTime
 * @property $userSession
 * @property $state
 */
class TestModel extends AbstractModel
{
    static public $keyID;
    private $keyID2;

    function __construct(array $data = [])
    {
        parent::__construct($data);
        // 静态属性需初始化
        self::$keyID = null;
    }


    function setkeyID2()
    {
        echo $this->keyID2.PHP_EOL;
        $this->keyID2 = time();
    }


}