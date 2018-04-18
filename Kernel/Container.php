<?php

namespace Lichee\Kernel;

use ArrayAccess;

/**
 * Class Container
 * @package Lichee\Kernel
 */
class Container implements ArrayAccess
{
    protected $values = [];
    protected $aliases = [];

    public function __construct(array $values = array())
    {
        $this->values = $values;
    }

    public function alias($abstract, $id)
    {
        $this->aliases[$abstract] = $id;
    }

    public function isAlias($name)
    {
        return isset($this->aliases[$name]);
    }


    public function getAlias($abstract)
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        if ($this->aliases[$abstract] === $abstract) {
            throw new \LogicException("[{$abstract}] is aliased to itself.");
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    public function offsetSet($id, $value)
    {
        $this->values[$id] = $value;
    }

    public function offsetGet($id)
    {
        if ($this->isAlias($id)) {
            $id = $this->getAlias($id);
        }
        if (!array_key_exists($id, $this->values)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        $isFactory = is_object($this->values[$id]) && method_exists($this->values[$id], '__invoke');

        return $isFactory ? $this->values[$id]($this) : $this->values[$id];
    }

    public function offsetExists($id)
    {
        return array_key_exists($id, $this->values);
    }

    public function offsetUnset($id)
    {
        unset($this->values[$id]);
    }

    public function get($id)
    {
        return $this->offsetGet($id);
    }

    public function has($id)
    {
        return $this->offsetExists($id);
    }

    public function share($callable)
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException('Service definition is not a Closure or invokable object.');
        }
        return function ($c) use ($callable) {
            static $object;

            if (null === $object) {
                $object = $callable($c);
            }

            return $object;
        };
    }

    public function make($id)
    {
        return $this->offsetGet($id);
    }

    public function protect($callable)
    {
        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException('Callable is not a Closure or invokable object.');
        }
        return function ($c) use ($callable) {
            return $callable;
        };
    }

    public function raw($id)
    {
        if (!array_key_exists($id, $this->values)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->values[$id];
    }

    public function extend($id, $callable)
    {
        if (!array_key_exists($id, $this->values)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        if (!is_object($this->values[$id]) || !method_exists($this->values[$id], '__invoke')) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" does not contain an object definition.', $id));
        }

        if (!is_object($callable) || !method_exists($callable, '__invoke')) {
            throw new \InvalidArgumentException('Extension service definition is not a Closure or invokable object.');
        }

        $factory = $this->values[$id];

        return $this->values[$id] = function ($c) use ($callable, $factory) {
            return $callable($factory($c), $c);
        };
    }

    public function keys()
    {
        return array_keys($this->values);
    }
}
