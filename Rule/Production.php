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
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Production
 */
abstract class Production extends Rule implements ProductionInterface
{
    /**
     * @var array
     */
    public $sequence;

    /**
     * @var int
     */
    private $length;

    /**
     * @var \Closure
     */
    private $reducer;

    /**
     * Rule constructor.
     *
     * @param array $sequence
     * @param \Closure $reducer|null
     */
    public function __construct(array $sequence, \Closure $reducer = null)
    {
        $this->withSequence($sequence);
        $this->reducer = $reducer;
    }

    /**
     * @return array|int[]
     */
    public function getSequence(): array
    {
        return $this->sequence;
    }

    /**
     * @param array $rules
     * @return ProductionInterface|$this
     */
    public function withSequence(array $rules): ProductionInterface
    {
        $this->length = \count($rules);
        $this->sequence = $rules;

        return $this;
    }

    /**
     * @param int $type
     * @param int $offset
     * @param array|NodeInterface[]|TokenInterface[] $children
     * @return iterable|NodeInterface[]|NodeInterface
     */
    public function reduce(array $children, int $offset, int $type): iterable
    {
        if ($this->reducer) {
            return ($this->reducer)($children, $offset, $type);
        }

        return $children;
    }
}
