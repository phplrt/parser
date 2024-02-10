<?php

declare(strict_types=1);

namespace Phplrt\Parser\Context;

use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\Context;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Parser
 */
final class TreeBuilder implements BuilderInterface
{
    /**
     * @param array<int<0, max>|non-empty-string, callable(Context, mixed):mixed> $reducers
     */
    public function __construct(
        private readonly array $reducers,
    ) {
    }

    public function build(Context $context, mixed $result): mixed
    {
        if (isset($this->reducers[$context->state])) {
            return ($this->reducers[$context->state])($context, $result);
        }

        return $result;
    }
}
