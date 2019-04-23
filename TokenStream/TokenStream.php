<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\TokenStream;

use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\TokenInterface;

/**
 * Class TokenStream
 */
final class TokenStream extends Buffer
{
    /**
     * @return bool
     */
    public function isEoi(): bool
    {
        return $this->current()->getName() === EndOfInput::T_NAME;
    }

    /**
     * @return TokenInterface|null
     */
    public function current(): ?TokenInterface
    {
        return parent::current();
    }

    /**
     * @return TokenInterface|null
     */
    public function next(): ?TokenInterface
    {
        parent::next();

        return $this->current();
    }

    /**
     * @return null|TokenInterface
     */
    public function prev(): ?TokenInterface
    {
        parent::prev();

        return $this->current();
    }

    /**
     * @return int
     */
    public function offset(): int
    {
        $current = $this->current();

        return $current ? $current->getOffset() : 0;
    }
}
