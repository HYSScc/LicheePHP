<?php

namespace Lichee\Template\Exception;

use Throwable;

class TemplateException extends \RuntimeException
{
    public function __construct($message = "Template Render Exception", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}