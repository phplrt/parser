<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Lexer\Token\Renderer;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Buffer\EagerBuffer;
use Phplrt\Parser\Builder\BuilderInterface;
use Phplrt\Parser\Builder\Common;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Rule\ProductionInterface;
use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Parser\Rule\TerminalInterface;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\File;
use Phplrt\Source\ReadableInterface;

/**
 * A recurrence recursive descent parser implementation.
 *
 * Is a kind of top-down parser built from a set of mutually recursive methods
 * defined in:
 *  - Phplrt\Parser\Rule\ProductionInterface::reduce()
 *  - Phplrt\Parser\Rule\TerminalInterface::reduce()
 *
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
 * NOTE: Vulnerable to left recursion, like:
 *
 * <code>
 *      Digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;
 *      Operator = "+" | "-" | "*" | "/" ;
 *      Number = Digit { Digit } ;
 *
 *      Expression = Number | Number Operator ;
 *      (*           ^^^^^^   ^^^^^^
 *          In this case, the grammar is incorrect and should be replaced by:
 *
 *          Expression = Number { Operator } ;
 *      *)
 * </code>
 */
class Parser implements ParserInterface
{
    /**
     * @var string
     */
    public const CONFIG_INITIAL_RULE = 'initial';

    /**
     * @var string
     */
    public const CONFIG_AST_BUILDER = 'builder';

    /**
     * @var string
     */
    public const CONFIG_BUFFER = 'buffer';

    /**
     * @var string
     */
    public const CONFIG_EOI = 'eoi';

    /**
     * @var string
     */
    private const ERROR_XDEBUG_NOTICE_MESSAGE =
        'Please note that if Xdebug is enabled, a "Fatal error: Maximum function nesting level of "%d" ' .
        'reached, aborting!" errors may occur. In the second case, it is worth increasing the ini value ' .
        'or disabling the extension.';

    /**
     * Contains the readonly token object which was last successfully processed
     * in the rules chain.
     *
     * It is required so that in case of errors it is possible to report that
     * it was on it that the problem arose.
     *
     * Note: This is a stateful data and may cause a race condition error. In
     * the future, it is necessary to delete this data with a replacement for
     * the stateless structure.
     *
     * @var TokenInterface|null
     */
    private $token;

    /**
     * Contains the readonly NodeInterface object which was last successfully
     * processed while parsing.
     *
     * Note: This is a stateful data and may cause a race condition error. In
     * the future, it is necessary to delete this data with a replacement for
     * the stateless structure.
     *
     * @var NodeInterface|null
     */
    private $node;

    /**
     * A buffer class that allows you to iterate over the stream of tokens and
     * return to the selected position.
     *
     * Initialized by the generator with tokens during parser launch.
     *
     * @var string
     */
    private $buffer = EagerBuffer::class;

    /**
     * An abstract syntax tree builder.
     *
     * @var BuilderInterface
     */
    private $builder;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var string|int|null
     */
    private $initial;

    /**
     * The lexer instance.
     *
     * @var LexerInterface
     */
    private $lexer;

    /**
     * Array of transition rules for the parser.
     *
     * @var array|RuleInterface[]
     */
    private $rules;

    /**
     * Token indicating the end of parsing.
     *
     * @var string
     */
    private $eoi = TokenInterface::END_OF_INPUT;

    /**
     * Parser constructor.
     *
     * @param LexerInterface $lexer
     * @param array|RuleInterface[] $rules
     * @param array $options
     */
    public function __construct(LexerInterface $lexer, array $rules, array $options = [])
    {
        $this->lexer = $lexer;
        $this->rules = $rules;

        $this->bootConfigs($options);

        $this->boot();
    }

    /**
     * @param array $options
     * @return void
     */
    private function bootConfigs(array $options): void
    {
        $this->eoi     = $options[static::CONFIG_EOI] ?? $this->eoi;
        $this->buffer  = $options[static::CONFIG_BUFFER] ?? $this->buffer;
        $this->builder = $options[static::CONFIG_AST_BUILDER] ?? $this->getDefaultBuilder();
        $this->initial = $options[static::CONFIG_INITIAL_RULE] ?? $this->getDefaultInitialRule($this->rules);
    }

    /**
     * @param array $rules
     * @return int|mixed|string|null
     */
    private function getDefaultInitialRule(array $rules)
    {
        return \count($rules) ? \array_key_first($rules) : 0;
    }

    /**
     * @return BuilderInterface
     */
    private function getDefaultBuilder(): BuilderInterface
    {
        return new Common();
    }

