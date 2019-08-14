<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Parser\Buffer\BufferInterface;

/**
 * Class Optional
 */
class Optional extends Production
{
    /**
     * @param BufferInterface $buffer
     * @param int $type
     * @param int $offset
     * @param \Closure $reduce
     * @return iterable|null
     */
    public function match(BufferInterface $buffer, int $type, int $offset, \Closure $reduce): ?iterable
    {
        [$revert, $children] = [$buffer->key(), []];

        foreach ($this->sequence as $rule) {
            if (($result = $reduce($rule)) === null) {
                $buffer->seek($revert);

                return $children;
            }

            if (\is_array($result)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $children = \array_merge($children, $result);
            } else {
                $children[] = $result;
            }
        }

        return $this->reduce($children, $offset, $type);
    }
}
