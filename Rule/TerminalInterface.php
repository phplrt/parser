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
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Interface TerminalInterface
 */
interface TerminalInterface extends RuleInterface
{
    /**
     * @param BufferInterface $buffer
     * @return null|mixed
     */
    public function reduce(BufferInterface $buffer): ?TokenInterface;
}
