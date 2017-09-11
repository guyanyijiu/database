## Database

基于 Illuminate\Database 修改而来，为了使用 laravel 一样优雅的语法、又简单轻量：

1. 支持 Illuminate\Database 的所有查询构造器语法
2. 去除了schema event 功能，简化了类调用
3. Model类不再作为 ORM 模型，只有和查询构造器一样的功能
4. 兼容PHP5.5

### 用法

目前并不能单独使用，依赖容器和配置加载类

```php
use Pimple\Container;
use guyanyijiu\Database\DatabaseServiceProvider;

$container = new Container();

//需要自己实现一个 config 实例用来获取数据库配置
$container['config'] = function (){
    return new Config();
};

$container->register(new DatabaseServiceProvider());

```

> `composer require pimple/pimple` 这里使用的容器类


获取 db 实例，然后就可以用 laravel 一样的语法来操作数据库了
