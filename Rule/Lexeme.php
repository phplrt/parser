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
class Lexeme extends Terminal
{
    /**
     * @param BufferInterface $buffer
     * @return TokenInterface|null
     */
    public function match(BufferInterface $buffer): ?TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getType() === $this->token) {
            $buffer->next();

            return $haystack;
        }

        return null;
    }
}
