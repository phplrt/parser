<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\LexerExceptionInterface;
use Phplrt\Contracts\Parser\ParserExceptionInterface;

class LexerInitializationException extends \LogicException implements ParserExceptionInterface
{
    final public const CODE_LEXER_INTERNAL_ERROR = 0x01;

    protected const CODE_LAST = self::CODE_LEXER_INTERNAL_ERROR;

    public static function fromLexerException(LexerExceptionInterface $e): self
    {
        $message = 'An error occurred while lexer initialization: %s';
        $message = \sprintf($message, $e->getMessage());

        return new static($message, self::CODE_LEXER_INTERNAL_ERROR, $e);
    }
}
