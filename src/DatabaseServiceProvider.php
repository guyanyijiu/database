<?php
namespace guyanyijiu\Database;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use guyanyijiu\Database\Connectors\ConnectionFactory;

class DatabaseServiceProvider implements ServiceProviderInterface{

    public function register(Container $container){
        $container['db'] = function ($container){
            return new DatabaseManager($container, new ConnectionFactory);
        };
    }

}
