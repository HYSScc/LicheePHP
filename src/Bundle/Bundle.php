<?php

namespace Lichee\Bundle;

use Lichee\Kernel\Container;
use Lichee\Router\RouteInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Bundle
 * @package Lichee\Bundle
 */
abstract class Bundle implements BundleInterface
{
    /**
     * @var
     */
    protected $container;

    /**
     * @var
     */
    protected $name;

    /**
     * @var
     */
    protected $path;

    /**
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Gets the Bundle namespace.
     *
     * @return string The Bundle namespace
     */
    public function getNamespace(): string
    {
        $class = get_class($this);

        return substr($class, 0, strrpos($class, '\\'));
    }

    /**
     * @return string The Bundle absolute path
     */
    public function getPath(): string
    {
        if (null === $this->path) {
            $reflected = new \ReflectionObject($this);
            $this->path = dirname($reflected->getFileName());
        }

        return $this->path;
    }

    /**
     * @return string The Bundle name
     */
    final public function getName(): string
    {
        if (null !== $this->name) {
            return $this->name;
        }

        $name = get_class($this);
        $pos = strrpos($name, '\\');

        return $this->name = false === $pos ? $name : substr($name, $pos + 1);
    }

    /**
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->container['dispatcher'];
    }

    public function boot()
    {
        // TODO: Implement boot() method.
    }

    public function build(Container $container)
    {
        // TODO: Implement build() method.
    }

    public function getParent()
    {
        // TODO: Implement getParent() method.
    }

    public function register()
    {
        // TODO: Implement register() method.
    }

    public function setViewsBasePath(string $basePath)
    {
        // TODO: Implement setViewsBasePath() method.
    }

    public function getViewsBasePath(): string
    {
        // TODO: Implement getViewsBasePath() method.
    }

}