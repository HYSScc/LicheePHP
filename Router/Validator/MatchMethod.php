<?php
/**
 * Created by PhpStorm.
 * User: xingfeilong
 * Date: 2017/9/9
 * Time: 下午9:10
 */

namespace Lichee\Router\Validator;


use Lichee\Router\RouteInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MatchMethod
 * @package Lichee\Router\Validator
 */
class MatchMethod implements MatchInterface
{
    /**
     * @param RouteInterface $route
     * @param Request $request
     * @return bool
     */
    public function match(RouteInterface $route, Request $request): bool
    {
        return count(
                array_intersect(
                    array_map('strtoupper', $route->getMethods()),
                    [strtoupper($request->getMethod())])
            ) > 0;
    }
}