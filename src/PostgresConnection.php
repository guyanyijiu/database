<?php

namespace guyanyijiu\Database;

use guyanyijiu\Database\Query\Processors\PostgresProcessor;
use guyanyijiu\Database\Query\Grammars\PostgresGrammar as QueryGrammar;

class PostgresConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \guyanyijiu\Database\Query\Grammars\PostgresGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \guyanyijiu\Database\Query\Processors\PostgresProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }
}
