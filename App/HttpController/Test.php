<?php
namespace App\HttpController;

use App\HttpController\Api\ApiBase;
use App\Model\UserModel;

class Test extends ApiBase
{

    /**
     * @Di(key="IOC")
     */
    protected $ioc;

    /**
     * @Context(key="context")
     */
    protected $context;

    function index(){
        $this->writeJson(400, null, 'fail');
    }

    /**
     * @Param(name="account",from={GET,POST},notEmpty="不能为空")
     * @Param(name="userAccount", notEmpty="xxxx")
     * @Param(name="userPassword", notEmpty="")
     * @throws \EasySwoole\ORM\Exception\Exception
     */
    public function login()
    {
        $mod = new UserModel();
        $mod->userAccount = trim( $this->input('userAccount', ""));
        $mod->userPassword = md5( trim( $this->input('userPassword', "")));
        $info = $mod->login();
        if( $info ){
            $info['clientRealIP'] = $this->clientRealIP();
            $this->writeJson(200, $info, 'succeed');
        }else{
            $this->writeJson(400, $info, 'fail');
        }
    }





}