<?php

namespace Lichee\Router;

use Lichee\Helpers\Arr;
use Lichee\Kernel\Container;
use Lichee\Router\Validator\MatchInterface;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use RuntimeException;

class Router implements UrlGeneratorInterface
{
	public static $allowMethods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	public static $allowScheme = ['http', 'https'];

	/**
	 * @var array
	 */
	protected $routes;

	/**
	 * @var RouteInterface
	 */
	public $route;

	public function get($uri, $callback = null)
	{
		return $this->createRoute(['GET', 'HEAD'], $uri, $callback);
	}

	public function post($uri, $callback = null)
	{
		return $this->createRoute(['POST'], $uri, $callback);
	}

	public function put($uri, $callback = null)
	{
		return $this->createRoute(['PUT'], $uri, $callback);
	}

	public function patch($uri, $callback = null)
	{
		return $this->createRoute(['PATCH'], $uri, $callback);
	}

	public function delete($uri, $callback = null)
	{
		return $this->createRoute(['DELETE'], $uri, $callback);
	}

	public function any($uri, $callback = null)
	{
		return $this->createRoute(static::$allowMethods, $uri, $callback);
	}

	public function options($uri, $callback = null)
	{
		return $this->createRoute(['OPTIONS'], $uri, $callback);
	}

	/**
	 * 创建Route
	 * @param array $methods
	 * @param $path string pattern
	 * @param $callback
	 * @param array $schemes
	 * @param string $host
	 * @param array $params
	 * @param bool $caseSensitive
	 * @param string $name
	 * @return RouteInterface
	 */
	public function createRoute(array $methods, string $path, $callback,
								array $schemes = [],
								string $host = '',
								array $params = [],
								bool $caseSensitive = false, string $name = '')
	{
		if(array_intersect($methods, static::$allowMethods) < 1) {
			throw new InvalidArgumentException('invalid parameter @methods not allow');
		}
		if(!empty($scheme) && array_intersect($schemes, static::$allowScheme)) {
			throw new InvalidArgumentException('invalid parameter @schemes not allow');
		}
		return $this->routes[] = new Route(
			$path,
			$callback,
			$methods,
			$host,
			$schemes,
			$params,
			$caseSensitive,
			$name);
	}

	/**
	 * @param $routes
	 * @return mixed
	 */
	public function loadRoues($routes)
	{
		if($routes instanceof \Closure) {
			return $routes($this);
		}
		return require $routes;
	}

	/**
	 * @param Request $request
	 * @return bool|RouteInterface
	 */
	public function matchCurrentRoute(Request $request)
	{
		if($this->route instanceof RouteInterface) {
			return $this->route;
		}
		$routes = $this->routes ?? [];
		/**
		 * @var $route RouteInterface
		 */
		foreach ($routes as $route) {
			foreach ($route::getValidators() as $validator) {
				/**
				 * @var $validator MatchInterface
				 */
				if(false == $validator->match($route, $request)) {
					continue 2;
				}
			}
			return $this->route = $route;
		}
		return false;
	}

	/**
	 * @param string $name
	 * @param array $parameters
	 * @param int $referenceType
	 * @return string
	 */
	public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
	{
		// TODO: Implement generate() method.
		return '';
	}
}