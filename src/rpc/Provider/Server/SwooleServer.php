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

namespace Dubbo\Provider\Server;

use Dubbo\Common\Logger\LoggerFacade;
use Dubbo\Common\Logger\LoggerSimple;
use Dubbo\Common\YMLParser;
use Dubbo\Registry\RegistryFactory;
use Dubbo\Provider\Service;
use Dubbo\Common\Protocol\Dubbo\DubboProtocol;
use Dubbo\Monitor\MonitorFilter;
use Swoole\Lock;
use Swoole\Process;
use Swoole\Server;
use Swoole\Table;

class SwooleServer
{
    private $_config;
    private $_ymlParser;
    private $_swServer;
    private $_service;
    private $_urlSwTable;
    private $_monitorFilter;
    private $_monitorFilterLock;

    public function __construct($filename)
    {
        $ymlParser = new YMLParser($filename);
        $ymlParser->providerRequired();
        $this->_ymlParser = $ymlParser;
        $this->_config = $filename;
        LoggerFacade::setLogger(new LoggerSimple($this->_ymlParser));
        $this->_createUrlTable();
        if ($ymlParser->getMonitorProtocol()) {
            $this->_monitorFilter = new MonitorFilter($this->_ymlParser);
            $this->_monitorFilterLock = new Lock(SWOOLE_MUTEX);
        }
    }

    private function _createUrlTable()
    {
        $this->_urlSwTable = new Table(500);
        $this->_urlSwTable->column('url', Table::TYPE_STRING, 1024);
        $this->_urlSwTable->create();
    }

    private function _fillUrlToSwTable()
    {
        $process = new Process(function ($process) {
            swoole_set_process_name("php-dubbo.{$this->_ymlParser->getApplicationName()}: loadService process");
            $service = new Service(new YMLParser($this->_config));
            $service->load();
            foreach ($service->getSwooleTable() as $service => $item) {
                $this->_urlSwTable->set($service, ['url' => $item['dubboUrl']->buildUrl()]);
            }
        });
        $process->start();
        Process::wait();
        return $process;
    }

    public function startUp()
    {
        $this->_fillUrlToSwTable();
        $this->_swServer = new Server('0.0.0.0', $this->_ymlParser->getProtocolPort());
        $this->_swServer->set($this->_ymlParser->getSwooleSettings());
        $this->onStart();
        $this->onManagerStart();
        $this->onWorkerStart();
        $this->onReceive();
//        $this->onTask();
//        $this->onFinish();
        $this->onManagerStop();
        $this->_swServer->start();
    }

    public function onStart()
    {
        $this->_swServer->on('Start', function (Server $server) {
            swoole_set_process_name("php-dubbo.{$this->_ymlParser->getApplicationName()}: master process ({$this->_config})");
            echo "Server start......\n";
        });
    }

    public function onManagerStart()
    {
        $this->_swServer->on('ManagerStart', function (Server $server) {
            swoole_set_process_name("php-dubbo.{$this->_ymlParser->getApplicationName()}: manager process");
            foreach ($this->_urlSwTable as $service => $item) {
                $serviceSet[$service] = $item['url'];
            }
            $instance = RegistryFactory::getInstance($this->_ymlParser);
            $_arr = $instance->registerServiceSet($serviceSet);
            $instance->close();
            echo "Register service to the registration center:  \033[32m success:{$_arr[0]}, \033[0m \033[31m fail:{$_arr[1]} \033[0m\n";
            if (!$_arr[0]) {
                $server->shutdown();
            }
            if ($this->_monitorFilter) {
                $this->_swServer->tick(60000, function () {
                    $this->monitorCollect();
                });
            }
            echo "Start providing services\n";
        });
    }

    public function onWorkerStart()
    {
        $this->_swServer->on('WorkerStart', function (Server $server, int $worker_id) {
            swoole_set_process_name("php-dubbo.{$this->_ymlParser->getApplicationName()}: worker process");
            $this->_service = new Service(new YMLParser($this->_config));
            $this->_service->load();
        });

    }

    public function onReceive()
    {
        $this->_swServer->on('Receive', function (Server $server, int $fd, int $reactor_id, string $data) {
            $result = $monitorKey = '';
            try {
                $startTime = getMillisecond();
                $protocol = new DubboProtocol();
                $decoder = $protocol->unpackRequest($data);
                if ($protocol->getHeartBeatEvent()) {
                    $result = $this->_service->returnHeartBeat($protocol);
                    goto _result;
                }
                if ($this->_monitorFilter) {
                    $monitorKey = $decoder->getServiceName() . '/' . $decoder->getMethod();
                }
                $result = $this->_service->invoke($protocol, $decoder, $server, $fd, $reactor_id);
                if ($monitorKey) {
                    goto _success;
                }
            } catch (\Exception $exception) {
                $result = $this->_service->returnException($protocol, (string)$exception);
                if ($monitorKey) {
                    goto _fail;
                }
            }
            if (false) {
                _success:
                $this->_monitorFilter->incrCount($monitorKey);
                $this->_monitorFilter->incrSuccess($monitorKey);
                $this->_monitorFilter->incrElapsed($monitorKey, $startTime);
                $this->_monitorFilter->incrOutput($monitorKey, $protocol->getLen());
                goto _result;
                _fail:
                $this->_monitorFilter->incrCount($monitorKey);
                $this->_monitorFilter->incrFailure($monitorKey);
                $this->_monitorFilter->incrElapsed($monitorKey, $startTime);
            }
            _result:
            $server->send($fd, $result);
        });
    }

//    public function onTask()
//    {
//        $this->_swServer->on('Task', function (Server $serv, int $task_id, int $src_worker_id, $data) {
//
//        });
//    }
//
//    public function onFinish()
//    {
//        $this->_swServer->on('Finish', function (Server $serv, int $task_id, string $data) {
//
//        });
//    }

    public function onManagerStop()
    {
        $this->_swServer->on('ManagerStop', function (Server $serv) {
            $instance = RegistryFactory::getInstance($this->_ymlParser);
            foreach ($this->_urlSwTable as $service => $item) {
                $instance->destroyService($service, $item['url']);
            }
            $instance->close();
        });
    }

    public function monitorCollect()
    {
        try {
            $parameters = [
                'application' => $this->_ymlParser->getApplicationName(),
                'provider' => $this->_ymlParser->getProtocolHost() . ':' . $this->_ymlParser->getProtocolPort()
            ];
            $this->_monitorFilter->collect($parameters);
        } catch (\Exception $exception) {
            LoggerFacade::getLogger()->error("Monitor exception: ", $exception);
        }
    }


}