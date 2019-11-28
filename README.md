English | [中文](./README-CN.md)

# dubbo-php-framework

dubbo-php-framework is a RPC communication framework for PHP language. It is fully compatible with Dubbo protocol, and can be used as provider terminal and consumer terminal simultaneously. Using zookeeper for service registration discovery, and using fastjson and hessian for Serialization

![arch](https://github.com/crazyxman/dubbo-php-framework/blob/master/Arch.png)

# Introduction
- php provider runs in multiple processes. The worker process is used to process specific business, the manager process controls the lifecycle of the worker process, and the master process processes the network IO.
- Agent monitors the change of provider address information in registry and synchronizes them to local memory for all php consumers on the machine to share
- consumer、 agent are deployed on all consumer machines and communicate with each other on unix socket or TCP socket
provider is deployed on all provider machines to control the lifecycle of all php providers on that machine

Wiki: [中文](https://github.com/crazyxman/dubbo-php-framework/wiki/%E4%B8%AD%E6%96%87)
