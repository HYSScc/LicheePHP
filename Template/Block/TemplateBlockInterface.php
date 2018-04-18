<?php

namespace Lichee\Template\Block;


/**
 * Interface BlockTemplate
 * @package Lichee\Template
 */
interface TemplateBlockInterface
{
    /**
     * <blockId>的开始方法
     * @param $blockId
     * @return mixed
     */
    public function beginBlock($blockId);

    /**
     * <blockId>的结束方法
     * @param $blockId
     * @return mixed
     */
    public function endBlock($blockId);

    /**
     * 渲染Block内容
     * @param $blockId
     * @param $default
     * @return mixed
     */
    public function block($blockId, $default = '');
}