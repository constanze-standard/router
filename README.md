# ConstanzeStandard Route

[![GitHub license](https://img.shields.io/github/license/alienwow/SnowLeopard.svg)](https://github.com/alienwow/SnowLeopard/blob/master/LICENSE)
[![LICENSE](https://img.shields.io/badge/license-Anti%20996-blue.svg)](https://github.com/996icu/996.ICU/blob/master/LICENSE)
[![Coverage 100%](https://img.shields.io/azure-devops/coverage/swellaby/opensource/25.svg)](https://github.com/constanze-standard/route)

一个轻量级的 php 路由

constanze-standard-route 只提供一个路由所需的基本的功能。是一个相对轻量级且高效率的组件。

## 安装
> composer require constanze-standard/route

* [关键字](#关键字)
* [开始使用](#开始使用)
  * [添加 route 信息](#添加-route-信息)
  * [根据附加数据查询 route](#根据附加数据查询-route)
* [请求派发](#请求派发)
  * [1) 匹配成功](#1-匹配成功)
  * [2) 未匹配成功](#2-未匹配成功)
  * [3) URL 匹配，Http Method 不匹配的情况](#3-url-匹配http-method-不匹配的情况)
* [Collector 缓存](#collector-缓存)
* [一个完整的案例](#一个完整的案例)

## 工作原理
constanze-standard/route 由线路集合 (route collection) 与派发器 (dispatcher) 组成。在实践中，收集器 (collector) 收集 route，生成集合，然后由 dispatcher 派发请求，并返回对应的路由信息。

## 关键字
1. route: 线路，包含了一组预存信息，包括： URL 匹配模式、处理程序、附带数据和 URL 参数。
2. collection: 集合，在这里指路由集合。是一个抽象概念，集合中包含多个 route, 用于 route 数据的统一处理。
3. collector: 收集器，在这里指 route 收集器。是组件内 collection 的默认实现。可以直接使用。
4. dispatcher: 派发器，主要用于派发请求，根据请求信息在 collection 中查询符合条件的 route，并返回 route 信息或相关的匹配错误信息。

## 开始使用
首先，我们需要构建 collection, collection 组件需要实现 `ConstanzeStandard\Route\Interfaces\CollectionInterface` 中的接口，这里我们使用默认的 `ConstanzeStandard\Route\Collector` 组件。

### 添加 route 信息
```php
use ConstanzeStandard\Route\Collector;

$collector = new Collector();
$collector->attach('GET', '/user/{id}', function() {
    return 'bar';
}, 'additional data');
```
用 `ConstanzeStandard\Route\Collector::attach` 方法向 Collector 中添加一条 route 信息，也就是attach 方法接收的 4 个参数：
1. methods: 请求的 http 方法。这个参数可以是字符串或数组，代表本条 route 支持的一个或多个 http method.
2. pattern: URL 的匹配模式。pattern 支持解析 URL 中的参数，如 `/user/{id}`, `{id}` 位置对应的内容将会被提取出来，并在命中请求时返回。
3. handler: 处理程序。在常见的路由场景中，往往需要预存一个 route 的处理程序，它可以是 一个闭包, 函数或数组。
4. data: 绑定在 route 上的附加数据，理论上可以是任何类型的数据，但如果你要使用 collector 的缓存机制，则 data 必须符合 var_export 函数对数据的要求。

### 根据附加数据查询 route
`ConstanzeStandard\Route\Collector` 提供了 `getRoutesByData` 方法，用于根据 route 的附加数据获取一个或多个 route 信息。这对路由定位应用很有帮助。
```php
...
list($pattern, $handler, $data, $variables) = $collector->getRoutesByData(['name' => 'Alex']);
// []
```
`getRoutesByData` 方法的第一个参数接收一个字符串或只包含字符串的数组，Collector 会将它作为查询条件，查找附加数据中包含这些数据的 route。如果参数类型为字符串，则匹配的 key 为 `0`。

如果你希望返回符合条件的第一条 route，则设置 `getRoutesByData` 方法的第二个参数为 `true`，否则将会返回所有符合条件的 route 的数组。

### 请求派发
Dispatcher 对象必须实现 `ConstanzeStandard\Route\Interfaces\DispatcherInterface` 中的方法。这里使用默认的 `ConstanzeStandard\Route\Dispatcher`.

```php
use ConstanzeStandard\Route\Dispatcher;
...

$dispatcher = new Dispatcher($collector);
$result = $dispatcher->dispatch('GET', '/user/10');
```
`Dispatcher` 的初始化需要传入一个 collection 实例作为匹配的数据源，将当前的 http method 和 URL 传入 `dispatch` 方法中即可开始匹配符合要求的数据。

`dispatch` 方法的返回值根据匹配状态的不同，分为三种情况：
#### 1) 匹配成功
如果匹配成功将会返回一个包含状态和 route 信息的数组，形如：
```php
use ConstanzeStandard\Route\Dispatcher;

// 匹配成功的 dispatch 方法返回值
[Dispatcher::STATUS_OK, $handler, $data, $params];
```
返回的数组按固定顺序，包括：
1. 匹配成功的状态码：`Dispatcher::STATUS_OK`.
2. route 处理程序。
3. 附加数据
4. URL 中提取到的参数 (i.e. ['id' => 10]).

#### 2) 未匹配成功
```php
use ConstanzeStandard\Route\Dispatcher;

[Dispatcher::STATUS_ERROR, Dispatcher::ERROR_NOT_FOUND];
```
当 collection 中没有一条与 URL 相匹配的 route 时，则会返回数组项：
1. 匹配错误的状态码：`Dispatcher::STATUS_ERROR`.
2. 错误类型：`Dispatcher::ERROR_NOT_FOUND`

#### 3) URL 匹配，Http Method 不匹配的情况
```php
use ConstanzeStandard\Route\Dispatcher;

[Dispatcher::STATUS_ERROR, Dispatcher::ERROR_ALLOWED_METHODS, ['GET', 'POST']];
```
Dispatcher 在查询一条 route，如果 URL 符合条件，则会进一步检查 Http Method, 这时，如果 Method 不匹配，并且剩余的 route 均不匹配，则会返回数组向：
1. 匹配错误的状态码：`Dispatcher::STATUS_ERROR`.
2. 错误类型：`Dispatcher::ERROR_ALLOWED_METHODS`
3. 这个 URL 所支持的 Http Method.

### Collector 缓存
每一次请求，都会向 Collector 逐条添加 route，消耗较大，所以可以将 Collector 的数据缓存在一个文件中，每次从缓存文件整个读取数据即可。

通过设置 `withCache` 选项开启 Collector 缓存：
```php
use ConstanzeStandard\Route\Collector;

$collector = new Collector([
    'withCache' => __DIR__ . '/cache_file.php'
]);
```
`withCache` 选项的默认值是 false（不使用缓存）。我们将 `withCache` 设置为缓存文件的路径开启缓存。当缓存文件存在时，collector 从缓存文件中读取数据，当缓存文件不存在时，collector 会创建缓存文件，并将数据写入文件。所以缓存文件所在的目录必须可写。

collector 缓存一定程度上缓解了数据装载时的消耗，但 handler 将 `Closure` 作为必要的数据类型无法被缓存，所以 collector 每次仍需构建一份 `[id => handler]` 形式的数组。

### 一个完整的案例
```php
use ConstanzeStandard\Route\Collector;
use ConstanzeStandard\Route\Dispatcher;

$collector = new Collector([
    'withCache' => __DIR__ . '/cache_file.php'
]);

$collector->attach('GET', '/user/{id}', function() {
    return 'bar';
}, ['name' => 'user']);

$dispatcher = new Dispatcher($collector);
$result = $dispatcher->dispatch('GET', '/user/10');

if ($result[0] == Dispatcher::STATUS_OK) {
    list($status, $handler, $data, $params) = $result;
} elseif ($result[0] == Dispatcher::ERROR) {
    $errorType = $result[1];
    // errors process.
}
```

Happy Hacking!
