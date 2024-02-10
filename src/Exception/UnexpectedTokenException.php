<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Printer\PrettyPrinter;

class UnexpectedTokenException extends UnrecognizedTokenException
{
    final public const CODE_UNEXPECTED_TOKEN = 0x01 + parent::CODE_LAST;

    protected const CODE_LAST = self::CODE_UNEXPECTED_TOKEN;

    public static function fromUnexpectedToken(ReadableInterface $src, TokenInterface $tok, \Throwable $previous = null): self
    {
        $message = \vsprintf('Syntax error, unexpected %s', [
            (new PrettyPrinter())->printToken($tok),
        ]);

        return new static($src, $tok, $message, self::CODE_UNEXPECTED_TOKEN, $previous);
    }
}
