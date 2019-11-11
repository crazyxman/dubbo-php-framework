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

namespace Dubbo\Registry\Client;

use Dubbo\Common\Logger\LoggerFacade;
use Dubbo\Common\YMLParser;
use \Zookeeper;

class ZookeeperClient
{
    private $_handle;
    private $_rootNode = '/dubbo';
    private $_providersNode = 'providers';

    public function __construct(YMLParser $ymlParser)
    {
        $clusterIp = $ymlParser->getRegistryAddress();
        $this->_handle = new Zookeeper($clusterIp);
    }

    public function registerServiceSet($serviceSet)
    {
        $succCount = 0;
        $failCount = 0;
        foreach ($serviceSet as $serviceName => $url) {
            $path = $this->registerService($serviceName, $url);
            if ($path) {
                $succCount++;
            } else {
                $failCount++;
            }
        }
        return [$succCount, $failCount];
    }

    public function registerService($serviceName, $url)
    {
        $path = $this->_rootNode . '/' . $serviceName . '/' . $this->_providersNode . '/' . $url;
        if (!$this->_handle->exists($path)) {
            $path = $this->_createPath($path);
            if ($path) {
                LoggerFacade::getLogger()->info("Register.  ", $path);
            } else {
                LoggerFacade::getLogger()->error("Register fail. ", $path);
            }
        }
        return $path;
    }

    public function destroyService($serviceName, $url)
    {
        $path = $this->_rootNode . '/' . $serviceName . '/' . $this->_providersNode . '/' . $url;
        if ($this->_handle->exists($path)) {
            $this->_handle->delete($path);
            LoggerFacade::getLogger()->info("Unregister.", $path);
        }
    }

    private function _createPath($path)
    {
        if (!$this->_handle->exists($path)) {
            $prevPath = $this->_createPath(substr($path, 0, strrpos($path, '/')));
            if (!$prevPath) {
                return false;
            }
            $path = $this->_handle->create($path, null, [['perms' => Zookeeper::PERM_ALL, 'scheme' => 'world', 'id' => 'anyone']]);
            if (!$path) {
                LoggerFacade::getLogger()->error("Create path fail. ", $path);
                return false;
            }
        }
        return $path;
    }

    public function getChildren($path)
    {
        if (!$this->_handle->exists($path)) {
            LoggerFacade::getLogger()->warn("getChildren() path no exists. ", $path);
        }
        return $this->_handle->getChildren($path);
    }

    public function close()
    {
        $this->_handle->close();
    }


}