<?php

namespace guyanyijiu\Database;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use guyanyijiu\Database\Connectors\ConnectionFactory;

class DatabaseServiceProvider implements ServiceProviderInterface{

    public function register(Container $container){

        $container['db.factory'] = function (){
            return new ConnectionFactory();
        };

        $container['db'] = function ($container){
            return new DatabaseManager($container, $container['db.factory']);
        };

        Model::setConnectionResolver($container['db']);

    }

}
