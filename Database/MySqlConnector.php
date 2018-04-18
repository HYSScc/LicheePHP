<?php

namespace Lichee\Database;

use PDO;

/**
 * Class MySqlConnector
 * @package Lichee\Database
 */
class MySqlConnector extends Connector implements ConnectorInterface
{
    /**
     * @param  array $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);
        $options = $this->getOptions($config);
        $connection = $this->createConnection($dsn, $config, $options);

        if (isset($config['unix_socket'])) {
            $connection->exec("use `{$config['database']}`;");
        }

        $charset = $config['charset'];
        $names = "set names '$charset'" .
            (!empty($config['collation']) ? " collate '{$config['collation']}' " : '');

        $connection->prepare($names)->execute();

        if (isset($config['timezone'])) {
            $connection->prepare(
                'set time_zone="' . $config['timezone'] . '"'
            )->execute();
        }

        $this->setModes($connection, $config);

        return $connection;
    }

    /**
     * @param  array $config
     * @return string
     */
    protected function getDsn(array $config): string
    {
        return $this->configHasSocket($config) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
    }

    /**
     * @param array $config
     * @return bool
     */
    protected function configHasSocket(array $config): bool
    {
        return isset($config['unix_socket']) && !empty($config['unix_socket']);
    }

    /**
     * @param array $config
     * @return string
     */
    protected function getSocketDsn(array $config): string
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * @param array $config
     * @return string
     */
    protected function getHostDsn(array $config): string
    {
        return isset($config['port'])
            ? "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}"
            : "mysql:host={$config['host']};dbname={$config['database']}";
    }

    /**
     * @param PDO $connection
     * @param array $config
     */
    protected function setModes(PDO $connection, array $config)
    {
        if (isset($config['modes'])) {
            $modes = implode(',', $config['modes']);
            $connection->prepare("set session sql_mode='" . $modes . "'")->execute();

        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'")->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }
}
