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
 * Class MatchUrl
 * @package Lichee\Router\Validator
 */
class MatchUrl implements MatchInterface
{

    /**
     * @param RouteInterface $route
     * @param Request $request
     * @return bool
     */
    public function match(RouteInterface $route, Request $request): bool
    {
        $routePattern = $route->getHost() . $route->getPath();
        $url = ($route->getHost() ? $request->getHost() : '') . $request->getPathInfo();
        if ($routePattern === $url) {
            return true;
        }
        $ids = array();
        $lastChar = substr($routePattern, -1);

        // Get splat
        if ($lastChar === '*') {
            $n = 0;
            $len = strlen($url);
            $count = substr_count($routePattern, '/');

            for ($i = 0; $i < $len; $i++) {
                if ($url[$i] == '/') $n++;
                if ($n == $count) break;
            }

            $route->setSplat((string)substr($url, $i + 1));
        }

        // Build the regex for matching
        $regex = str_replace(array(')', '/*'), array(')?', '(/?|/.*?)'), $routePattern);

        $regex = preg_replace_callback(
            '#@([\w]+)(:([^/\(\)]*))?#',
            function ($matches) use (&$ids) {
                $ids[$matches[1]] = null;
                if (isset($matches[3])) {
                    return '(?P<' . $matches[1] . '>' . $matches[3] . ')';
                }
                return '(?P<' . $matches[1] . '>[^/\?]+)';
            },
            $regex
        );

        // Fix trailing slash
        if ($lastChar === '/') {
            $regex .= '?';
        } // Allow trailing slash
        else {
            $regex .= '/?';
        }

        $routeParams = [];
        // Attempt to match route and named parameters
        if (preg_match('#^' . $regex . '(?:\?.*)?$#' . (($route->isCaseSensitive()) ? '' : 'i'), $url, $matches)) {
            foreach ($ids as $k => $v) {
                $routeParams[$k] = (array_key_exists($k, $matches)) ? urldecode($matches[$k]) : null;
            }
            $route->setParams($routeParams);
            $route->setRegex($regex);

            return true;
        }

        return false;
    }


}