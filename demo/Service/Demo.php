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

    public function sadfa()
    {

    }
}