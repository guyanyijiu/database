<?php

namespace guyanyijiu\Database;

use guyanyijiu\Support\Str;
use guyanyijiu\Database\Query\Builder;

abstract class Model
{

    /**
     * 当前连接名
     *
     * @var null
     */
    protected $connection;

    /**
     * 当前model的表名
     *
     * @var null
     */
    protected $table;

    /**
     * 是否支持自动更新时间字段
     *
     * @var bool
     */
    protected $timestamps = false;

    /**
     * 时间字段格式
     *
     * @var
     */
    protected $dateFormat;

    /**
     * 数据库连接解析类实例
     *
     * @var
     */
    protected static $resolver;

    /**
     * 创建时间字段名
     */
    const CREATED_AT = 'ctime';

    /**
     * 修改时间字段名
     */
    const UPDATED_AT = 'utime';

    /**
     * Model constructor.
     *
     * @param null $connection
     * @param null $table
     */
    public function __construct($connection = null, $table = null){
        if(!is_null($connection)){
            $this->connection = $connection;
        }
        if(!is_null($table)){
            $this->table = $table;
        }
    }

    /**
     * 生成新的查询构造器实例
     *
     * @Author   liuchao
     * @return \guyanyijiu\Database\Query\Builder
     */
    public function newQuery(){
        $builder = new Builder($this->getConnection());
        $builder->setModel($this);
        return $builder;
    }

    /**
     * 获取当前数据库连接
     *
     * @Author   liuchao
     * @return \guyanyijiu\Database\Connections\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }

    /**
     * 获取当前数据库连接名
     *
     * @Author   liuchao
     * @return null
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * 设置当前数据库连接名
     *
     * @Author   liuchao
     *
     * @param $name
     *
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * 生成一个新的数据库连接实例
     *
     * @Author   liuchao
     *
     * @param null $connection
     *
     * @return mixed
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * 设置数据库连接解析类实例
     *
     * @Author   liuchao
     *
     * @param $resolver
     */
    public static function setConnectionResolver($resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * 获取当前model表名
     *
     * @Author   liuchao
     * @return mixed|null
     */
    public function getTable()
    {
        if (! isset($this->table)) {
            return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
        }

        return $this->table;
    }

    /**
     * 设置当前model表名
     *
     * @Author   liuchao
     *
     * @param $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }


    /**
     * 代理方法调用
     *
     * @Author   liuchao
     *
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return call_user_func_array([$this, $method], $parameters);
        }

        return call_user_func_array([$this->newQuery(), $method], $parameters);
    }

    /**
     * 代理静态方法调用
     *
     * @Author   liuchao
     *
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([(new static), $method], $parameters);
    }

}
