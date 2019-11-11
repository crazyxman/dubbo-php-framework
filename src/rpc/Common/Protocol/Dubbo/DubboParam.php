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

namespace Dubbo\Common\Protocol\Dubbo;


use Icecave\Collections\Collection;
use Icecave\Flax\UniversalObject;

class DubboParam
{
    /*
    const SHORT = 1;
    const INT = 2;
    const INTEGER = 2;
    const LONG = 3;
    const FLOAT = 4;
    const DOUBLE = 5;
    const STRING = 6;
    const BOOL = 7;
    const BOOLEAN = 7;
    const MAP = 8;
    */
    const ARRAYLIST = 9;
    const DEFAULT_TYPE = 10;

    const adapter = [
        /*
        self::SHORT => 'S',
        self::INT => 'I',
        self::LONG => 'J',
        self::FLOAT => 'F',
        self::DOUBLE => 'D',
        self::BOOLEAN => 'Z',
        self::STRING => 'Ljava/lang/String;',
        self::MAP => 'Ljava/util/Map;',
        */
        self::ARRAYLIST => 'Ljava/util/ArrayList;',
        self::DEFAULT_TYPE => 'Ljava/lang/Object;'
    ];

    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     *
     * @param integer $value
     * @return UniversalObject
     */
    public static function object($class, $properties)
    {
        $prop = new \stdClass;
        foreach ($properties as $key => $value) {
            $prop->$key = $value;
        }
        return new UniversalObject($class, $prop);
    }

    /**
     *
     * @param mixed $arg
     * @return string
     * @throws ConsumerException
     */
    public function argToType($param)
    {
        $type = gettype($param);
        switch ($type) {
            case 'integer':
            case 'boolean':
            case 'double':
            case 'string':
            case 'NULL':
                return self::adapter[self::DEFAULT_TYPE];
            case 'array':
                if (Collection::isSequential($param)) {
                    return self::adapter[self::ARRAYLIST];
                } else {
                    return self::adapter[self::DEFAULT_TYPE];
                }
            case 'object':
                if ($param instanceof UniversalObject) {
                    $className = $param->className();
                } else {
                    $className = get_class($param);
                }
                return 'L' . str_replace(['.', '\\'], '/', $className) . ';';
            default:
                //throw Exception
                //throw new ConsumerException("Handler for type {$type} not implemented");
        }
    }

    public function typeRefs()
    {
        $typeRefs = '';
        foreach ($this->params as $param) {
            $typeRefs .= $this->argToType($param);
        }
        return $typeRefs;
    }

    public function getParams()
    {
        return $this->params;
    }


}