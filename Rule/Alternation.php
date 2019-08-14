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
 * Class LeftAlternation
 */
class Alternation extends Production
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
        foreach ($this->sequence as $rule) {
            if (($value = $reduce($rule)) !== null) {
                $children = \is_array($value) ? $value : [$value];

                return $this->reduce($children, $offset, $type);
            }
        }

        return null;
    }
}
