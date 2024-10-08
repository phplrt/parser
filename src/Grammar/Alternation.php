<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

/**
 * @final marked as final since phplrt 3.4 and will be final since 4.0
 */
class Alternation extends Production
{
    public function __construct(
        /**
         * @var list<array-key>
         */
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
