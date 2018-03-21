<?php

namespace Lichee\Kernel;

use FrameworkBundle\FrameworkBundle;
use http\Exception\RuntimeException;
use Lichee\Bundle\BundleInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Kernel
 * @package Lichee
 */
abstract class Kernel implements KernelInterface
{

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * whether debug method
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var array
     */
    protected $bundles = [];

    /**
     * root directory
     * @var
     */
    protected $rootDir;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @return array
     */
    abstract public function getCoreComponent(): array;

    /**
     * @return array
     */
    abstract public function getBundleClasses(): array;

    /**
     * Kernel constructor.
     * @param string $environment
     * @param bool $debug
     * @param Container $container
     */
    public function __construct(string $environment = 'prod', bool $debug = false, Container $container)
    {
        $this->environment = $environment;
        $this->debug = $debug;
        $this->rootDir = $this->getRootDir();
        $this->setContainer($container);
        $this->initTimezone();
        $this->initComponent();
        $this->registerBundles();
    }

    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        if (!$this->booted) {
            $this->container = $container;
        }
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    protected function initComponent()
    {
        $components = $this->getCoreComponent();
        foreach ($components as $id => $component) {
            $this->container[$id] = $component;
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        if (!$this->booted) {
            throw new RuntimeException('Kernel Not Boostrap');
        }
        $this->bootBundles();
        /**
         * @var $httpHandler HttpHandle
         */
        $httpHandler = $this->container['http_request_handle'];
        return $httpHandler
            ->handle($request);
    }

    protected function initTimezone()
    {
        date_default_timezone_set($this->getTimeZone());
    }

    protected function registerBundles()
    {
        foreach ($this->getBundleClasses() as $bundleClass) {
            /**
             * @var $bundle BundleInterface
             */
            $bundle = new $bundleClass;
            $bundle->setContainer($this->getContainer());
            $bundle->register();
            $this->bundles[$bundle->getName()] = $bundle;
        }
        $this->booted=true;
    }

    protected function bootBundles()
    {
        foreach ($this->getBundles() as $bundleInstance) {
            $bundleInstance->boot();
        }
    }

    /**
     * @return array
     */
    public function getBundles(): array
    {
        return $this->bundles;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (new \ReflectionClass(static::class))
            ->getShortName();
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = dirname($r->getFileName());
        }
        return $this->rootDir;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->getRootDir() . '/runtime/cache/' . $this->environment;
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->getRootDir() . '/runtime/logs';
    }

    /**
     * @return string
     */
    public function getConfigDir(): string
    {
        return $this->getRootDir() . '/config';
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return "UTF-8";
    }

    /**
     * @return string
     */
    public function getTimeZone(): string
    {
        return "Asia/Shanghai";
    }
}