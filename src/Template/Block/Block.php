<?php

namespace Lichee\Template\Block;

/**
 * Class Block
 * @package Lichee\Template
 */
class Block
{
    public $content;
    public $canRender = false;

    public function begin()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    public function end()
    {
        $this->content = ob_get_clean();
        $this->canRender = true;
    }

    public function render()
    {
        if ($this->canRender) {
            return $this->content;
        } else {
            return '';
        }
    }

    public function __toString()
    {
        return '';
    }
}