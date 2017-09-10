<?php

namespace guyanyijiu\Database;

use PDO;
use guyanyijiu\Support\Arr;
use guyanyijiu\Support\Str;
use InvalidArgumentException;
use guyanyijiu\Database\Connections\Connection;
use guyanyijiu\Database\Connectors\ConnectionFactory;

class DatabaseManager
{
    /**
     * 容器实例
     *
     * @var
     */
    protected $container;

    /**
     * 数据库连接工厂实例
     *
     * @var \guyanyijiu\Database\Connectors\ConnectionFactory
     */
    protected $factory;

    /**
     * 有效的连接
     *
     * @var array
     */
    protected $connections = [];

    /**
     * 当前实例
     *
     * @var
     */
    protected static $instance;

    /**
     * DatabaseManager constructor.
     *
     * @param                                                   $container
     * @param \guyanyijiu\Database\Connectors\ConnectionFactory $factory
     */
    public function __construct($container, ConnectionFactory $factory)
    {
        $this->container = $container;
        $this->factory = $factory;
        static::$instance = $this;
    }

    /**
     * 获取一个数据库连接
     *
     * @Author   liuchao
     *
     * @param null $name
     *
     * @return mixed
     */
    public function connection($name = null)
    {
        list($database, $type) = $this->parseConnectionName($name);

        $name = $name ?: $database;

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(
                $connection = $this->makeConnection($database), $type
            );
        }

        return $this->connections[$name];
    }

    /**
     * Parse the connection into an array of the name and read / write type.
     *
     * @param  string  $name
     * @return array
     */
    protected function parseConnectionName($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        return Str::endsWith($name, ['::read', '::write'])
                            ? explode('::', $name, 2) : [$name, null];
    }

    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return \guyanyijiu\Database\Connections\Connection
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name);

        return $this->factory->make($config, $name);
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->container['config']['database.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database [$name] not configured.");
        }

        return $config;
    }

    /**
     * Prepare the database connection instance.
     *
     * @param  \guyanyijiu\Database\Connections\Connection  $connection
     * @param  string  $type
     * @return \guyanyijiu\Database\Connections\Connection
     */
    protected function configure(Connection $connection, $type)
    {
        $connection = $this->setPdoForType($connection, $type);

        // Here we'll set a reconnector callback. This reconnector can be any callable
        // so we will set a Closure to reconnect from this manager with the name of
        // the connection, which will allow us to reconnect from the connections.
        $connection->setReconnector(function ($connection) {
            $this->reconnect($connection->getName());
        });

        return $connection;
    }

    /**
     * Prepare the read / write mode for database connection instance.
     *
     * @param  \guyanyijiu\Database\Connections\Connection  $connection
     * @param  string  $type
     * @return \guyanyijiu\Database\Connections\Connection
     */
    protected function setPdoForType(Connection $connection, $type = null)
    {
        if ($type == 'read') {
            $connection->setPdo($connection->getReadPdo());
        } elseif ($type == 'write') {
            $connection->setReadPdo($connection->getPdo());
        }

        return $connection;
    }

    /**
     * Disconnect from the given database and remove from local cache.
     *
     * @param  string  $name
     * @return void
     */
    public function purge($name = null)
    {
        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
     *
     * @param  string  $name
     * @return void
     */
    public function disconnect($name = null)
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Reconnect to the given database.
     *
     * @param  string  $name
     * @return \guyanyijiu\Database\Connections\Connection
     */
    public function reconnect($name = null)
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshPdoConnections($name);
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param  string  $name
     * @return \guyanyijiu\Database\Connections\Connection
     */
    protected function refreshPdoConnections($name)
    {
        $fresh = $this->makeConnection($name);

        return $this->connections[$name]
                                ->setPdo($fresh->getPdo())
                                ->setReadPdo($fresh->getReadPdo());
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->container['config']['database.default'];
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->container['config']['database.default'] = $name;
    }

    /**
     * Get all of the support drivers.
     *
     * @return array
     */
    public function supportedDrivers()
    {
        return ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    }

    /**
     * Get all of the drivers that are actually available.
     *
     * @return array
     */
    public function availableDrivers()
    {
        return array_intersect(
            $this->supportedDrivers(),
            str_replace('dblib', 'sqlsrv', PDO::getAvailableDrivers())
        );
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->connection(), $method], $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::$instance->connection(), $method], $parameters);
    }
}
