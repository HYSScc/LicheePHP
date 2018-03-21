<?php

namespace Lichee\Service;

use Lichee\Kernel\Container;

/**
 * Class ServiceGenerator
 */
class ServiceGenerator
{
	/**
	 * @var Container
	 */
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @param $className
	 * @return mixed
	 */
	public function get($className)
	{
		if(isset($this->container['service'][$className])) {
			return $this->container['service'][$className];
		}
		return $this->generator($className);
	}

	/**
	 * @param $className
	 * @return mixed
	 */
	protected function generator($className)
	{
		$serviceInstance = new $className($this->container);
		return $serviceInstance;
	}
}