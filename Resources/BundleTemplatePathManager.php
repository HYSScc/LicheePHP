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

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * 基础路径
     * @var string
     */
    protected $basePath;

    public function __construct(KernelInterface $kernel, $basePath)
    {
        $this->kernel = $kernel;
        $this->basePath = trim($basePath, '/');
    }

    /**
     * 获取真实路径
     * @param $template
     * @return mixed
     */
    public function getTemplateRealPath($template)
    {
        $bundleReplaceReplaceMap = $this->getAliasReplaceMap();
        return str_replace(
            array_keys($bundleReplaceReplaceMap),
            $bundleReplaceReplaceMap,
            $template
        );
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
            $bundleReplaceMap[self::ALIAS_DELIMITER . $bundleKey] = $bundle->getPath() . "/" . $this->basePath;
        }
        return $bundleReplaceMap;
    }

}





