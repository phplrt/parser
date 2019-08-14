<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Rule\Lexeme;
use Phplrt\Parser\Rule\Optional;
use Phplrt\Lexer\LexerInterface;
use Phplrt\Compiler\LexerBuilder;
use Phplrt\Parser\Rule\Alternation;
use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Parser\Rule\Concatenation;
use Phplrt\Compiler\LexerBuilderInterface;
use Phplrt\Parser\Rule\ProductionInterface;

/**
 * Class Builder
 */
class Grammar
{
    /**
     * @var array
     */
    private $ids = [];

    /**
     * @var array|RuleInterface[]
     */
    private $rules = [];

    /**
     * @var LexerBuilder
     */
    private $lexer;

    /**
     * @var int
     */
    private $tokens = 0;

    /**
     * Builder constructor.
     *
     * @param \Closure $expr
     * @param string|null $initial
     */
    public function __construct(\Closure $expr, string $initial = null)
    {
        $this->lexer = new LexerBuilder();

        if ($initial) {
            $this->fetchId($initial);
        }

        $this->extend($expr);
    }

    /**
     * @param string $pcre
     * @param string|null $name
     * @return RuleInterface
     */
    public function token(string $pcre, string $name = null): RuleInterface
    {
        $name = $name ?? $pcre;

        $this->tokens++;

        $this->lexer->token($name, $pcre);

        return new Lexeme(0xff + $this->tokens);
    }

    /**
     * @param string|\Closure $nameOrCallback
     * @param \Closure|null $then
     * @return LexerBuilderInterface
     */
    public function lexer($nameOrCallback, \Closure $then = null): LexerBuilderInterface
    {
        return $this->lexer->state($nameOrCallback, $then);
    }

    /**
     * @return array
     */
    public function getGrammar(): array
    {
        $result = [];

        foreach ($this->getRules() as $id => $rule) {
            switch (true) {
                case $rule instanceof ProductionInterface:
                    $result[$id] = $rule->getSequence();
                    break;
                case $rule instanceof Lexeme:
                    $result[$id] = $rule->token;
                    break;
            }
        }

        \ksort($result);

        return $result;
    }

    /**
     * @param array $rules
     * @param \Closure $then
     * @return RuleInterface
     */
    public function sequenceOf(array $rules, \Closure $then = null): RuleInterface
    {
        return new Concatenation($rules, $then);
    }

    /**
     * @param array $rules
     * @param \Closure $then
     * @return RuleInterface
     */
    public function optional(array $rules, \Closure $then = null): RuleInterface
    {
        return new Optional($rules, $then);
    }

    /**
     * @param array $rules
     * @param \Closure|null $then
     * @return RuleInterface
     */
    public function oneOf(array $rules, \Closure $then = null): RuleInterface
    {
        return new Alternation($rules, $then);
    }

    /**
     * @return array|int[]
     */
    public function getTypes(): array
    {
        $result = [];

        foreach ($this->getRules() as $id => $rule) {
            $result[$id] = $rule->getType();
        }

        \ksort($result);

        return $result;
    }

    /**
     * @param \Closure $expr
     * @return Grammar
     */
    public function extend(\Closure $expr): self
    {
        /** @var \Generator $generator */
        $generator = $expr($this);

        while ($generator->valid()) {
            [$name, $rule] = [$generator->key(), $generator->current()];

            if ($rule instanceof ProductionInterface) {
                $rule->withSequence(\array_map($this->mapper(), $rule->getSequence()));
            }

            $this->rules[$id = $this->fetchId((string)$name)] = $rule;

            $generator->send($id);
        }

        \ksort($this->rules);

        return $this;
    }

    /**
     * @return \Closure
     */
    private function mapper(): \Closure
    {
        return function ($id) {
            switch (true) {
                case \is_int($id):
                    return $id;
                case \is_string($id):
                    return $this->fetchId($id);
                default:
                    return $this->ids[] = \count($this->ids);
            }
        };
    }

    /**
     * @param string $expr
     * @return int
     */
    private function fetchId(string $expr): int
    {
        if (! isset($this->ids[$expr])) {
            $this->ids[$expr] = \count($this->ids);
        }

        return $this->ids[$expr];
    }

    /**
     * @return array|RuleInterface[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        return $this->lexer->build();
    }
}
