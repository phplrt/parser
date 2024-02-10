<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

abstract class Terminal extends Rule implements TerminalInterface
{
    /**
     * @param bool $keep
     */
    public function __construct(
        public readonly bool $keep,
    ) {
    }

    /**
     * @return bool
     */
    public function isKeep(): bool
    {
        return $this->keep;
    }
}
