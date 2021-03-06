# phalcon-project
[![Total Downloads](https://poser.pugx.org/limingxinleo/phalcon-project/downloads)](https://packagist.org/packages/limingxinleo/phalcon-project)
[![Latest Stable Version](https://poser.pugx.org/limingxinleo/phalcon-project/v/stable)](https://packagist.org/packages/limingxinleo/phalcon-project)
[![Latest Unstable Version](https://poser.pugx.org/limingxinleo/phalcon-project/v/unstable)](https://packagist.org/packages/limingxinleo/phalcon-project)
[![License](https://poser.pugx.org/limingxinleo/phalcon-project/license)](https://packagist.org/packages/limingxinleo/phalcon-project)


[Phalcon 官网](https://docs.phalconphp.com/zh/latest/index.html)

[wiki](https://github.com/limingxinleo/simple-subcontrollers.phalcon/wiki)

## 安装
1. 安装项目
~~~
composer create-project limingxinleo/thrift-go-phalcon-project
~~~
2. 使用Composer安装Thrift扩展后，把go的扩展包拷贝到GOPATH中(或建立软连接)。
~~~
ln -s  /your/path/to/thrift-go-phalcon-project/vendor/apache/thrift/lib/go/thrift thrift
~~~
3. 编译服务 
- Go 使用 thrift -r --gen go:thrift_import=thrift App.thrift
- Php 使用 thrift -r --gen php:server,psr4 App.thrift

4. Go服务安装
~~~
从GO官网下载编译好的压缩包 例如 go1.8.3.linux-amd64.tar.gz
$ tar -xzf go1.8.3.linux-amd64.tar.gz
$ mv go /usr/local/go/1.8.3
$ vim /etc/profile 
export GOROOT='/usr/local/go/1.8.3' # 没有文件夹则新建
export GOPATH='/usr/local/go/libs/' # 没有文件夹则新建
export PATH=$GOROOT/bin:$PATH
$ go get -u github.com/kardianos/govendor
$ cd /usr/local/go/libs/src/github.com/kardianos/govendor/
$ go build
$ cd /usr/local/bin
$ ln -s /usr/local/go/libs/src/github.com/kardianos/govendor/govendor govendor
~~~

## Go&Swoole RPC 服务
* Go
thrift/gen-go/main.go
~~~
# RPC服务注册方法
server.RegisterProcessor("app", service.NewAppProcessor(&impl.App{}));
~~~

* Swoole
app/tasks/Thrift/Service.php
~~~
$handler = new AppHandler();
$processor->registerProcessor('app', new AppProcessor($handler));
~~~

## 服务实现
* Swoole
app/thrift/Services/AppHandle.php
~~~php
<?php 
namespace App\Thrift\Services;

use MicroService\AppIf;

class AppHandler extends Handler implements AppIf
{
    public function version()
    {
        return $this->config->version;
    }

}
~~~

## 负载均衡
- Nginx Stream负载均衡已经十分强大了，自带健康检查。[TCP负载均衡](https://github.com/limingxinleo/note/blob/master/nginx/nginx.md#tcp负载均衡)

## 服务发现
1. 项目本人已内置基于Thrift的注册中心功能
- 已实现Swoole服务注册中心
- 已实现Go服务注册中心

2. 或者配合[注册中心](https://github.com/limingxinleo/service-registry-swoole-phalcon.git)一起使用
app/tasks/Thrift/ServiceTask.php
~~~
protected function beforeServerStart(swoole_server $server)
{
    parent::beforeServerStart($server); // TODO: Change the autogenerated stub

    // 增加服务注册心跳进程
    $worker = new swoole_process(function (swoole_process $worker) {
        $client = new swoole_client(SWOOLE_SOCK_TCP);
        if (!$client->connect(env('REGISTRY_IP'), env('REGISTRY_PORT'), -1)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        swoole_timer_tick(5000, function () use ($client) {
            $service = env('REGISTRY_SERVICE', 'github');
            $data = [
                'service' => $service,
                'ip' => env('SERVICE_IP'),
                'port' => env('SERVICE_PORT'),
                'nonce' => time(),
                'register' => true,
                'sign' => 'xxx',
            ];

            $client->send(json_encode($data));
            $result = $client->recv();

            $result = json_decode($result, true);
            if ($result['success']) {
                foreach ($result['services'] as $key => $item) {
                    Redis::hset($service, $key, json_encode($item));
                }
            }
        });
    });

    $server->addProcess($worker);
}

~~~


## Thrift 数据类型
1. 基本类型（括号内为对应的Java类型）：
~~~
bool（boolean）: 布尔类型(TRUE or FALSE)
byte（byte）: 8位带符号整数
i16（short）: 16位带符号整数
i32（int）: 32位带符号整数
i64（long）: 64位带符号整数
double（double）: 64位浮点数
string（String）: 采用UTF-8编码的字符串
~~~

2. 特殊类型（括号内为对应的Java类型）
~~~
binary（ByteBuffer）：未经过编码的字节流
~~~

3. Structs（结构）：
~~~
struct UserProfile {
    1: i32 uid,
    2: string name,
    3: string blurb
}

struct UserProfile {
    1: i32 uid = 1,
    2: string name = "User1",
    3: string blurb
}
~~~

4. 容器，除了上面提到的基本数据类型，Thrift还支持以下容器类型：

> list(java.util.ArrayList)
> set(java.util.HashSet)
> map（java.util.HashMap）

~~~
struct Node {
    1: i32 id,
    2: string name,
    3: list<i32> subNodeList,
    4: map<i32,string> subNodeMap,
    5: set<i32> subNodeSet
}

struct SubNode {
    1: i32 uid,
    2: string name,
    3: i32 pid
}

struct Node {
    1: i32 uid,
    2: string name,
    3: list<subNode> subNodes
}
~~~

5. 服务
~~~
service UserStorage {
    void store(1: UserProfile user),
    UserProfile retrieve(1: i32 uid)
}
~~~

