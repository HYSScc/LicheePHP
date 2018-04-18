<?php

namespace Lichee\Log;

use Lichee\Log\LogBag;

/**
 * Class Logger
 * @package Lichee\Log
 */
class Logger
{

    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const FLUSH_LIMIT = 500;

    /**
     * 存储日志
     * storage Lichee\Log\LogBag instance
     * @var array
     */
    protected $messages = [];

    /**
     * 日志处理类
     * handler class
     * @var LogHandlerInterface
     */
    protected $handler;

    /**
     * 加载驱动
     * Logger constructor.
     * @param LogHandlerInterface $handler
     */
    public function __construct(LogHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->init();
    }

    protected function init()
    {
        register_shutdown_function(function () {
            $this->handler->handler($this->messages);
        });
    }

    /**
     * @param $level
     * @param $message
     * @param $data
     * @return mixed
     */
    protected function log($level, $message, $data)
    {
        $this->messages[count($this->messages)] = $this->handler->createLogBag(...func_get_args());
        return $this->messages[count($this->messages) - 1];
    }

    /**
     * record application traces
     * @param $message
     * @param $data
     * @return mixed
     */
    public function debug($message, $data)
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (count($traces) >= 2) {
            array_pop($traces);
        }
        $arguments = func_get_args();
        $arguments[] = array_filter(array_map(function ($item) {
            unset($item['type']);
            if (isset($item['class']) && $item['class'] == self::class) {
                return false;
            }
            return $item;
        }, $traces));
        return $this->log(self::LEVEL_DEBUG, ...$arguments);
    }

    /**
     * record warning
     * @param $message
     * @param $data
     * @return mixed
     */
    public function warning($message, $data)
    {
        return $this->log(self::LEVEL_WARNING, ...func_get_args());
    }

    /**
     * record information
     * @param $message
     * @param $data
     * @return mixed
     */
    public function info($message, $data)
    {
        return $this->log(self::LEVEL_INFO, ...func_get_args());
    }

    /**
     * record error
     * @param $message
     * @param $data
     * @return mixed
     */
    public function error($message, $data)
    {
        return $this->log(self::LEVEL_ERROR, ...func_get_args());
    }

    public function __destruct()
    {
        $this->messages = [];
    }

}
