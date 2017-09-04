<?php

namespace Database;

use Pimple\Container;

class DatabaseManager{

    /**
     * 容器实例
     *
     * @var
     */
    protected $container;

    /**
     * 数据库连接者实例
     *
     * @var
     */
    protected $connector;

    /**
     * 已经实例化的连接
     *
     * @var array
     */
    protected $connections = [];


    /**
     * DatabaseManager constructor.
     *
     * @param $container
     * @param $connector
     */
    public function __construct(Container $container, Connector $connector){
        $this->container = $container;
        $this->connector = $connector;
    }

    /**
     * 获取一个数据库连接实例
     *
     * @Author   liuchao
     *
     * @param null $name
     *
     * @return mixed
     */
    public function connection($name = null){
        $name = $name ? $name : $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $connection = $this->makeConnection($name);

            $connection->setReconnector(function ($connection) {
                $this->reconnect($connection->getName());
            });

            $this->connections[$name] = $connection;
        }

        return $this->connections[$name];
    }


    /**
     * 生成一个数据库连接
     *
     * @Author   liuchao
     *
     * @param $name
     *
     * @return mixed
     */
    protected function makeConnection($name){

        $config = $this->configuration($name);

        return $this->connector->make($config, $name);
    }

    /**
     * 获取一个数据库的配置
     *
     * @Author   liuchao
     *
     * @param $name
     *
     * @return mixed
     */
    protected function configuration($name){
        $name = $name ? $name : $this->getDefaultConnection();

        $connections = $this->container['config']['database.connections'];

        if (!in_array($name, $connections)) {
            throw new \InvalidArgumentException("Database [$name] not configured.");
        }

        return $connections[$name];
    }


    /**
     * 断开指定连接并清除连接实例缓存
     *
     * @Author   liuchao
     *
     * @param null $name
     */
    public function purge($name = null){
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    /**
     * 断开指定连接
     *
     * @Author   liuchao
     *
     * @param null $name
     */
    public function disconnect($name = null){
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * 重连指定连接
     *
     * @Author   liuchao
     *
     * @param null $name
     *
     * @return mixed
     */
    public function reconnect($name = null){
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshConnections($name);
    }

    /**
     * 刷新指定连接
     *
     * @Author   liuchao
     *
     * @param $name
     *
     * @return mixed
     */
    protected function refreshConnections($name){
        $fresh = $this->makeConnection($name);

        return $this->connections[$name]
            ->setPrimaryPdo($fresh->getPrimaryPdo())
            ->setReadPdo($fresh->getReadPdo());
    }

    /**
     * 获取默认的连接名
     *
     * @Author   liuchao
     * @return mixed
     */
    public function getDefaultConnection(){
        return $this->container['config']['database.default'];
    }

    /**
     * 设置默认的数据库连接名
     *
     * @Author   liuchao
     *
     * @param $name
     */
    public function setDefaultConnection($name){
        $this->app['config']['database.default'] = $name;
    }

    /**
     * 获取所有已创建的连接
     *
     * @Author   liuchao
     * @return array
     */
    public function getConnections(){
        return $this->connections;
    }

    /**
     * 代理connection的调用
     *
     * @Author   liuchao
     *
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters){
        return call_user_func_array([$this->connection(), $method], $parameters);
    }
}
