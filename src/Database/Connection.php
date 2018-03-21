<?php

namespace Lichee\Database;

use PDO;
use PDOStatement;
use Closure;
use Exception;
use ErrorException;
use Lichee\Database\ConnectionFactory;
use PDOException;

final class Connection
{
    /**
     * @var PDO
     */
    protected $connector;

    /**
     * @var int
     */
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * @var array
     */
    protected $selector = array();

    /**
     * 查询需要的参数的key
     * @var array
     */
    protected $selectorKeys = [
        'sql' => 'sql',
        'params' => 'params',
        'callback' => 'callback',
    ];

    /**
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var array
     */
    protected $config;

    /**
     * Connection constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param ConnectionFactory $connectionFactory
     */
    public function setConnectionFactory(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param int $fetchMode
     */
    public function setFetchMode(int $fetchMode)
    {
        $this->fetchMode = $fetchMode;
    }

    /**
     * @param $insertSql
     * @param array $params
     * @return mixed
     * @throws ErrorException
     */
    public function insert($insertSql, $params = [])
    {
        $this->command($insertSql, $params)->exec();
        return $this->getInsertId();
    }

    /**
     * @param $insertSql
     * @param array $params
     * @return mixed
     */
    public function delete($insertSql, $params = [])
    {
        return $this->command($insertSql, $params)->exec();
    }

    /**
     * @param $insertSql
     * @param array $params
     * @return mixed
     */
    public function update($insertSql, $params = [])
    {
        return $this->command($insertSql, $params)->exec();
    }

    /**
     * @param $selectSql
     * @param array $params
     * @return $this
     */
    public function selectAll($selectSql, $params = [])
    {
        return $this->command($selectSql, $params)
            ->fetchAll();
    }

    /**
     * @param $selectSql
     * @param array $params
     * @return mixed
     */
    public function selectOne($selectSql, $params = [])
    {
        return $this->command($selectSql, $params)
            ->fetch();
    }

    /**
     * @param $selectSql
     * @param array $params
     * @param int $number
     * @return mixed
     */
    public function selectColumn($selectSql, $params = [], $number = 0)
    {
        return $this->command($selectSql, $params)
            ->column($number);
    }

    /**
     * @param $selectSql
     * @param array $params
     * @return $this
     */
    public function command($selectSql, $params = [])
    {
        $selectorCallable = function (PDOStatement $sth, callable $callback) use ($params) {
            $sth->setFetchMode($this->fetchMode);
            $sth->execute($params);
            return call_user_func($callback, $sth);
        };
        $this->selector = [
            $this->selectorKeys['sql'] => $selectSql,
            $this->selectorKeys['params'] => $params,
            $this->selectorKeys['callback'] => $selectorCallable
        ];
        return $this;
    }

    /**
     * @return mixed
     */
    public function exec()
    {
        return $this->run(function (PDOStatement $sth) {
            return $sth->rowCount();
        });
    }

    /**
     * @return mixed
     */
    public function fetchAll()
    {
        return $this->run(function (PDOStatement $sth) {
            return $sth->fetchAll();
        });
    }

    /**
     * @param int $number
     * @return mixed
     * @throws ErrorException
     */
    public function column(int $number = 0)
    {
        return $this->run(function (PDOStatement $sth) use ($number) {
            return $sth->fetchColumn($number);
        });
    }

    /**
     * @return mixed
     */
    public function fetch()
    {
        return $this->run(function (PDOStatement $sth) {
            return $sth->fetch();
        });
    }

    /**
     * @param $key
     * @return mixed|string
     */
    protected function parseSelector($key)
    {
        return $this->selector[$key];
    }

    /**
     * @param callable $callback
     * @return mixed
     * @throws PDOException
     */
    private function run(callable $callback)
    {
        try {
            $pdo = $this->getConnector();
            $sql = $this->parseSelector('sql');
            $selectorCallable = $this->parseSelector('callback');
            $pdoStatement = $pdo->prepare($sql);
            $queryResult = call_user_func($selectorCallable, $pdoStatement, $callback);
            return $queryResult;
        } catch (\PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    protected function clearSelector()
    {
        $this->selector = null;
    }

    /**
     * @return mixed
     */
    public function getInsertId()
    {
        return $this->callPdoMethod(function (PDO $pdo) {
            return $pdo->lastInsertId();
        });
    }

    /**
     * @param callable $callable
     */
    public function transaction(callable $callable)
    {
        try {
            $isCommit = true;
            $this->begin();
            call_user_func($callable, $this);
        } catch (Exception $e) {
            $isCommit = false;
        } finally {
            $isCommit === true ? $this->commit() : $this->rollabck();
        }
    }

    public function begin()
    {
        return $this->callPdoMethod(function (PDO $pdo) {
            $pdo->beginTransaction();
        });
    }

    /**
     * @return mixed
     */
    public function rollback()
    {
        return $this->callPdoMethod(function (PDO $pdo) {
            $pdo->rollBack();
        });
    }

    /**
     * @return mixed
     */
    public function commit()
    {
        return $this->callPdoMethod(function (PDO $pdo) {
            $pdo->commit();
        });
    }

    /**
     * @param callable $callable
     * @return mixed
     */
    protected function callPdoMethod(callable $callable)
    {
        return call_user_func($callable, $this->getConnector());
    }

    /**
     * @return \PDO
     */
    public function getConnector()
    {
        if (!empty($this->connector)) {
            return $this->connector;
        }
        return $this->connector = $this->connectionFactory->createConnector($this->getConfig());
    }

    /**
     * @return array
     */
    public function defaultConfig(): array
    {
        return [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'test',
            'prefix' => '',
            'charset' => 'utf8',
            'collation' => '',
            'strict' => false,
            'username' => 'root',
            'password' => '',
        ];
    }
}