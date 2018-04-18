<?php

namespace Lichee\Database;

/**
 * Interface ConnectorInterface
 * @package Lichee\Database
 */
interface ConnectorInterface
{
    /**
     * @param array $config
     * @return mixed
     */
    public function connect(array $config);
}
