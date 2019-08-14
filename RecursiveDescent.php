<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Runtime\AbstractParser;
use Phplrt\Parser\Rule\TerminalInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Rule\ProductionInterface;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;

/**
 * Recursive descent parser
 */
class RecursiveDescent extends AbstractParser
{
    /**
     * @var int
     */
    private $state = 0;

    /**
     * @var array|RuleInterface[]
     */
    private $rules;

    /**
     * @var int
     */
    public $cycles = 0;

    /**
     * @var int
     */
    public $rollbacks = 0;

    /**
     * Parser constructor.
     *
     * @param array|RuleInterface[] $rules
     * @param LexerInterface $lexer
     */
    public function __construct(array $rules, LexerInterface $lexer)
    {
        parent::__construct($lexer);

        $this->rules = $rules;
    }

    /**
     * @param ReadableInterface $src
     * @return NodeInterface
     * @throws NotReadableExceptionInterface
     * @throws ParserException
     * @throws ParserRuntimeException
     */
    public function parse(ReadableInterface $src): iterable
    {
        $buffer = $this->lex($src);

        if (($result = $this->reduce($buffer, $this->state)) === null) {
            throw $this->unexpectedToken($src, $this->token ?? $buffer->current());
        }

        if ($buffer->current()->getType() !== $this->eoi) {
            throw $this->unexpectedToken($src, $buffer->current());
        }

        return $this->filter($result);
    }

    /**
     * @param BufferInterface $buffer
     * @return \Closure
     */
    private function next(BufferInterface $buffer): \Closure
    {
        return function (int $state) use ($buffer) {
            return $this->reduce($buffer, $state);
        };
    }

    /**
     * @param BufferInterface $buffer
     * @param int $state
     * @return iterable|TokenInterface|null
     */
    private function reduce(BufferInterface $buffer, int $state)
    {
        $this->cycles++;

        $rule = $this->rules[$state];
        $offset = $buffer->current()->getOffset();

        switch (true) {
            case $rule instanceof ProductionInterface:
                $result = $rule->match($buffer, $state, $offset, $this->next($buffer));
                break;

            case $rule instanceof TerminalInterface:
                if ($result = $rule->match($buffer)) {
                    $this->token = $result;
                }

                break;
        }

        if (($result ?? null) === null) {
            $this->rollbacks++;
        }

        return $result ?? null;
    }

    /**
     * @param iterable $payload
     * @return iterable|NodeInterface
     */
    private function filter(iterable $payload): iterable
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

}
