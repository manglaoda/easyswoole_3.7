<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2019/3/29 0029
 * Time: 10:45
 */
namespace App\HttpController\Api;

use App\HttpController\BaseController;
use EasySwoole\EasySwoole\Core;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Http\Exception\ParamAnnotationValidateError;
use EasySwoole\Http\Message\Status;
use EasySwoole\HttpAnnotation\Exception\Annotation\ParamValidateError;

abstract class ApiBase extends BaseController
{
//    public function index()
//    {
//        // TODO: Implement index() method.
//        $this->actionNotFound('index');
//    }

    // 路由查询失败触发
    protected function actionNotFound(?string $action): void
    {
        $this->writeJson(404, null, 'Url访问路径有误');
    }

    // 加载路由触发
    public function onRequest(?string $action): ?bool
    {
        if (!parent::onRequest($action)) {
            return false;
        }
        return true;
    }

    // 程序执行完时触发
    public function afterAction(?string $action): void
    {
        //回收逻辑
    }



    protected function onException(\Throwable $throwable): void
    {
        //$throwable instanceof ParamAnnotationValidateError
        if ($throwable instanceof ParamValidateError) {

            $validate = $throwable->getValidate();
            $errorMsg = $validate->getError()->getErrorRuleMsg();
            $errorCol = $validate->getError()->getField();
            $this->writeJson(400,null,"字段{$errorCol}：{$errorMsg}");

        } else {
            if (Core::getInstance()->isDev()) {
                $this->writeJson(500, null, $throwable->getMessage());
            } else {
                Trigger::getInstance()->throwable($throwable);
                $this->writeJson(500, null, '系统内部错误，请稍后重试');
            }
        }
    }
}