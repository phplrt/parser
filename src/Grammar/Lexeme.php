<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Contracts\Lexer;
use Phplrt\Parser\Buffer\BufferInterface;

class Lexeme extends Terminal
{
    /**
     * @param int<0, max>|non-empty-string $token
     * @param bool $keep
     */
    public function __construct(
        public readonly int|string $token,
        bool $keep = true,
    ) {
        parent::__construct($keep);
    }

    public function reduce(BufferInterface $buffer): ?Lexer\TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getName() === $this->token) {
            return $haystack;
        }

        return null;
    }
}
