<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Printer\PrettyPrinter;

class UnrecognizedTokenException extends \RuntimeException implements ParserRuntimeExceptionInterface
{
    final public const CODE_UNRECOGNIZED_TOKEN = 0x01;

    protected const CODE_LAST = self::CODE_UNRECOGNIZED_TOKEN;

    final public function __construct(
        private readonly ReadableInterface $source,
        private readonly TokenInterface $token,
        string $message,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromUnrecognizedToken(ReadableInterface $src, TokenInterface $tok, \Throwable $previous = null): self
    {
        $message = \vsprintf('Syntax error, unrecognized %s', [
            (new PrettyPrinter())->printToken($tok),
        ]);

        return new static($src, $tok, $message, self::CODE_UNRECOGNIZED_TOKEN, $previous);
    }

    public function getSource(): ReadableInterface
    {
        return $this->source;
    }

    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}
