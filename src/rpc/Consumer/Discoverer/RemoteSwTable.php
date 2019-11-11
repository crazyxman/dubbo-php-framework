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

namespace Dubbo\Consumer\Discoverer;

use Dubbo\Common\Client\SwooleClient;
use Dubbo\Common\DubboException;
use Dubbo\Common\YMLParser;

class RemoteSwTable
{
    private $_timeout = 0.5; //default timeout

    private $_sw_client;

    private $_ymlParser;

    public function __construct(YMLParser $ymlParser)
    {
        $host = $ymlParser->getDiscovererHost();
        $port = $ymlParser->getDiscovererPort();
        $sw_client = new SwooleClient($host, $port, $this->_timeout);
        $sw_client->setDiscoverer();
        $this->_sw_client = $sw_client;
        $this->_ymlParser = $ymlParser;
    }

    public function getProviders($service, $config)
    {
        $retry = $this->_ymlParser->getDiscovererRetry();
        $host = $this->_ymlParser->getDiscovererHost();
        $port = $this->_ymlParser->getDiscovererPort();
        do {
            if ($this->_sw_client->connect()) {
                break;
            }
        } while ($retry-- > 0);
        if (!$this->_sw_client->isConnected()) {
            throw new DubboException("Discoverer cannot connect to {$host}:{$port}");
        }
        $filter = ($config['group'] ?? '-') . ':' . ($config['version'] ?? '-');
        if (!$this->_sw_client->send($service . '|' . $filter)) {
            $this->_sw_client->close();
            throw new DubboException("Discoverer send() data fail!");
        }
        $content = $this->_sw_client->recv();
        $this->_sw_client->close();
        if (false === $content) {
            throw new DubboException("Discoverer recv() data fail!");
        }
        return json_decode($content, true);

    }
}