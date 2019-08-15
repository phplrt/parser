<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Rule\TerminalInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Rule\ProductionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;

/**
 * A LL(k) recurrence recursive descent parser implementation.
 *
 * Is a kind of top-down parser built from a set of mutually recursive methods
 * defined in:
 *  - Phplrt\Parser\Rule\ProductionInterface::reduce()
 *  - Phplrt\Parser\Rule\TerminalInterface::reduce()
 * Where each such class implements one of the terminals or productions of the
 * grammar. Thus the structure of the resulting program closely mirrors that
 * of the grammar it recognizes.
 *
 * A "recurrence" means that instead of predicting, the parser simply tries to
 * apply all the alternative rules in order, until one of the attempts succeeds.
 *
 * Such a parser may require exponential work time, and does not always
 * guarantee completion, depending on the grammar.
 *
 * Vulnerable to left recursion, like:
 * <code>
 *      Digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;
 *      Operator = "+" | "-" | "*" | "/" ;
 *      Number = Digit { Digit } ;
 *
 *      Expression = Number | Number Operator ;
 *      (*            ^^^^^^   ^^^^^^
 *          In this case, the grammar is incorrect and should be replaced by:
 *
 *          Expression = Number { Operator } ;
 *      *)
 * </code>
 */
class LL extends AbstractParser
{
    /**
     * @var string
     */
    private const ERROR_XDEBUG_NOTICE_MESSAGE =
        'Please note that if Xdebug is enabled, a "Fatal error: Maximum function nesting level of "%d" ' .
        'reached, aborting!" errors may occur. In the second case, it is worth increasing the ini value ' .
        'or disabling the extension.';

    /**
     * @var array|RuleInterface[]
     */
    private $rules;

    /**
     * Parser constructor.
     *
     * @param array|RuleInterface[] $rules
     * @param LexerInterface $lexer
     */
    public function __construct(LexerInterface $lexer, array $rules)
    {
        parent::__construct($lexer);

        $this->detectDebuggers();
        $this->boot($this->rules = $rules);
    }

    /**
     * @param array $rules
     * @return void
     */
    private function boot(array $rules): void
    {
        if ($this->initial === null) {
            $this->initial = \array_key_first($rules);
        }
    }

    /**
     * @return void
     */
    private function detectDebuggers(): void
    {
        if (\function_exists('\\xdebug_is_enabled')) {
            @\trigger_error(\vsprintf(self::ERROR_XDEBUG_NOTICE_MESSAGE, [
                \ini_get('xdebug.max_nesting_level')
            ]));
        }
    }

    /**
     * {@inheritDoc}
     * @throws NotReadableExceptionInterface
     */
    public function parse(ReadableInterface $src): iterable
    {
        $this->rollbacks = $this->reduces = 0;

        $buffer = $this->lex($src);

        if (($result = $this->reduce($buffer, $this->initial)) === null) {
            throw $this->error($src, $this->token ?? $buffer->current());
        }

        if ($buffer->current()->getType() !== $this->eoi) {
            throw $this->error($src, $buffer->current());
        }

        return $this->normalize($result);
    }

    /**
     * @param BufferInterface $buffer
     * @param int $state
     * @return iterable|TokenInterface|null
     */
    private function reduce(BufferInterface $buffer, int $state)
    {
        [$rule, $token, $result] = [$this->rules[$state], $buffer->current(), null];

        $this->reduces++;

        switch (true) {
            case $token->getType() === $this->eoi:
                $result = null;
                break;

            case $rule instanceof ProductionInterface:
                $result = $rule->reduce($buffer, $state, $token->getOffset(), function (int $state) use ($buffer) {
                    return $this->reduce($buffer, $state);
                });
                break;

            case $rule instanceof TerminalInterface:
                if ($result = $rule->reduce($buffer)) {
                    $this->token = $result;

                    $buffer->next();
                }
                break;
        }

        if ($result === null) {
            $this->rollbacks++;
        }

        return $result;
    }
}
