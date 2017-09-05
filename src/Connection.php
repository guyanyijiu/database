<?php
namespace Database;

use Closure;

class Connection
{
    /**
     * 事务支持
     */
    use DetectsDeadlocks;
    use DetectsLostConnections;
    use ManagesTransactions;

    /**
     * 主PDO连接
     *
     * @var
     */
    protected $primaryPdo;

    /**
     * 用于读操作的PDO连接
     *
     * @var
     */
    protected $readPdo;

    /**
     * 数据库
     *
     * @var string
     */
    protected $database;

    /**
     * 表前缀
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * 配置
     *
     * @var array
     */
    protected $config = [];

    /**
     * 开启事务
     *
     * @var int
     */
    protected $transactions = 0;

    /**
     * 重连方法
     *
     * @var
     */
    protected $reconnector;

    /**
     * Connection constructor.
     *
     * @param        $primaryPdo
     * @param string $database
     * @param string $tablePrefix
     * @param array  $config
     */
    public function __construct($primaryPdo, $database = '', $tablePrefix = '', array $config = []){
        $this->primaryPdo = $primaryPdo;

        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;

    }


    /**
     * 获取读操作的PDO连接
     *
     * @Author   liuchao
     *
     * @param bool $useReadPdo
     *
     * @return \PDO
     */
    protected function getPdoForRead($useReadPdo = true){
        return $useReadPdo ? $this->getReadPdo() : $this->getPrimaryPdo();
    }

    /**
     * 断开PDO连接
     *
     * @Author   liuchao
     */
    public function disconnect(){
        $this->setPrimaryPdo(null)->setReadPdo(null);
    }


    /**
     * 获取主PDO连接
     *
     * @Author   liuchao
     * @return mixed
     */
    public function getPrimaryPdo(){
        if ($this->primaryPdo instanceof Closure) {
            return $this->primaryPdo = call_user_func($this->primaryPdo);
        }

        return $this->primaryPdo;
    }

    /**
     * 获取读PDO连接
     *
     * @Author   liuchao
     * @return mixed
     */
    public function getReadPdo(){
        //事务强制使用写库
        if ($this->transactions >= 1) {
            return $this->getPrimaryPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ? $this->readPdo : $this->getPrimaryPdo();
    }

    /**
     * 设置一个主PDO连接
     *
     * @Author   liuchao
     *
     * @param $pdo
     *
     * @return $this
     */
    public function setPrimaryPdo($pdo){
        $this->transactions = 0;

        $this->primaryPdo = $pdo;

        return $this;
    }

    /**
     * 设置一个读PDO连接
     *
     * @Author   liuchao
     *
     * @param $pdo
     *
     * @return $this
     */
    public function setReadPdo($pdo){
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * 获取连接名
     *
     * @Author   liuchao
     * @return mixed
     */
    public function getName(){
        return $this->getConfig('name');
    }

    /**
     * 设置重连的操作
     *
     * @Author   liuchao
     *
     * @param callable $reconnector
     *
     * @return $this
     */
    public function setReconnector(callable $reconnector){
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * 重连
     *
     * @Author   liuchao
     * @return mixed
     */
    public function reconnect(){
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * 获取当前连接的配置
     *
     * @Author   liuchao
     *
     * @param null $option
     *
     * @return mixed
     */
    public function getConfig($option = null){
        return $option ? $this->config[$option] : $this->config;
    }

    /**
     * 获取当前连接的数据库名
     *
     * @Author   liuchao
     * @return string
     */
    public function getDatabaseName(){
        return $this->database;
    }

    /**
     * 获取表前缀
     *
     * @Author   liuchao
     * @return string
     */
    public function getTablePrefix(){
        return $this->tablePrefix;
    }

    /**
     * 支持的读写方法
     *
     * @Author   liuchao
     *
     * @param $method
     *
     * @return bool|string
     */
    protected function isReadOrWrite($method){
        $read = [
            'where',
            'select',
            'get',
            'has',
            'count',
            'max',
            'min',
            'avg',
            'sum',
            'query',
        ];

        $write = [
            'insert',
            'update',
            'delete',
            'replace',
        ];

        if(in_array($method, $read)){
            return 'read';
        }

        if(in_array($method, $write)){
            return 'write';
        }

        return false;
    }

    /**
     * 事务操作
     *
     * @Author   liuchao
     *
     * @param callable $callback
     * @param int      $attempts
     */
    public function action(callable $callback, $attempts = 1){
        $this->transaction($callback, $attempts);
    }

    /**
     * 这里代理 medoo 支持的方法
     *
     * @Author   liuchao
     *
     * @param $method
     * @param $parameters
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $parameters){
        $checkMethod = $this->isReadOrWrite($method);

        if(! $checkMethod){
            throw new \BadMethodCallException($method);
        }

        if($this->transactions == 1 || $checkMethod == 'write'){
            $pdo = $this->getPrimaryPdo();
        }else{
            $pdo = $this->getPdoForRead();
        }

        $result = call_user_func_array([$pdo, $method], $parameters);
        $error = $pdo->error();
        if($error[1] || $error[2]){
            throw new \Exception($error[0] . ' : ' . $error[2], $error[1]);
        }
        return $result;
    }

    /**
     * 这里代理 medoo 可访问的属性
     *
     * @Author   liuchao
     *
     * @param $key
     *
     * @return mixed
     */
    public function __get($key){
        if($key == 'pdo'){
            return $this->getPrimaryPdo()->pdo;
        }
    }

}
