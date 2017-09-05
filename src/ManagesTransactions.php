<?php
namespace Database;

use Closure;
use Exception;
use Throwable;

trait ManagesTransactions
{
    /**
     * 在事务中执行一个闭包
     *
     * @Author   liuchao
     *
     * @param \Closure $callback
     * @param int      $attempts 执行次数
     *
     * @return mixed
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction();

            try {
                $result = $callback($this);
                $this->commit();
                return $result;
            }
            catch (Exception $e) {
                $this->handleTransactionException(
                    $e, $currentAttempt, $attempts
                );
            }
            catch (Throwable $e) {
                $this->rollBack();

                throw $e;
            }
        }
    }

    /**
     * 捕获事务执行中的异常
     *
     * @Author   liuchao
     *
     * @param $e
     * @param $currentAttempt
     * @param $maxAttempts
     */
    protected function handleTransactionException($e, $currentAttempt, $maxAttempts){
        if ($this->causedByDeadlock($e) && $this->transactions == 1) {
            $this->transactions = 0;

            throw $e;
        }

        $this->rollBack();

        if ($this->causedByDeadlock($e) && $currentAttempt < $maxAttempts) {
            return;
        }

        throw $e;
    }

    /**
     * 开启事务
     *
     * @Author   liuchao
     * @throws \Exception
     */
    public function beginTransaction(){
        if ($this->transactions == 0){
            try{
                $this->getPrimaryPdo()->pdo->beginTransaction();
            }
            catch(Exception $e){
                $this->handleBeginTransactionException($e);
            }
            $this->transactions = 1;
        }else{
            throw new Exception('有未提交的事务');
        }

    }

    /**
     * 捕捉事务开始的异常
     *
     * @Author   liuchao
     *
     * @param $e
     */
    protected function handleBeginTransactionException($e){
        if ($this->causedByLostConnection($e)) {
            $this->reconnect();

            $this->getPrimaryPdo()->pdo->beginTransaction();
        } else {
            throw $e;
        }
    }

    /**
     * 提交
     *
     * @Author   liuchao
     * @throws \Exception
     */
    public function commit(){
        if ($this->transactions == 1) {
            $this->getPrimaryPdo()->pdo->commit();
            $this->transactions = 0;
        }else{
            throw new Exception('无需要提交的事务');
        }
    }

    /**
     * 回滚
     *
     * @Author   liuchao
     * @throws \Exception
     */
    public function rollBack(){
        if($this->transactions == 1){
            $this->getPrimaryPdo()->pdo->rollBack();
            $this->transactions = 0;
        }else{
            throw new Exception('无需要回滚的事务');
        }

    }

}
