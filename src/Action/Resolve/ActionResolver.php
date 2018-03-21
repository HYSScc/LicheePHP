<?php

namespace Lichee\Action\Resolve;


use Lichee\Action\Action;
use Lichee\Kernel\Container;
use Lichee\Router\RouteInterface;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use ReflectionParameter;
use ReflectionType;
use Closure;

/**
 * Class TransformController
 * @package Lichee\Router
 */
class ActionResolver
{
	/**
	 * @var Container
	 */
	protected $container;
	/**
	 * @var RouteInterface
	 */
	protected $route;

	/**
	 * @var /Closure
	 */
	protected $callable;

	protected $actionSuffix = 'Action';

	/**
	 * TransformController constructor.
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @return string
	 */
	public function getActionSuffix(): string
	{
		return $this->actionSuffix;
	}

	/**
	 * @param string $actionSuffix
	 */
	public function setActionSuffix(string $actionSuffix)
	{
		$this->actionSuffix = $actionSuffix;
	}

	/**
	 * 将当前路由转换成可执行结构
	 * @param RouteInterface $route
	 * @return callable|Closure
	 */
	public function resolver(RouteInterface $route)
	{
		if($this->callable) {
			return $this->callable;
		}
		$callback = $route->getCallback();
		if(is_string($callback)) {
			$this->callable = $this->generatorActionInstanceCallable($route);
		} elseif($callback instanceof \Closure) {
			$this->callable = $this->generatorClosureInstanceCallable($route);
		} else {
			throw new InvalidArgumentException("Invalid Argument Route Callback,Should be give [ClassName,Closure]");
		}
		return $this->callable;
	}

	/**
	 * 根据closure生成可执行回调
	 * @param RouteInterface $route
	 * @return callable
	 */
	protected function generatorClosureInstanceCallable(RouteInterface $route)
	{
		$callback = $route->getCallback();
		$reflectionMethod = (new \ReflectionObject($callback))->getMethod('__invoke');
		$parameters = $this->getReflectionFillMethodParams($reflectionMethod, $route);
		return function ($callback) use ($parameters, $callback) {
			return $callback(...$parameters);
		};
	}

	/**
	 * 根据Http Method来获取应该执行的Action Method
	 * @return string
	 */
	protected function getExpectMethod()
	{
		$method = strtolower($this->getRequest()->getMethod());
		return sprintf("%s" . $this->actionSuffix, $method);
	}

	/**
	 * @return Request
	 */
	protected function getRequest()
	{
		return $this->container['request'];
	}

	/**
	 * 根据形参来自己捕获参数
	 * @param \ReflectionMethod $reflectionMethod Method反射
	 * @param RouteInterface $route 当前的Route
	 * @return array
	 * @throws \ReflectionException
	 */
	protected function getReflectionFillMethodParams(\ReflectionMethod $reflectionMethod, RouteInterface $route)
	{
		$methodParameters = $reflectionMethod->getParameters();
		/**
		 * 生成控制器方法的默认参数
		 * @return array
		 */
		$methodParametersMap = [];
		foreach ($methodParameters as $parameter) {
			/**
			 * @var $parameter ReflectionParameter
			 */
			$parameterValue = $parameter->getClass();
			//1 如果是类,就尝试从容器中获取服务
			if(is_object($parameterValue)) {
				if($parameterValue->isInstantiable()) {
					$className = $parameterValue->getName();
					if(isset($this->container[$className])) {
						$methodParametersMap[$parameter->getName()] = $this->container[$className];
					} else {
						$methodParametersMap[$parameter->getName()] = $className();
					}
				} else {
					throw new \ReflectionException("class@{$parameter->getName()} Cannot Instantiate");
				}
			} else {
				//2 如果是从路由中定义的,则尝试从路由中获取
				if($route->getParams()[$parameter->getName()] ?? null) {
					$methodParametersMap[$parameter->getName()] = $route->getParams()[$parameter->getName()];
				} else {
					//3 如果路由中也没有,从Request参数中获取
					$request = $this->getRequest();
					$propertyName = $request == 'GET' ? 'query' : 'request';
					if($this->getRequest()->{$propertyName}->has($parameter->getName())) {
						$methodParametersMap[$parameter->getName()] = $this->getRequest()->{$propertyName}->get($parameter->getName());
					} else {
						//4 如果Request也没有,则尝试默认参数
						if($parameter->isDefaultValueAvailable()) {
							$methodParametersMap[$parameter->getName()] = $parameter->getDefaultValue();
						} else {
							//5 如果以上皆不可 则返回null
							$methodParametersMap[$parameter->getName()] = null;
						}
					}
				}
			}
		}
		return $methodParametersMap;
	}

	/**
	 * @param RouteInterface $route
	 * @return Closure
	 */
	protected function generatorActionInstanceCallable(RouteInterface $route)
	{
		$action = $route->getCallback();
		$reflection = new \ReflectionClass($action);
		$actionMethod = $this->getExpectMethod();
		if(!$reflection->hasMethod($actionMethod)) {
			throw new InvalidArgumentException(sprintf("%s Missing Method [%s]", $action, $actionMethod));
		}
		$methodParametersMap = $this->getReflectionFillMethodParams($reflection->getMethod($actionMethod), $route);
		return function ($callback) use (
			$reflection,
			$route,
			$action,
			$actionMethod,
			$methodParametersMap
		) {
			/**
			 * @var $actionInstance Action
			 */
			$actionInstance = new $action();
			$actionInstance->setContainer($this->container);
			$actionInstance->setRoute($route);
			return $callback(['action' => $actionInstance, 'method' => $actionMethod, 'params' => $methodParametersMap]);
		};
	}

}