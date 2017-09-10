<?php

namespace guyanyijiu\Database\Connections;

use guyanyijiu\Database\Query\Processors\PostgresProcessor;
use guyanyijiu\Database\Query\Grammars\PostgresGrammar as QueryGrammar;

class PostgresConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

}
