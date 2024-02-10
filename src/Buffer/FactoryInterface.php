<?php

declare(strict_types=1);

namespace Phplrt\Parser\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

interface FactoryInterface
{
    /**
     * @param iterable<int<0, max>, TokenInterface> $tokens
     */
    public function create(iterable $tokens): BufferInterface;
}
