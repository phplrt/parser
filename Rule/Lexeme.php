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
 * Class Lexeme
 */
class Lexeme extends Rule implements TerminalInterface
{
    /**
     * @var int
     */
    public $token;

    /**
     * Lexeme constructor.
     *
     * @param int $token
     */
    public function __construct(int $token)
    {
        $this->token = $token;
    }

    /**
     * @param BufferInterface $buffer
     * @return TokenInterface|null
     */
    public function reduce(BufferInterface $buffer): ?TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getType() === $this->token) {
            return $haystack;
        }

        return null;
    }
}
