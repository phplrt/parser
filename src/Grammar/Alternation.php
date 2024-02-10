<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Parser\Buffer\BufferInterface;

class Alternation extends Production
{
    /**
     * @param non-empty-list<int<0, max>|non-empty-string> $sequence
     */
    public function __construct(
        public readonly array $sequence,
    ) {}

    public function reduce(BufferInterface $buffer, \Closure $reduce): mixed
    {
        $rollback = $buffer->key();

        foreach ($this->sequence as $rule) {
            if (($result = $reduce($rule)) !== null) {
                return $result;
            }

            $buffer->seek($rollback);
        }

        return null;
    }
}
