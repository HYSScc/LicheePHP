<?php
/**
 * Created by PhpStorm.
 * User: xingfeilong
 * Date: 2018/1/17
 * Time: 下午1:57
 */

namespace Lichee\Bundle;


use Lichee\Router\RouteInterface;

interface ActionBundleInterface
{

    public function setTheme(string $theme);

    public function getTheme(): string;

    public function setViewBasePath(string $basePath);

    public function getViewBasePath();

    public function runAction(RouteInterface $route);
}