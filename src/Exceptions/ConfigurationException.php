<?php

namespace Lastdino\ApprovalFlow\Exceptions;

use Exception;

/**
 * 設定に関する例外
 */
class ConfigurationException extends Exception
{
    public function __construct(string $message = "設定エラーが発生しました", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
