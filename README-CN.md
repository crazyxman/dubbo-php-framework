[English](./README.md) | 中文

# dubbo-php-framework

dubbo-php-framework 使用Swoole实现的RPC通信框架,它与Dubbo协议完全兼容,并且可以同时作为消费者和提供者,使用Zookeeper用作服务注册发现,支持fastjson,hessian等数据序列化方式。

![arch](https://github.com/crazyxman/dubbo-php-framework/blob/master/Arch.png)

# 介绍
- provider在多进程中运行、工作进程用于处理特定的业务，管理进程控制工作进程的生命周期，主进程处理网络IO。
- agent监视注册中心中提供者地址信息的更改，并将其同步到本地内存，以供consumer使用。
- consumer与agent配合使用,通过TCP或UnixSocket从agent中获取提供者地址。

# 变化
- 重写了全部代码，目录结构层次, 利于可读性,扩展。
- 引入composer进行管理加载，利于安装及作为其他框架的一个组件使用。
- 原有的agent模块由 c代码+redis 改为 纯php实现, 减少组件依赖，利于使用。
- provider,consumer,agent等配置文件互相独立,存放位置自定义。利于使用者根据自身框架类型调整位置及环境区分。
- 配置文件格式由ini改为yaml,减少冗余字段，可读性更高。
- 去除log4php内置了日志组件, 对外提供日志组件实现接口，利于用户自定义日志格式。
- provider模块引入注解可将现有代码基本无需修改即可注册为dubbo服务, 无侵入。
- swoole_server配置及回调函数用户可自定义,利于使用者根据当前应用场景优化服务。
- 消费同ip:port提供者时,保持了TCP连接。
- hessian返回的序列化数据解析后由复杂的对象转为了数组，可读性更高。
- monitor 收集的数据更加完整。

Wiki: [English](https://github.com/crazyxman/dubbo-php-framework/wiki/English).[中文](https://github.com/crazyxman/dubbo-php-framework/wiki/%E4%B8%AD%E6%96%87)
