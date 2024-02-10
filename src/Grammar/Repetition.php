<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Parser\Buffer\BufferInterface;

class Repetition extends Production
{
    /**
     * @var int<0, max>|\INF
     */
    public readonly int|float $to;

    /**
     * @param int|string $rule
     * @param int $from
     * @param int|float $to
     */
    public function __construct(
        public readonly int|string $rule,
        public readonly int $from = 0,
        int|float $to = \INF,
    ) {
        \assert($to >= $from, 'Min repetitions count must be greater or equal than max repetitions');

        $this->to = \is_infinite($to) ? \INF : (int)$to;
    }

    public function reduce(BufferInterface $buffer, \Closure $reduce): ?iterable
    {
        $children = [];
        $iterations = 0;
        $global = $buffer->key();

        do {
            $inRange  = $iterations >= $this->from && $iterations <= $this->to;
            $rollback = $buffer->key();

            if (($result = $reduce($this->rule)) === null) {
                if (!$inRange) {
                    $buffer->seek($global);

                    return null;
                }

                $buffer->seek($rollback);

                return $children;
            }

            if (\is_array($result)) {
                $children = [...$children, ...$result];
            } else {
                $children[] = $result;
            }

            ++$iterations;
        } while ($result !== null);

        return $children;
    }
}
