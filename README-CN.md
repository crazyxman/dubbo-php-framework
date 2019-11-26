[English](./README.md) | 中文

# dubbo-php-framework

dubbo-php-framework 使用Swoole实现的RPC通信框架,它与Dubbo协议完全兼容,并且可以同时作为消费者和提供者,使用Zookeeper用作服务注册发现,支持fastjson,hessian等数据序列化方式。

![arch](https://github.com/crazyxman/dubbo-php-framework/blob/master/Arch.png)

# 介绍
- provider在多进程中运行、工作进程用于处理特定的业务，管理进程控制工作进程的生命周期，主进程处理网络IO。
- agent监视注册中心中提供者地址信息的更改，并将其同步到本地内存，以供consumer使用。
- consumer与agent配合使用,通过TCP或UnixSocket从agent中获取提供者地址。

Wiki: [English](https://github.com/crazyxman/dubbo-php-framework/wiki/English).[中文](https://github.com/crazyxman/dubbo-php-framework/wiki/%E4%B8%AD%E6%96%87)
