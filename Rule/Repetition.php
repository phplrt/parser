<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Parser\Buffer\BufferInterface;

/**
 * Class Repetition
 */
class Repetition extends Concatenation
{
    /**
     * @var int|float
     */
    private $gte;

    /**
     * @var int|float
     */
    private $lte;

    /**
     * Repetition constructor.
     *
     * @param array $sequence
     * @param int|float $gte
     * @param int|float $lte
     * @param \Closure|null $reducer
     */
    public function __construct(array $sequence, float $gte, float $lte = \INF, \Closure $reducer = null)
    {
        \assert($lte >= $gte, 'Min repetitions count must be greater or equal than max repetitions');

        parent::__construct($sequence, $reducer);

        $this->gte = $gte;
        $this->lte = $lte;
    }

    /**
     * @param BufferInterface $buffer
     * @param int $type
     * @param int $offset
     * @param \Closure $reduce
     * @return iterable|null
     */
    public function reduce(BufferInterface $buffer, int $type, int $offset, \Closure $reduce): ?iterable
    {
        [$children, $iterations] = [[], 0];

        do {
            [$valid, $rollback] = [
                $iterations >= $this->gte && $iterations <= $this->lte,
                $buffer->key(),
            ];

            $result = parent::reduce($buffer, $type, $offset, $reduce);

            if ($result === null && ! $valid) {
                $buffer->seek($rollback);

                return null;
            }

            $children = $this->merge($children, $result);
        } while ($result !== null && ++$iterations);

        return $children;
    }
}
