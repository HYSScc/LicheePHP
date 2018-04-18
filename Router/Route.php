<?php

namespace Lichee\Router;

use Lichee\Router\Validator\MatchMethod;
use Lichee\Router\Validator\MatchScheme;
use Lichee\Router\Validator\MatchUrl;

/**
 * Class Route
 * @package Lichee\Router
 */
class Route implements RouteInterface
{

    public $path;
    public $callback;
    public $methods;
    public $schemes;
    public $host;
    public $params;
    public $regex;
    public $splat;
    public $action;
    public $name=null;
    public $caseSensitive = false;
    public static $validators;

    /**
     * Route constructor.
     * @param $path
     * @param $callback
     * @param $methods
     * @param $host
     * @param $schemes
     * @param $params
     * @param $caseSensitive
     * @param $name
     */
    public function __construct($path, $callback, $methods, $host, $schemes, $params, $caseSensitive, $name)
    {
        $this->path = $path;
        $this->callback = $callback;
        $this->methods = $methods;
        $this->host = $host;
        $this->schemes = $schemes;
        $this->params = $params;
        $this->caseSensitive = $caseSensitive;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @return mixed
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return mixed
     */
    public function getSchemes(): array
    {
        return $this->schemes;
    }

    public function getSplat()
    {
        return $this->splat;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }


    /**
     * @param mixed $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @param $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param array $methods
     * @return $this
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param mixed $schemes
     * @return $this
     */
    public function setSchemes(array $schemes)
    {
        $this->schemes = $schemes;
        return $this;
    }

    /**
     * @param $regex
     * @return $this
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param $splat
     * @return $this
     */
    public function setSplat($splat)
    {
        $this->splat = $splat;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCaseSensitive(): bool
    {
        return $this->caseSensitive;
    }

    public function setCaseSensitive(bool $caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
        return $this;
    }

    /**
     *
     * @return array
     */
    public static function getValidators(): array
    {
        if (isset(static::$validators)) {
            return static::$validators;
        }
        return static::$validators = [
            new MatchMethod(),
            new MatchScheme(),
            new MatchUrl(),
        ];
    }

    /**
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return in_array('https',$this->getSchemes());
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}
