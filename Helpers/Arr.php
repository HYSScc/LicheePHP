<?php

namespace Lichee\Helpers;

use ArrayAccess;

/**
 * Class Arr
 * @package Lichee\Helpers
 */
class Arr
{

    public static function group(array $array, $key)
    {
        $grouped = array();

        foreach ($array as $item) {
            if (empty($grouped[$item[$key]])) {
                $grouped[$item[$key]] = array();
            }

            $grouped[$item[$key]][] = $item;
        }

        return $grouped;
    }

    public static function index(array $array, $name)
    {
        $indexedArray = array();

        if (empty($array)) {
            return $indexedArray;
        }

        foreach ($array as $item) {
            if (isset($item[$name])) {
                $indexedArray[$item[$name]] = $item;
                continue;
            }
        }

        return $indexedArray;
    }

    public static function rename(array $array, array $map)
    {
        $keys = array_keys($map);
        foreach ($array as $key => $value) {
            if (in_array($key, $keys)) {
                $array[$map[$key]] = $value;
                unset($array[$key]);
            }
        }
        return $array;
    }

    public static function trim($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = static::trim($value);
            } elseif (is_string($value)) {
                $array[$key] = trim($value);
            }
        }

        return $array;
    }

    public static function every($array, $callback = null)
    {
        foreach ($array as $value) {
            if ((is_null($callback) && !$value) || (is_callable($callback) && !$callback($value))) {
                return false;
            }
        }

        return true;
    }

    public static function some($array, $callback = null)
    {
        foreach ($array as $value) {
            if ((is_null($callback) && $value) || (is_callable($callback) && $callback($value))) {
                return true;
            }
        }

        return false;
    }


    public static function random($array, $number = null)
    {
        $requested = is_null($number) ? 1 : $number;

        $count = count($array);

        if ($requested > $count) {
            throw new \InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }

        if (is_null($number)) {
            return $array[array_rand($array)];
        }

        if ((int)$number === 0) {
            return [];
        }

        $keys = array_rand($array, $number);

        $results = [];

        foreach ((array)$keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }

}