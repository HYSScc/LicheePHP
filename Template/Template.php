<?php

namespace Lichee\Template;

use ArrayAccess;
use AppBundle\AppBundle;
use Lichee\Kernel\Container;
use Lichee\Resources\BundleTemplatePathManager;
use Lichee\Template\Block\Block;
use Lichee\Template\Exception\TemplateException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Lichee\Template\Block\TemplateBlockInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Template
 * @package Lichee\Template
 */
class Template implements TemplateInterface, TemplateBlockInterface, ArrayAccess
{
    /**
     * @var BundleTemplatePathManager
     */
    protected $fileManager;

    /**
     * @var array
     */
    private $extendStack = [];

    /**
     * @var
     */
    protected $blockStack;

    /**
     * @var array
     */
    protected $data;

    /**
     * Template constructor.
     * @param BundleTemplatePathManager $fileManager
     */
    public function __construct(BundleTemplatePathManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param string $template
     * @param array $parameters
     * @return string
     */
    public function render($template, $parameters = array()): string
    {
        $subTemplate = $this->getTemplateContent($template, $parameters);
        if (empty($this->extendStack)) {
            return $subTemplate;
        }
        $lastLayoutRenderContent = null;
        $rendNextLayout = function ($nextLayout) use (&$lastLayoutRenderContent, &$rendNextLayout) {
            $lastLayoutRenderContent = call_user_func($nextLayout);
            if ($this->extendStack) {
                $rendNextLayout(array_shift($this->extendStack));
            }
        };
        if ($this->extendStack) {
            $rendNextLayout(array_shift($this->extendStack));
        }
        return $lastLayoutRenderContent;
    }

    /**
     * 模板是否存在
     * @param $template
     * @return bool
     */
    public function hasTemplate($template): bool
    {
        return file_exists($this->fileManager->getTemplateRealPath($template));
    }

    /**
     * 获取模板内容
     * @param $template
     * @param $parameters
     * @return mixed
     */
    public function getTemplateContent($template, $parameters): string
    {
        $templatePath = $this->getTemplateRealPath($template);
        if (!$this->hasTemplate($template)) {
            throw new NotFoundHttpException($templatePath);
        }
        $content = $this->getRenderContent($templatePath, $parameters);
        return $content;
    }

    /**
     * 获取模板绝对路径
     * @param $template
     * @return mixed
     */
    protected function getTemplateRealPath($template): string
    {
        return $this->fileManager->getTemplateRealPath($template);
    }

    /**
     * 继承模板
     *
     * 这里继承模板后layout不会立即执行 @see render()
     * @param string $template
     * @param array $parameters
     * @return null
     */
    public function extend($template, $parameters = array())
    {
        $this->extendStack[$template] = function () use ($template, $parameters) {
            return $this->getTemplateContent($template, $parameters);
        };
    }

    /**
     * begin block scope
     * @param $blockId
     * @return mixed
     */
    public function beginBlock($blockId)
    {
        if (isset($this->blockStack[$blockId])) {
            throw new TemplateException("This Block[$blockId] already exists");
        }
        $block = new Block();
        $block->begin();
        $this->blockStack[$blockId] = $block;
        return $this->blockStack[$blockId];
    }

    /**
     * @param $blockId
     * @return string
     */
    public function endBlock($blockId)
    {
        if (!isset($this->blockStack[$blockId])) {
            throw new TemplateException("This Block[$blockId] does not exist");
        }
        /**
         * @var $block Block
         */
        $block = $this->blockStack[$blockId];
        $block->end();
        return $block;
    }

    /**
     * @param $blockId
     * @param string $default
     * @return string
     */
    public function block($blockId, $default = '')
    {
        if (!isset($this->blockStack[$blockId])) {
            return $default;
        }
        /**
         * @var $block Block
         */
        $block = $this->blockStack[$blockId];
        unset($this->blockStack[$blockId]);
        return $block->render();
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        preg_match("/^(?P<method>beginBlock|endBlock|block)(?P<blockName>[a-zA-Z]+)$/", $name, $matches);
        if (!isset($matches['method'])) {
            throw new \InvalidArgumentException("Dynamic Call Method Not Found");
        }
        return $this->{$matches['method']}($matches['blockName'], $arguments ?: null);
    }


    /**
     * get render content
     * @param $file
     * @param array $parameters
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    public function getRenderContent($file, array $parameters)
    {
        $obInitialLevel = ob_get_level();
        $clearObLevel = function ($obInitialLevel) {
            while (ob_get_level() > $obInitialLevel) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
        };
        ob_start();
        ob_implicit_flush(false);
        extract($parameters, EXTR_OVERWRITE);
        try {
            require($file);
            return ob_get_clean();
        } catch (\Exception $e) {
            $clearObLevel($obInitialLevel);
            throw $e;
        } catch (\Throwable $e) {
            $clearObLevel($obInitialLevel);
            throw $e;
        }
    }

    /**
     * @param $file
     * @param array $parameters
     * @return string
     */
    public function renderWidget($file, array $parameters = [])
    {
        return $this->getTemplateContent($file, $parameters);
    }


}
