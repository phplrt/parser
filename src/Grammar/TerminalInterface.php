<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Interface denoting a leaf (that is a terminal) rule.
 */
interface TerminalInterface extends RuleInterface
{
    /**
     * Returns a matched token if the current buffer state is correctly
     * processed. Otherwise, if the rule does not match the required one,
     * it returns null.
     *
     * @param BufferInterface $buffer
     * @return TokenInterface|null
     */
    public function reduce(BufferInterface $buffer): ?TokenInterface;

    /**
     * @return bool
     */
    public function isKeep(): bool;
}
