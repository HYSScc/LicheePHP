<?php

namespace Lichee\Kernel\BaseKernel;

use Lichee\Kernel\Kernel;

/**
 * Class BaseKernel
 * @package Lichee\Kernel\BaseKernel
 */
class BaseKernel extends Kernel
{

	/**
	 * @return \Generator
	 */
	public function registerBundles()
	{
		$bundles = require $this->getConfigDir() . '/bundles.php';
		foreach ($bundles as $class => $environments) {
			if(in_array('all', $environments, true) || in_array($this->getEnvironment(), $environments, true)) {
				yield new $class();
			}
		}
	}

	/**
	 * @return array
	 */
	public function registerServiceProviders(): array
	{
		/**
		 * @var $serviceProvidersClosure \Closure
		 */
		$serviceProvidersClosure = require $this->getConfigDir() . '/ServiceProviders.php';
		$serviceProvidersClosure->bindTo($this);
		return $serviceProvidersClosure($this);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCacheDir(): string
	{
		return $this->getRootDir() . '/runtime/cache/' . $this->environment;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLogDir(): string
	{
		return $this->getRootDir() . '/runtime/logs';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConfigDir(): string
	{
		return $this->getRootDir() . '/Config';
	}
}