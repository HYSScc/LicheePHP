<?php

namespace Lichee\Log;


interface LogHandlerInterface
{
    /**
     * 处理日志
     * each log in the array is processed
     * @param array $messages
     * @return mixed
     */
    public function handler(array $messages);

    /**
     * 创建日志
     * create log
     * @param $args
     * @return mixed
     */
    public function createLogBag($args);
}