<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Runtime;

use Phplrt\Ast\Anonymous;
use Phplrt\Parser\Buffer\EagerBuffer;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Lexer\LexerInterface as PhplrtLexerInterface;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;

/**
 * Class AbstractParser
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     * @var int
     */
    protected $skip = TokenInterface::TYPE_SKIP;

    /**
     * @var int
     */
    protected $eoi = TokenInterface::TYPE_END_OF_INPUT;

    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * AbstractParser constructor.
     *
     * @param LexerInterface $lexer
     */
    public function __construct(LexerInterface $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * @param int $state
     * @param int $offset
     * @param array|NodeInterface[]|TokenInterface[] $children
     * @return NodeInterface
     */
    protected function create(int $state, int $offset, array $children = []): NodeInterface
    {
        return new Anonymous($state, [
            'offset' => $offset,
        ], $children);
    }

    /**
     * @param ReadableInterface $src
     * @return BufferInterface
     * @throws ParserException
     * @throws ParserRuntimeException
     * @throws NotReadableExceptionInterface
     */
    protected function lex(ReadableInterface $src): BufferInterface
    {
        try {
            return new EagerBuffer($this->streamOf($src));
        } catch (RuntimeExceptionInterface $e) {
            throw $this->unexpectedToken($src, $e->getToken());
        } catch (\Exception|LexerExceptionInterface $e) {
            throw new ParserException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param ReadableInterface $src
     * @return \Generator
     * @throws LexerExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    private function streamOf(ReadableInterface $src): \Generator
    {
        foreach ($this->lexer->lex($src) as $token) {
            if ($token->getType() !== $this->skip) {
                yield $token;
            }
        }
    }

    /**
     * @param ReadableInterface $src
     * @param TokenInterface $token
     * @return ParserRuntimeException
     * @throws NotReadableExceptionInterface
     */
    protected function unexpectedToken(ReadableInterface $src, TokenInterface $token): ParserRuntimeException
    {
        $message = \sprintf('Syntax error, unexpected %s (%s)', $token, $this->nameOf($token));

        $exception = new ParserRuntimeException($message);
        $exception->throwsIn($src, $token->getOffset());

        return $exception;
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    protected function nameOf(TokenInterface $token): string
    {
        if ($this->lexer instanceof PhplrtLexerInterface) {
            return $this->lexer->nameOf($token->getType());
        }

        $hex = '0x' . \str_pad(\dechex(\abs($token->getType())), 4, '0', \STR_PAD_LEFT);

        return $token->getType() > 0 ? $hex : '-' . $hex;
    }
}
