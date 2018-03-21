<?php

namespace Lichee\Resources;

use Lichee\Bundle\BundleInterface;
use Lichee\Kernel\KernelInterface;

/**
 * Class BundleFileManager
 * @package Lichee\Resources
 */
class BundleTemplatePathManager implements TemplatePathManagerInterface
{
    const ALIAS_DELIMITER = "@";

    protected $kernel;

    protected $viewsPath;

    public function __construct(KernelInterface $kernel, $viewsPath)
    {
        $this->kernel = $kernel;
        $this->viewsPath = trim($viewsPath, '/');
    }

    public function getTemplateRealPath($view)
    {
        $bundleReplaceMap = $this->getAliasReplaceMap();
        return str_replace(array_keys($bundleReplaceMap), $bundleReplaceMap, $view);
    }

    /**
     * @return array
     */
    protected function getAliasReplaceMap()
    {
        static $bundleReplaceMap;
        if (!empty($bundleReplaceMap)) {
            return $bundleReplaceMap;
        }
        /**
         * @var $bundle BundleInterface
         */
        foreach ($this->kernel->getBundles() as $bundleKey => $bundle) {
            $bundleReplaceMap[self::ALIAS_DELIMITER . $bundleKey] = $bundle->getPath() . "/" . $this->viewsPath;
        }
        return $bundleReplaceMap;
    }

}





