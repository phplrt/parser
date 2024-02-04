<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

final class Repetition extends Production
{
    /**
     * @var int<0, max>
     */
    public readonly int $from;

    /**
     * @var int<0, max>|float
     */
    public readonly int|float $to;

    /**
     * @param array-key $rule
     * @param int<0, max> $gte
     * @param int<0, max>|float $lte
     */
    public function __construct(
        public readonly string|int $rule,
        int $gte = 0,
        int|float $lte = \INF,
    ) {
        \assert($lte >= $gte, new \InvalidArgumentException(
            'Min repetitions count must be greater or equal than max repetitions',
        ));

        $this->from = $gte;
        $this->to = \is_infinite($lte) ? \INF : (int) $lte;
    }

    public function getTerminals(array $rules): iterable
    {
        if (!isset($rules[$this->rule])) {
            return [];
        }

        return $rules[$this->rule]->getTerminals($rules);
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        $children = [];
        $iterations = 0;

        $global = $buffer->key();

        do {
            $inRange = $iterations >= $this->from && $iterations <= $this->to;
            $rollback = $buffer->key();

            if (($result = $reduce($this->rule)) === null) {
                if (!$inRange) {
                    $buffer->seek($global);

                    return null;
                }

                $buffer->seek($rollback);

                return $children;
            }

            $children = $this->mergeWith($children, $result);
            ++$iterations;
        } while ($result !== null);

        return $children;
    }
}
