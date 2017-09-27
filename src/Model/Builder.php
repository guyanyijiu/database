<?php

namespace guyanyijiu\Database\Model;

use guyanyijiu\Support\Arr;
use guyanyijiu\Database\Model;
use guyanyijiu\Database\Query\Builder as QueryBuilder;

/**
 * @mixin \guyanyijiu\Database\Query\Builder
 */
class Builder
{
    /**
     * The base query builder instance.
     *
     * @var \guyanyijiu\Database\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \guyanyijiu\Database\Model
     */
    protected $model;

    /**
     * Create a new Model query builder instance.
     *
     * @param  \guyanyijiu\Database\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * update
     *
     * @Author   liuchao
     *
     * @param array $values
     *
     * @return int
     */
    public function update(array $values)
    {
        return $this->getQuery()->update($this->addUpdatedAtColumn($values));
    }

    /**
     * insert
     *
     * @Author   liuchao
     *
     * @param array $values
     *
     * @return bool
     */
    public function insert(array $values){
        return $this->getQuery()->insert($this->addCreatedAtColumn($values));
    }

    /**
     * insertGetId
     *
     * @Author   liuchao
     *
     * @param array $values
     *
     * @return int
     */
    public function insertGetId(array $values)
    {
        return $this->getQuery()->insertGetId($this->addCreatedAtColumn($values));
    }

    /**
     * increment
     *
     * @Author   liuchao
     *
     * @param       $column
     * @param int   $amount
     * @param array $extra
     *
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->getQuery()->increment(
            $column, $amount, $this->addUpdatedAtColumn($extra)
        );
    }

    /**
     * decrement
     *
     * @Author   liuchao
     *
     * @param       $column
     * @param int   $amount
     * @param array $extra
     *
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->getQuery()->decrement(
            $column, $amount, $this->addUpdatedAtColumn($extra)
        );
    }

    /**
     * 添加修改时间字段
     *
     * @Author   liuchao
     *
     * @param array $values
     *
     * @return array
     */
    protected function addUpdatedAtColumn(array $values)
    {
        if (! $this->model->usesTimestamps()) {
            return $values;
        }

        return Arr::add(
            $values, $this->model->getUpdatedAtColumn(),
            $this->model->freshTimestampString()
        );
    }

    /**
     * 添加创建时间字段
     *
     * @Author   liuchao
     *
     * @param array $values
     *
     * @return array
     */
    protected function addCreatedAtColumn(array $values){
        if (! $this->model->usesTimestamps()) {
            return $values;
        }

        return Arr::add(
            $values, $this->model->getCreatedAtColumn(),
            $this->model->freshTimestampString()
        );
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \guyanyijiu\Database\Query\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param  \guyanyijiu\Database\Query\Builder  $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get the model instance being queried.
     *
     * @return \guyanyijiu\Database\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \guyanyijiu\Database\Model  $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->query->from($model->getTable());

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->getQuery()->{$method}(...$parameters);
    }

}
