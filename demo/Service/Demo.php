<?php

namespace DubboDemo\Service;

use Dubbo\Provider\Annotations\DubboClassAnnotation;
use Dubbo\Provider\Annotations\DubboMethodAnnotation;

/**
 *
 * @DubboClassAnnotation(serviceAlias="php.dubbo.demo.DemoService")
 */
class Demo
{

    /**
     * @DubboMethodAnnotation
     */
    public function sayHello()
    {
        return "Dubbo sayHello!";
    }

    public static function dubboEntrance($method, $args, $server, $fd, $reactor_id)
    {
        $_self = new self();
        return $_self->$method($args);
    }
}