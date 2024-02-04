<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

final class Optional extends Production
{
    /**
     * @param array-key $rule
     */
    public function __construct(
        public readonly int|string $rule,
    ) {}

    public function getTerminals(array $rules): iterable
    {
        if (!isset($rules[$this->rule])) {
            return [];
        }

        return $rules[$this->rule]->getTerminals($rules);
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce)
    {
        $rollback = $buffer->key();

        if (($result = $reduce($this->rule)) !== null) {
            return $result;
        }

        $buffer->seek($rollback);

        return [];
    }
}