    /**
     * @return void
     */
    private function boot(): void
    {
        if (\function_exists('\\xdebug_is_enabled')) {
            @\trigger_error(\vsprintf(self::ERROR_XDEBUG_NOTICE_MESSAGE, [
                \ini_get('xdebug.max_nesting_level'),
            ]));
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param string|resource|ReadableInterface|mixed $source
     * @throws \Throwable
     */
    public function parse($source): iterable
    {
        if (\count($this->rules) === 0) {
            return [];
        }

        return $this->run($this->open($source));
    }

    /**
     * @param ReadableInterface $source
     * @return iterable
     * @throws \Throwable
     */
    private function run(ReadableInterface $source): iterable
    {
        $buffer = $this->getBuffer($this->lex($source));

        $this->reset($buffer);

        return $this->parseOrFail($source, $buffer);
    }

    /**
     * @param \Generator $stream
     * @return BufferInterface
     */
    private function getBuffer(\Generator $stream): BufferInterface
    {
        \assert(
            \is_subclass_of($this->buffer, BufferInterface::class),
            'Buffer class should implement ' . BufferInterface::class
        );

        $class = $this->buffer;

        return new $class($stream);
    }

    /**
     * @param ReadableInterface $source
     * @return \Generator
     */
    private function lex(ReadableInterface $source): \Generator
    {
        yield from $this->lexer->lex($source->getContents());
    }

    /**
     * @param BufferInterface $buffer
     * @return void
     */
    private function reset(BufferInterface $buffer): void
    {
        $this->token = $buffer->current();
        $this->node  = null;
    }

    /**
     * @param ReadableInterface $source
     * @param BufferInterface $buffer
     * @return iterable
     * @throws \Throwable
     */
    private function parseOrFail(ReadableInterface $source, BufferInterface $buffer): iterable
    {
        $result = $this->next($source, $buffer, $this->initial);

        if (\is_iterable($result) && $this->isEoi($buffer)) {
            return $result;
        }

        $message = \vsprintf(ParserRuntimeException::ERROR_UNEXPECTED_TOKEN, [
            $this->render($this->token ?? $buffer->current()),
        ]);

        throw new ParserRuntimeException($message, $this->token ?? $buffer->current());
    }

    /**
     * @param ReadableInterface $source
     * @param BufferInterface $buffer
     * @param string|int|mixed $state
     * @return mixed
     */
    protected function next(ReadableInterface $source, BufferInterface $buffer, $state)
    {
        return $this->reduce($source, $buffer, $state);
    }

    /**
     * @param ReadableInterface $source
     * @param BufferInterface $buffer
     * @param int|string $state
     * @return iterable|TokenInterface|null
     */
    private function reduce(ReadableInterface $source, BufferInterface $buffer, $state)
    {
        /** @var TokenInterface $token */
        [$rule, $result, $token] = [$this->rules[$state], null, $buffer->current()];

        switch (true) {
            case $rule instanceof ProductionInterface:
                $result = $rule->reduce($buffer, function ($state) use ($source, $buffer) {
                    return $this->next($source, $buffer, $state);
                });

                break;

            case $rule instanceof TerminalInterface:
                $result = $rule->reduce($buffer);

                if ($result !== null) {
                    $buffer->next();

                    $this->spotTerminal($buffer);

                    if (! $rule->isKeep()) {
                        return [];
                    }
                }

                break;
        }

        if ($result === null) {
            return null;
        }

        // Assert reducer type
        \assert(
            $result instanceof TokenInterface || $result instanceof NodeInterface || \is_array($result),
            'Reducer constraint violation: ' . (\is_object($result) ? \get_class($result) : \gettype($result))
        );

        return $this->build($token, $state, $result);
    }

    /**
     * Capture the most recently processed token.
     * In case of a syntax error, it will be displayed as incorrect.
     *
     * @param BufferInterface $buffer
     * @return void
     */
    private function spotTerminal(BufferInterface $buffer): void
    {
        if ($buffer->current()->getOffset() > $this->token->getOffset()) {
            $this->token = $buffer->current();
        }
    }

    /**
     * @param TokenInterface $token
     * @param int|string $state
     * @param mixed $result
     * @return mixed|null
     */
    private function build(TokenInterface $token, $state, $result)
    {
        $result = $this->builder->build($this->rules[$state], $token, $state, $result) ?? $result;

        if ($result instanceof NodeInterface) {
            $this->node = $result;
        }

        return $result;
    }

    /**
     * Matches a token identifier that marks the end of the source.
     *
     * @param BufferInterface $buffer
     * @return bool
     */
    private function isEoi(BufferInterface $buffer): bool
    {
        $current = $buffer->current();

        return $current->getName() === $this->eoi;
    }

    /**
     * @param TokenInterface $token
     * @return string
     */
    private function render(TokenInterface $token): string
    {
        if (\class_exists(Renderer::class)) {
            return (new Renderer())->value($token);
        }

        return '"' . $token->getValue() . '"';
    }

    /**
     * @param string|resource|mixed $source
     * @return ReadableInterface
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    private function open($source): ReadableInterface
    {
        return File::new($source);
    }
}
