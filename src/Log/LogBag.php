<?php

namespace Lichee\Log;


class LogBag
{
    /**
     * 日志级别
     * @var string
     */
    public $level;

    /**
     * 消息
     * @var string
     */
    public $message;

    /**
     * 日志数据
     * @var array
     */
    public $data;

    /**
     * 时间戳
     * @var string
     */
    public $time;

    /**
     * 追溯
     * @var array
     */
    public $traces;

    /**
     * 存储路径
     * @var array
     */
    public $savePath;

    /**
     * LogBag constructor.
     * @param string $level
     * @param string $message
     * @param array $data
     * @param string $time
     * @param array $traces
     */
    public function __construct($level, $message, $data = [], $traces = [], $time = '')
    {
        $this->level = (int)$level;
        $this->message = (string)$message;
        $this->data = (array)$data;
        $this->time = $time;
        $this->traces = (array)$traces;
    }
}