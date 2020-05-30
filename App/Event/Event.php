<?php
namespace App\Event;

use EasySwoole\Component\Container;
use EasySwoole\Component\Singleton;

class Event extends Container
{
    use Singleton;
    function set($key, $item)
    {
        if (is_callable($item)){
            return parent::set($key, $item);
        }else{
            return false;
        }
    }

    function hook($event,...$arg){
        $call = $this->get($event);
        if (is_callable($call)){
            return call_user_func($call,...$arg);
        }else{
            return null;
        }
    }
}