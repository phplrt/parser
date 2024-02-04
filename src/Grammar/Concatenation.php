<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

final class Concatenation extends Production
{
    /**
     * @param non-empty-list<array-key> $sequence
     */
    public function __construct(
        public readonly array $sequence,
    ) {}

    public function getTerminals(array $rules): iterable
    {
        $result = [];

        foreach ($this->sequence as $rule) {
            foreach ($rules[$rule]->getTerminals($rules) as $terminal) {
                $result[] = $terminal;
            }
        }

        return $result;
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        $revert = $buffer->key();
        $children = [];

        foreach ($this->sequence as $rule) {
            if (($result = $reduce($rule)) === null) {
                $buffer->seek($revert);

                return null;
            }

            $children = $this->mergeWith($children, $result);
        }

        return $children;
    }
}
