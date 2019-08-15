<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Lexer\Token\BaseToken;
use Phplrt\Exception\SourceException;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\LexerRuntimeException;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;

/**
 * Class ParserRuntimeException
 */
class ParserRuntimeException extends SourceException implements RuntimeExceptionInterface
{
    /**
     * @var TokenInterface|null
     */
    private $token;

    /**
     * @param TokenInterface|null $token
     * @return RuntimeExceptionInterface|$this
     */
    public function withToken(?TokenInterface $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        if ($this->token === null) {
            throw new \LogicException(\sprintf('Can not call %s. Token not define', __METHOD__));
        }

        return $this->token;
    }
}
