<?php

namespace guyanyijiu\Database;

use PDO;
use guyanyijiu\Database\Query\Processors\MySqlProcessor;
use guyanyijiu\Database\Query\Grammars\MySqlGrammar as QueryGrammar;

class MySqlConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \guyanyijiu\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \guyanyijiu\Database\Query\Processors\MySqlProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param  \PDOStatement $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) || is_float($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }
}
