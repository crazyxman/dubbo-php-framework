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

namespace Dubbo\Agent\Server;

use Dubbo\Agent\YMLParser;
use Dubbo\Agent\Registry\FilterProvider;
use Swoole\Server;
use Swoole\Coroutine;

class SwooleServer
{
    private $_ymlParser;

    private $_server;

    private $_callbackList;

    private $_registry;

    public function __construct(YMLParser $ymlParser, $callbackList)
    {
        $this->_ymlParser = $ymlParser;
        $this->_callbackList = $callbackList;
    }

    public function startup()
    {
        $this->_server = new Server('127.0.0.1', $this->_ymlParser->getServerPort(), SWOOLE_BASE);
        $this->_server->set(
            [
                'daemonize' => $this->_ymlParser->getServerDaemonize(),
            ]
        );
        $this->onWorkerStart();
        $this->onReceive();
        $this->registry();
        $this->_server->start();
    }

    private function registry()
    {
        if (isset($this->_callbackList['registry'])) {
            go(function () {
                $this->_registry = call_user_func($this->_callbackList['registry']);
                while (true) {
                    Coroutine::sleep(0.1);
                }
            });
        }
    }

    public function onWorkerStart()
    {
        $this->_server->on('WorkerStart', function (Server $server, int $worker_id) {
            swoole_set_process_name("php-dubbo-agent.{$this->_ymlParser->getApplicationName()}: master process ({$this->_ymlParser->getFilename()})");
        });
    }

    public function onReceive()
    {
        $this->_server->on('Receive', function (Server $server, int $fd, int $reactor_id, string $data) {
            $filterProvider = new FilterProvider($this->_registry);
            $provider = $filterProvider->find_provider($data);
            $server->send($fd, $provider . "\r\n\r\n");
        });
    }


}