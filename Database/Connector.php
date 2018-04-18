<?php

namespace Lichee\Database;

use PDO;
use PDOException;


/**
 * Class Connector
 * @package Lichee\Database
 */
abstract class Connector
{

    /**
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,        //强制列名为指定的大小写
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//设置错误报告
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,//将返回的空字符串转换为NULL
        PDO::ATTR_STRINGIFY_FETCHES => false,       //提取的时候将数值转换为字符串
        PDO::ATTR_EMULATE_PREPARES => false,        //是否使用PHP本地模拟SQL预处理,
        PDO::ATTR_TIMEOUT => 30,                      //连接超时
    ];

    /**
     * @param  string $dsn
     * @param  array $config
     * @param  array $options
     * @return \PDO
     */
    public function createConnection(string $dsn, array $config, array $options): PDO
    {
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
        return $pdo;
    }

    /**
     * @param array $config
     * @return array|mixed
     */
    public function getOptions(array $config): array
    {
        if (isset($config['options']) && is_array($config['options'])) {
            $this->options = $config['options'] + $this->options;
        }
        ksort($this->options);
        return $this->options;
    }

    /**
     * @param array $options
     * @return array
     */
    public function getDefaultOptions(array $options): array
    {
        $this->options = $options;
    }

}
