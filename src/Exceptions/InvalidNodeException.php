<?php

namespace Lastdino\ApprovalFlow\Exceptions;

use Exception;

/**
 * 無効なノードに関する例外
 */
class InvalidNodeException extends Exception
{
    public function __construct(string $message = "無効なノードです", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
