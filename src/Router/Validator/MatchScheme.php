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
 * Class MatchScheme
 * @package Lichee\Router\Validator
 */
class MatchScheme implements MatchInterface
{
    const SCHEME = ['http', 'https'];

    /**
     * @param RouteInterface $route
     * @param Request $request
     * @return bool
     */
    public function match(RouteInterface $route, Request $request): bool
    {
        $routeSchemes = array_map('strtolower', $route->getSchemes());
        $requestScheme[] = strtolower($request->getScheme());
        if (empty($routeSchemes)) {
            return true;
        }
        if (array_intersect($routeSchemes, $requestScheme)) {
            return true;
        }
        return true;
    }
}