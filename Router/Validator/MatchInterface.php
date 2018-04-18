<?php

namespace Lichee\Router\Validator;


use Lichee\Router\RouteInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface MatchInterface
 * @package Lichee\Router\Validator
 */
interface MatchInterface
{
    /**
     * @param RouteInterface $route
     * @param Request $request
     * @return bool
     */
    public function match(RouteInterface $route, Request $request): bool;
}