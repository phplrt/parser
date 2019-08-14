<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Buffer\BufferInterface;

/**
 * Interface ProductionInterface
 */
interface ProductionInterface
{
    /**
     * @return array|int[]
     */
    public function getSequence(): array;

    /**
     * @param array $rules
     * @return ProductionInterface|$this
     */
    public function withSequence(array $rules): self;

    /**
     * @param int $type
     * @param int $offset
     * @param BufferInterface $buffer
     * @param \Closure $reduce
     * @return iterable|null
     */
    public function match(BufferInterface $buffer, int $type, int $offset, \Closure $reduce): ?iterable;

    /**
     * @param array $children
     * @param int $offset
     * @param int $type
     * @return iterable|NodeInterface|NodeInterface[]
     */
    public function reduce(array $children, int $offset, int $type): iterable;
}
