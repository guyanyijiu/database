<?php

namespace Database;

use Exception;

trait DetectsDeadlocks
{
    /**
     * Determine if the given exception was caused by a deadlock.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByDeadlock(Exception $e)
    {
        $message = $e->getMessage();

        $errors = [
            'Deadlock found when trying to get lock',
            'deadlock detected',
            'The database file is locked',
            'database is locked',
            'database table is locked',
            'A table in the database is locked',
            'has been chosen as the deadlock victim',
        ];

        foreach($errors as $error){
            if(mb_strpos($message, $error) !== false){
                return true;
            }
        }
        return false;
    }
}
