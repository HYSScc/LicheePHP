<?php

namespace Lichee\Log\Handler;

use Lichee\Log\LogBag;
use Lichee\Log\LogHandlerInterface;

class FileLogHandler implements LogHandlerInterface
{
    /**
     * @var
     */
    protected $savePath;

    /**
     * FileLogHandler constructor.
     * @param string $savePath
     */
    public function __construct(string $savePath)
    {
        $this->savePath = $savePath;
    }

    /**
     * @param array $messages
     * @return array
     */
    public function handler(array $messages)
    {
        return [];
    }

    /**
     * @param $args
     * @return LogBag|__anonymous@645
     */
    public function createLogBag($args)
    {
        return (new class(...func_get_args()) extends LogBag
        {
            public function __construct($level, $message, array $data = [], array $traces = [], $time = '')
            {
                $time = $time ?: time();
                parent::__construct($level, $message, $data, $traces, $time);
            }
        });
    }

}