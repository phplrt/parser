<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Buffer\EagerBuffer;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface as ParserRuntimeExceptionInterface;

/**
 * Class AbstractParser
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     * Contains the readonly number of returns to the previous state in the
     * case of an incorrectly selected chain of rules.
     *
     * @var int
     */
    public $rollbacks = 0;

    /**
     * Contains the readonly number of rules which were processed.
     *
     * @var int
     */
    public $reduces = 0;

    /**
     * Contains a token identifier that is excluded from analysis.
     *
     * @var int
     */
    protected $skip = TokenInterface::TYPE_SKIP;

    /**
     * Contains a token identifier that marks the end of the source.
     *
     * @var int
     */
    protected $eoi = TokenInterface::TYPE_END_OF_INPUT;

    /**
     * Contains the readonly token object which was last successfully processed
     * in the rules chain.
     *
     * It is required so that in case of errors it is possible to report that
     * it was on it that the problem arose.
     *
     * @var TokenInterface
     */
    protected $token;

    /**
     * The maximum number of tokens (lexemes) that are stored in the buffer.
     *
     * @var int
     */
    protected $buffered = 100;

    /**
     * The initial identifier of the rule with which parsing begins.
     *
     * @var int|null
     */
    protected $initial;

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
     * A helper method that converts the returned data to the correct format.
     * <code>
     *  class MyParser extends AbstractParser
     *  {
     *      public function parse(ReadableInterface $src): iterable
     *      {
     *          return $this->normalize(
     *              $this->doParse($src)
     *          );
     *      }
     *  }
     * </code>
     *
     * @param iterable|NodeInterface|NodeInterface[] $payload
     * @return iterable|NodeInterface|NodeInterface[]
     */
    protected function normalize(iterable $payload): iterable
    {
        if ($payload instanceof NodeInterface) {
            return $payload;
        }

        $result = [];

        foreach ($payload as $item) {
            if ($item instanceof NodeInterface) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * A method that performs lexical analysis from the passed sources,
     * converts lexical analysis errors into parser errors and returns a
     * token buffer.
     *
     * @param ReadableInterface $src
     * @return BufferInterface
     * @throws ParserExceptionInterface
     * @throws ParserRuntimeExceptionInterface
     * @throws NotReadableExceptionInterface
     */
    protected function lex(ReadableInterface $src): BufferInterface
    {
        try {
            return $this->buffered($this->streamOf($src), $this->buffered);
        } catch (RuntimeExceptionInterface $e) {
            throw $this->error($src, $e->getToken());
        } catch (\Exception|LexerExceptionInterface $e) {
            throw new ParserException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Method that converts token stream to buffer of lexemes.
     *
     * @param \Generator|TokenInterface[] $stream
     * @param int $size
     * @return BufferInterface|TokenInterface[]
     */
    protected function buffered(\Generator $stream, int $size): BufferInterface
    {
        return new EagerBuffer($stream);
    }

    /**
     * Returns a stream of tokens, excluding ignored ones.
     *
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
     * Helper method that returns an error during parsing.
     *
     * @param ReadableInterface $src
     * @param TokenInterface $token
     * @return ParserRuntimeExceptionInterface
     * @throws NotReadableExceptionInterface
     */
    protected function error(ReadableInterface $src, TokenInterface $token): ParserRuntimeExceptionInterface
    {
        $message = \sprintf('Syntax error, unexpected %s', $token);

        $exception = new ParserRuntimeException($message);
        $exception->throwsIn($src, $token->getOffset());
        $exception->withToken($token);

        return $exception;
    }
}
