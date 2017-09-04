<?php
namespace Database;

class Connector{

    /**
     * 生成数据库连接实例
     *
     * @Author   liuchao
     *
     * @param array $config
     * @param null  $name
     *
     * @return \Database\Connection
     */
    public function make(array $config, $name = null){
        $config = $this->parseConfig($config, $name);

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config);
    }

    /**
     * 解析数据库配置
     *
     * @Author   liuchao
     *
     * @param array $config
     * @param       $name
     *
     * @return array
     */
    protected function parseConfig(array $config, $name){
        if(!isset($config['prefix'])){
            $config['prefix'] = '';
        }
        $config['name'] = $name;
        return $config;
    }

    /**
     * 创建普通数据库连接
     *
     * @Author   liuchao
     *
     * @param array $config
     *
     * @return \Database\Connection
     */
    protected function createSingleConnection(array $config){
        $pdo = $this->createPdoResolver($config);

        return $this->createConnection(
            $pdo, $config['database'], $config['prefix'], $config
        );
    }

    /**
     * 创建读写分离的数据库连接
     *
     * @Author   liuchao
     *
     * @param array $config
     *
     * @return $this
     */
    protected function createReadWriteConnection(array $config){
        $connection = $this->createSingleConnection($this->getWriteConfig($config));

        return $connection->setReadPdo($this->createReadPdo($config));
    }

    /**
     * 创建数据库读连接
     *
     * @Author   liuchao
     *
     * @param array $config
     *
     * @return \Closure
     */
    protected function createReadPdo(array $config){
        return $this->createPdoResolver($this->getReadConfig($config));
    }

    /**
     * 获取数据库读连接的配置
     *
     * @Author   liuchao
     *
     * @param array $config
     *
     * @return array
     */
    protected function getReadConfig(array $config)
    {
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'read')
        );
    }

    /**
     * 获取数据库写连接的配置
     *
     * @Author   liuchao
     *
     * @param array $config
     *
     * @return array
     */
    protected function getWriteConfig(array $config){
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'write')
        );
    }

    /**
     * 获取一个读或写的host配置
     *
     * @Author   liuchao
     *
     * @param array $config
     * @param       $type
     *
     * @return mixed
     */
    protected function getReadWriteConfig(array $config, $type){
        return isset($config[$type][0])
            ? $config[$type][array_rand($config[$type])]
            : $config[$type];
    }

    /**
     * 获取一个读或者写的完整配置
     *
     * @Author   liuchao
     *
     * @param array $config
     * @param array $merge
     *
     * @return array
     */
    protected function mergeReadWriteConfig(array $config, array $merge){
        $config = array_merge($config, $merge);

        if(isset($config['read'])){
            unset($config['read']);
        }

        if(isset($config['write'])){
            unset($config['write']);
        }

        return $config;
    }

    /**
     * 创建一个返回PDO连接的闭包
     *
     * @Author   liuchao
     *
     * @param array $config
     *
     * @return \Closure
     */
    protected function createPdoResolver(array $config){
        return function () use ($config) {

            $args = [
                'database_type' => $config['driver'],
                'database_name' => $config['name'],
                'server' => $config['host'],
                'username' => $config['username'],
                'password' => $config['password'],
            ];

            isset($config['port']) && $args['port'] = $config['port'];
            isset($config['charset']) && $args['charset'] = $config['charset'];
            isset($config['prefix']) && $args['prefix'] = $config['prefix'];
            isset($config['logging']) && $args['logging'] = $config['logging'];
            isset($config['option']) && $args['option'] = $config['option'];
            isset($config['command']) && $args['command'] = $config['command'];

            return new \Medoo\Medoo($args);

        };
    }


    /**
     * 创建一个 connection 实例
     *
     * @Author   liuchao
     *
     * @param        $connection
     * @param        $database
     * @param string $prefix
     * @param array  $config
     *
     * @return \Database\Connection
     */
    protected function createConnection($connection, $database, $prefix = '', array $config = []){
        return new Connection($connection, $database, $prefix, $config);
    }
}
