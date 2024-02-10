<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Context\ContextOptionsProviderInterface;
use Phplrt\Parser\Context\ContextOptionsTrait;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * This is an internal implementation of parser mechanisms and modifying the
 * value of fields outside can disrupt the operation of parser's algorithms.
 *
 * The presence of public modifiers in fields is required only to speed up
 * the parser, since direct access is several times faster than using methods
 * of setting values or creating a new class at each step of the parser.
 */
final class Context implements ContextOptionsProviderInterface
{
    use ContextOptionsTrait;

    /**
     * Contains the most recent token object in the token list
     * (buffer) which was last successfully processed in the ALL rules chain.
     *
     * It is required so that in case of errors it is possible to report that
     * it was on it that the problem arose.
     *
     * Please note that this value contains the last in the list of processed
     * ones, and not the last in time that was processed.
     *
     * @readonly Changing of this value is only available while the parser
     *           is running. Please do not manually change this value.
     * @psalm-readonly
     */
    public ?TokenInterface $lastProcessedToken = null;

    /**
     * Contains the token object which was last successfully processed
     * in the CURRENT rule.
     *
     * Please note that this value contains the last token in time, and not
     * the last in order in the buffer, unlike the value of
     * the {@see $lastProcessedToken}.
     *
     * @readonly Changing of this value is only available while the parser
     *           is running. Please do not manually change this value.
     * @psalm-readonly
     */
    public TokenInterface $token;

    /**
     * Contains the AST node object which was last successfully
     * processed while parsing.
     *
     * @readonly Changing of this value is only available while the parser
     *           is running. Please do not manually change this value.
     * @psalm-readonly
     */
    public ?object $node = null;

    /**
     * Contains the parser's current rule.
     *
     * @readonly Changing of this value is only available while the parser
     *           is running. Please do not manually change this value.
     * @psalm-readonly
     */
    public ?RuleInterface $rule = null;

    /**
     * @param ReadableInterface $source Contains information about the
     *        processed source.
     * @param BufferInterface $buffer  Contains a buffer of tokens that
     *        were collected from lexical analysis.
     * @param int<0, max>|non-empty-string $state Contains the identifier
     *        of the current state of the parser.
     * @param array<non-empty-string, mixed> $options
     */
    public function __construct(
        public readonly ReadableInterface $source,
        public readonly BufferInterface $buffer,
        public int|string $state,
        array $options,
    ) {
        $this->options = $options;

        $this->lastProcessedToken = $this->token = $this->buffer->current();
    }

    public function getBuffer(): BufferInterface
    {
        return $this->buffer;
    }

    public function getSource(): ReadableInterface
    {
        return $this->source;
    }

    public function getNode(): ?object
    {
        return $this->node;
    }

    public function getRule(): RuleInterface
    {
        assert($this->rule !== null, 'Context not initialized');

        return $this->rule;
    }

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function getState(): int|string
    {
        assert($this->state !== null, 'Context not initialized');

        return $this->state;
    }
}
