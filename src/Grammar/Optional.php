<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Parser\Buffer\BufferInterface;

class Optional extends Production
{
    /**
     * @param int<0, max>|non-empty-string $rule
     */
    public function __construct(
        public readonly int|string $rule,
    ) {}

    public function reduce(BufferInterface $buffer, \Closure $reduce): mixed
    {
        $rollback = $buffer->key();

        if (($result = $reduce($this->rule)) !== null) {
            return $result;
        }

        $buffer->seek($rollback);

        return [];
    }
}
