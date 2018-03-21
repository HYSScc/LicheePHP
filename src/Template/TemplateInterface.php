<?php

namespace Lichee\Template;

/**
 * Interface TemplateInterface
 * @package Lichee\Template
 */
interface TemplateInterface
{
    /**
     * 渲染模板和布局
     * @param $view
     * @param array $parameters
     * @return string
     */
    public function render($view, $parameters = array());
}
