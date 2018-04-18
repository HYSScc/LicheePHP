<?php

namespace Lichee\Router;

interface RouteInterface
{
    public function getPath();

    public function getCallback();

    public function getHost();

    public function getMethods();

    public function getRegex();

    public function getAction();

    public function getParams();

    public function getSchemes(): array;

    public function getSplat();

    public function isCaseSensitive(): bool;

    public function setCallback($callback);

    public function setHost($host);

    public function setMethods(array $methods);

    public function setParams(array $params);

    public function setSchemes(array $scheme);

    public function setRegex($regex);

    public function setPath($path);

    public function setSplat($splat);

    public function setAction($action);

    public function setCaseSensitive(bool $caseSensitive);

    public function isHttpOnly(): bool;

    public static function getValidators(): array;

}
