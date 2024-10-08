<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Parser\ParserExceptionInterface;
use Phplrt\Contracts\Parser\ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Parser\Context\TreeBuilder;
use Phplrt\Parser\Environment\Factory as EnvironmentFactory;
use Phplrt\Parser\Environment\SelectorInterface;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Exception\UnrecognizedTokenException;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\ProductionInterface;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\TerminalInterface;
use Phplrt\Source\SourceFactory;

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
 *
 * @template TNode of object
 * @template-implements ConfigurableParserInterface<TNode>
 */
final class Parser implements ConfigurableParserInterface, ParserConfigsInterface
{
    use ParserConfigsTrait;

    /**
     * @var non-empty-string
     */
    private const ERROR_BUFFER_TYPE = 'Buffer class should implement %s interface';

    /**
     * The {@see SelectorInterface} is responsible for preparing
     * and analyzing the PHP environment for the parser to work.
     */
    private readonly SelectorInterface $env;

    /**
     * The {@see BuilderInterface} is responsible for building the Abstract
     * Syntax Tree.
     *
     * @readonly will contain the PHP readonly attribute starting with phplrt 4.0.
     *
     * @psalm-readonly-allow-private-mutation
     */
    private BuilderInterface $builder;

    /**
     * Sources factory.
     */
    private readonly SourceFactoryInterface $sources;

    /**
     * The initial state (initial rule identifier) of the parser.
     *
     * @var array-key
     */
    private string|int $initial;

    /**
     * Array of transition rules for the parser.
     *
     * @var array<array-key, RuleInterface>
     */
    private readonly array $rules;

    private ?Context $context = null;

    /**
     * @param iterable<array-key, RuleInterface> $grammar an iterable of the
     *        transition rules for the parser
     * @param array<ParserConfigsInterface::CONFIG_*, mixed> $options
     */
    public function __construct(
        /**
         * The lexer instance.
         */
        private readonly LexerInterface $lexer,
        iterable $grammar = [],
        array $options = [],
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->env = new EnvironmentFactory();

        $this->rules = self::bootGrammar($grammar);
        $this->builder = self::bootBuilder($options);
        $this->initial = self::bootInitialRule($options, $this->rules);
        $this->sources = self::bootSourcesFactory($sources);

        $this->bootParserConfigsTrait($options);
    }

    private static function bootSourcesFactory(?SourceFactoryInterface $factory): SourceFactoryInterface
    {
        return $factory ?? new SourceFactory();
    }

    /**
     * @param array{
     *     builder?: BuilderInterface|iterable<int|non-empty-string, \Closure(Context,mixed):mixed>|null
     * } $options
     */
    private static function bootBuilder(array $options): BuilderInterface
    {
        $builder = $options[self::CONFIG_AST_BUILDER] ?? [];

        if ($builder instanceof BuilderInterface) {
            return $builder;
        }

        return new TreeBuilder($builder);
    }

    /**
     * @param iterable<array-key, RuleInterface> $grammar
     *
     * @return array<array-key, RuleInterface>
     */
    private static function bootGrammar(iterable $grammar): array
    {
        if ($grammar instanceof \Traversable) {
            return \iterator_to_array($grammar);
        }

        return $grammar;
    }

    /**
     * The method is responsible for initializing the initial
     * state of the grammar.
     *
     * @param array{
     *     initial?: array-key|null
     * } $options
     * @param array<array-key, RuleInterface> $grammar
     *
     * @return array-key
     */
    private static function bootInitialRule(array $options, array $grammar): int|string
    {
        $initial = $options[self::CONFIG_INITIAL_RULE] ?? null;

        if ($initial !== null) {
            return $initial;
        }

        $result = \array_key_first($grammar);

        if ($result === false || $result === null) {
            return 0;
        }

        return $result;
    }

    /**
     * Sets an initial state (initial rule identifier) of the parser.
     *
     * @param array-key $initial
     *
     * @deprecated since phplrt 3.4 and will be removed in 4.0
     */
    public function startsAt(string|int $initial): self
    {
        trigger_deprecation('phplrt/parser', '3.4', <<<'MSG'
            Using "%s::startsAt(array-key)" is deprecated, please use "%1$s::__construct()" instead.
            MSG, self::class);

        $this->initial = $initial;

        return $this;
    }

