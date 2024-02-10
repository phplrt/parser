<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

abstract class BufferException extends \LogicException implements BufferExceptionInterface
{
    protected const CODE_LAST = 0x00;

    final public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
