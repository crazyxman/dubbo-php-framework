<?php
/*
  +----------------------------------------------------------------------+
  | dubbo-php-framework                                                        |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.0 of the Apache license,    |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.apache.org/licenses/LICENSE-2.0.html                      |
  +----------------------------------------------------------------------+
  | Author: Jinxi Wang  <1054636713@qq.com>                              |
  +----------------------------------------------------------------------+
*/

//usage: php Consumer.php

define("VENDOR_DIR", __DIR__ . '/../../../');

include VENDOR_DIR . "/autoload.php";

use Dubbo\Consumer\DubboConsumer;
use Dubbo\Common\Protocol\Dubbo\DubboParam;

/*
//Custom logger
use Dubbo\Common\Logger\LoggerInterface;
use Dubbo\Common\Logger\LoggerFacade;
class CustomLogger implements LoggerInterface {}
LoggerFacade::setLogger(new CustomLogger());
 */

$consumerConfig = __DIR__ . '/../src/rpc/Config/ConsumerConfig.yaml';
$instance = DubboConsumer::getInstance($consumerConfig);
$service = $instance->loadService('php.dubbo.demo.DemoService');
$res = $service->invoke('sayHello', ['a' => 'b'], [1, 3]);
var_dump($res);

/*

// php call java, When the argument is an object
$service = $instance->loadService('com.imooc.springboot.dubbo.demo.ObjectDemoService');
$res = $service->invoke('sayHello',
    DubboParam::object(
        'com.imooc.springboot.dubbo.demo.dto.TestObjectDemo',
        [
            "name" => "Tom",
            "age" => 30,
            'bigDecimal' => DubboParam::object('java.lang.Object', ['value' => 15.6])
        ])
);

 */
