<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Parser\Buffer\BufferInterface;

class Concatenation extends Production
{
    /**
     * @param non-empty-list<int<0, max>|non-empty-string> $sequence
     */
    public function __construct(
        public readonly array $sequence,
    ) {
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        $offset = $buffer->key();
        $children = [];

        foreach ($this->sequence as $rule) {
            if (($result = $reduce($rule)) === null) {
                $buffer->seek($offset);

                return null;
            }

            if (\is_array($result)) {
                $children = [...$children, ...$result];
            } else {
                $children[] = $result;
            }
        }

        return $children;
    }
}