    /**
     * Sets an abstract syntax tree builder.
     *
     * @deprecated since phplrt 3.4 and will be removed in 4.0
     */
    public function buildUsing(BuilderInterface $builder): self
    {
        trigger_deprecation('phplrt/parser', '3.4', <<<'MSG'
            Using "%s::buildUsing(BuilderInterface)" is deprecated, please use "%1$s::__construct()" instead.
            MSG, self::class);

        $this->builder = $builder;

        return $this;
    }

    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @param mixed $source any source supported by the {@see SourceFactoryInterface::create()}
     * @param array<non-empty-string, mixed> $options list of additional
     *        runtime options for the parser (parsing context)
     *
     * @return iterable<array-key, TNode>
     * @throws ParserExceptionInterface an error occurs before source processing
     *         starts, when the given source cannot be recognized or if the
     *         parser settings contain errors
     * @throws ParserRuntimeExceptionInterface an exception that occurs after
     *         starting the parsing and indicates problems in the analyzed
     *         source
     */
    public function parse(mixed $source, array $options = []): iterable
    {
        if ($this->rules === []) {
            return [];
        }

        $this->env->prepare();

        try {
            $source = $this->sources->create($source);
        } catch (\Throwable $e) {
            throw ParserException::fromInternalError($e);
        }

        try {
            $buffer = $this->createBufferFromSource($source);

            $this->context = new Context($buffer, $source, $this->initial, $options);

            return $this->parseOrFail($this->context);
        } finally {
            $this->env->rollback();
        }
    }

    private function createBufferFromTokens(iterable $stream): BufferInterface
    {
        \assert(
            \is_subclass_of($this->buffer, BufferInterface::class),
            \sprintf(self::ERROR_BUFFER_TYPE, BufferInterface::class)
        );

        $class = $this->buffer;

        return new $class($stream);
    }

    /**
     * @throws ParserRuntimeExceptionInterface
     */
    private function createBufferFromSource(ReadableInterface $source): BufferInterface
    {
        try {
            return $this->createBufferFromTokens(
                $this->lexer->lex($source),
            );
        } catch (RuntimeExceptionInterface $e) {
            throw UnrecognizedTokenException::fromRuntimeException($e);
        } catch (LexerRuntimeExceptionInterface $e) {
            throw UnrecognizedTokenException::fromLexerRuntimeException($e);
        } catch (\Throwable $e) {
            throw ParserException::fromInternalError($e);
        }
    }

    /**
     * @throws ParserRuntimeException
     */
    private function parseOrFail(Context $context): iterable
    {
        $result = $this->next($context);

        if (\is_iterable($result)
            && ($this->allowTrailingTokens || $this->isEoi($context->buffer))
        ) {
            return $result;
        }

        $token = $context->lastOrdinalToken ?? $context->buffer->current();

        throw UnexpectedTokenException::fromToken(
            $context->getSource(),
            $token,
            null,
            $this->lookupExpectedTokens($context),
        );
    }

    /**
     * @return list<non-empty-string>
     */
    private function lookupExpectedTokens(Context $context): array
    {
        $rule = $context->rule ?? $this->rules[$this->initial] ?? null;

        if ($rule === null) {
            return [];
        }

        $tokens = [];

        foreach ($rule->getTerminals($this->rules) as $terminal) {
            if ($terminal instanceof Lexeme && \is_string($terminal->token)) {
                $tokens[$terminal->token] = $terminal->token;
            }

            if (\count($tokens) >= 3) {
                break;
            }
        }

        return \array_values($tokens);
    }

    private function next(Context $context): mixed
    {
        if ($this->step !== null) {
            return ($this->step)($context, fn($context): mixed => $this->runNextStep($context));
        }

        return $this->runNextStep($context);
    }

    private function runNextStep(Context $context): mixed
    {
        $rule = $context->rule = $this->rules[$context->state];
        $result = null;

        switch (true) {
            case $rule instanceof ProductionInterface:
                $result = $rule->reduce($context->buffer, function (int|string $state) use ($context) {
                    // Keep current state
                    $beforeState = $context->state;
                    $beforeLastProcessedToken = $context->lastProcessedToken;

                    // Update state
                    $context->state = $state;
                    $context->lastProcessedToken = $context->buffer->current();

                    $result = $this->next($context);

                    // Rollback previous state
                    $context->state = $beforeState;
                    $context->lastProcessedToken = $beforeLastProcessedToken;

                    return $result;
                });

                break;

            case $rule instanceof TerminalInterface:
                $result = $rule->reduce($context->buffer);

                if ($result !== null) {
                    $context->buffer->next();

                    if ($context->lastOrdinalToken === null
                        || $context->buffer->current()->getOffset() > $context->lastOrdinalToken->getOffset()) {
                        $context->lastOrdinalToken = $context->buffer->current();
                    }

                    if (!$rule->isKeep()) {
                        return [];
                    }
                }

                break;
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

        return $current->getName() === $this->eoi;
    }

    /**
     * Returns last execution context.
     *
     * Typically used in conjunction with the "tolerant" mode of the parser.
     *
     * ```
     *  $parser = new Parser(..., [Parser::CONFIG_ALLOW_TRAILING_TOKENS => true]);
     *  $parser->parse('...');
     *
     *  $context = $parser->getLastExecutionContext();
     *  var_dump($context->buffer->current()); // Returns the token where the parser stopped
     * ```
     *
     * @api
     */
    public function getLastExecutionContext(): ?Context
    {
        return $this->context;
    }
}
