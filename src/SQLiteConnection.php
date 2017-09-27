<?php

namespace guyanyijiu\Database;

use guyanyijiu\Database\Query\Processors\SQLiteProcessor;
use guyanyijiu\Database\Query\Grammars\SQLiteGrammar as QueryGrammar;

class SQLiteConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \guyanyijiu\Database\Query\Grammars\SQLiteGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \guyanyijiu\Database\Query\Processors\SQLiteProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new SQLiteProcessor;
    }

}
