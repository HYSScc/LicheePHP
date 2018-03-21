<?php

namespace Lichee\Resources;


/**
 * Class BundleFileManager
 * @package Lichee\Resources
 */
interface TemplatePathManagerInterface
{
    /**
     * @param $view
     * @return mixed
     */
    public function getTemplateRealPath($view);
}