<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Buffer\Factory;
use Phplrt\Parser\Buffer\FactoryInterface;
use Phplrt\Parser\Context\TreeBuilder;
use Phplrt\Parser\Environment\Factory as EnvironmentFactory;
use Phplrt\Parser\Environment\SelectorInterface;
use Phplrt\Parser\Exception\LexerInitializationException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Exception\UnrecognizedTokenException;
use Phplrt\Parser\Grammar\ProductionInterface;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Middleware\HandlerInterface;
use Phplrt\Parser\Middleware\Pipeline;
use Phplrt\Parser\Middleware\Pipeline\ClosureHandler;
use Phplrt\Parser\Middleware\PipelineInterface;
use Phplrt\Source\File;

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
final class Parser implements ParserInterface
{
    /**
     * The {@see BuilderInterface} is responsible for building
     * the Abstract Syntax Tree.
     */
    private BuilderInterface $builder;

    /**
     * The {@see SelectorInterface} is responsible for preparing
     * and analyzing the PHP environment for the parser to work.
     */
    private readonly SelectorInterface $env;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var non-empty-string|int
     */
    private readonly string|int $initial;

    /**
     * Contains an initializing pipeline handler (the {@see next()} method).
     */
    private readonly HandlerInterface $handler;

    /**
     * @param LexerInterface $lexer A Lexer implementation instance.
     * @param array<int|non-empty-string, RuleInterface> $grammar Array of
     *        transition rules for the parser.
     * @param BuilderInterface|array<int|non-empty-string, \Closure(Context,mixed):mixed> $builder
     * @param non-empty-string|int<0, max>|null $initial Initial rule/state
     *        identifier name.
     * @param array<non-empty-string, mixed> $options Arbitrary parser options
     *        to be passed to the execution context ({@see Context::$options}).
     */
    public function __construct(
        private readonly LexerInterface $lexer,
        private readonly array $grammar,
        int|string|null $initial = null,
        BuilderInterface|array $builder = [],
        private readonly PipelineInterface $pipeline = new Pipeline(),
        private readonly FactoryInterface $buffer = new Factory(),
        private readonly array $options = [],
    ) {
        $this->env = new EnvironmentFactory();
        $this->builder = self::bootBuilder($builder);
        $this->initial = self::bootInitialRule($initial, $this->grammar);
        $this->handler = new ClosureHandler($this->next(...));
    }

    /**
     * The method is responsible for initializing the
     * Abstract Syntax Tree builder.
     *
     * @param BuilderInterface|array<int|non-empty-string, callable(Context, mixed):mixed> $builder
     */
    private static function bootBuilder(BuilderInterface|array $builder): BuilderInterface
    {
        if ($builder instanceof BuilderInterface) {
            return $builder;
        }

        return new TreeBuilder($builder);
    }

    /**
     * The method is responsible for initializing the initial
     * state of the grammar.
     *
     * @param non-empty-string|int<0, max>|null $initial
     * @return non-empty-string|int<0, max>
     */
    private static function bootInitialRule(int|string|null $initial, array $grammar): int|string
    {
        if ($initial !== null) {
            return $initial;
        }

        $result = \array_key_first($grammar);

        if ($result === false) {
            return 0;
        }

        return $result;
    }

    public function parse(mixed $source, array $options = []): iterable
    {
        $source = File::new($source);

        if ($this->grammar === []) {
            return [];
        }

        $this->env->prepare();

        try {
            $context = $this->createExecutionContext($source, $options);
            $context->rule = $this->grammar[$context->state];

            $result = $this->pipeline->process($context, $this->handler);

            if (\is_iterable($result) && $this->isEoi($context->buffer)) {
                return $result;
            }

            throw UnexpectedTokenException::fromUnexpectedToken(
                $context->getSource(),
                $context->lastProcessedToken ?? $context->buffer->current(),
            );
        } finally {
            $this->env->rollback();
        }
    }

    /**
     * Creates a parsing execution context.
     *
     * @param array<non-empty-string, mixed> $options User options that are
     *        passed to the execution context.
     */
    private function createExecutionContext(ReadableInterface $source, array $options): Context
    {
        $buffer = $this->createExecutionBuffer($source);

        return new Context($source, $buffer, $this->initial, [...$this->options, ...$options]);
    }

    /**
     * Creates a temporary buffer from tokens for subsequent parsing
     * and building a syntax tree.
     *
     * @throws UnrecognizedTokenException Occurs when errors occur during
     *         lexical analysis execution.
     * @throws LexerInitializationException Occurs in case of lexer
     *         initialization errors.
     */
    private function createExecutionBuffer(ReadableInterface $source): BufferInterface
    {
        try {
            return $this->buffer->create(
                $this->lexer->lex($source)
            );
        } catch (LexerRuntimeExceptionInterface $e) {
            throw UnrecognizedTokenException::fromUnrecognizedToken($source, $e->getToken(), $e);
        } catch (LexerExceptionInterface $e) {
            throw LexerInitializationException::fromLexerException($e);
        }
    }

    private function next(Context $context): mixed
    {
        $context->rule = $this->grammar[$context->state];

        if ($context->rule instanceof ProductionInterface) {
            $result = $context->rule->reduce($context->buffer, function ($state) use ($context): mixed {
                // Keep current state
                $previousState = $context->state;
                $previousToken = $context->token;
                $previousRule  = $context->rule;

                // Update state
                $context->state = $state;
                $context->token = $context->buffer->current();
                $context->rule  = $this->grammar[$context->state];

                $result = $this->pipeline->process($context, $this->handler);

                // Rollback previous state
                $context->state = $previousState;
                $context->token = $previousToken;
                $context->rule  = $previousRule;

                return $result;
            });
        } else {
            $result = $context->rule->reduce($context->buffer);

            if ($result !== null) {
                $context->buffer->next();

                if ($context->buffer->current()->getOffset() > $context->lastProcessedToken->getOffset()) {
                    $context->lastProcessedToken = $context->buffer->current();
                }

                if (!$context->rule->isKeep()) {
                    return [];
                }
            }
        }

        if ($result === null) {
            return null;
        }

        $result = $this->builder->build($context, $result) ?? $result;

        if (\is_object($result)) {
            $context->node = $result;
        }

        return $result;
    }

    /**
     * Matches a token identifier that marks the end of the source.
     */
    private function isEoi(BufferInterface $buffer): bool
    {
        $current = $buffer->current();

        return $current->getChannel() === Channel::EOI;
    }
}
