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
     * @param string $template
     * @param array $parameters
     * @return string
     */
    public function render($template, $parameters):string;

    /**
     * 是否有这个模板
     * @param string $template
     * @return bool
     */
    public function hasTemplate($template): bool;

    /**
     * 获取模板内容
     * @param $template
     * @param $parameters
     * @return string
     */
    public function getTemplateContent($template, $parameters): string;

    /**
     * 继承模板
     * @param $template
     * @param array $parameters
     * @return mixed
     */
    public function extend($template, $parameters);
}
