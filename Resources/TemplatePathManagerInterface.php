<?php

namespace Lichee\Resources;


/**
 * Class BundleFileManager
 * @package Lichee\Resources
 */
interface TemplatePathManagerInterface
{
    /**
     * @param $template
     * @return mixed
     */
    public function getTemplateRealPath($template);
}