<?php

namespace Lichee\Database;

use Lichee\Database\MySqlConnector;
use PDOException;

/**
 * Class ConnectionFactory
 * @package Database
 */
class ConnectionFactory
{

    /**
     * @param string $driver
     * @return MySqlConnector
     */
    protected function create(string $driver)
    {
        switch (strtoupper($driver)) {
            case 'MYSQL':
                return new MySqlConnector();
                break;
        }
        throw new PDOException("lack of related driver");
    }

    /**
     * @param $config
     * @return \PDO
     */
    public function createConnector($config)
    {
        return $this->create($config['driver'])->connect($config);
    }

}
