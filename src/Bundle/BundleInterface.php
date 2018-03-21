<?php

namespace Lichee\Bundle;

use Lichee\Kernel\Container;
use Lichee\Router\RouteInterface;

/**
 * Interface BundleInterface
 * @package Lichee\Bundle
 */
interface BundleInterface
{
    /**
     * bootstrap
     * @return mixed
     */
    public function boot();

    /**
     * register
     * @return mixed
     */
    public function register();

    /**
     * @param Container $container
     * @return mixed
     */
    public function build(Container $container);

    /**
     * set container
     * @param Container $container
     * @return mixed
     */
    public function setContainer(Container $container);

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getNamespace(): string;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return mixed
     */
    public function getParent();

}
