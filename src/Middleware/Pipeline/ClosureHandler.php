<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware\Pipeline;

use Phplrt\Parser\Context;
use Phplrt\Parser\Middleware\HandlerInterface;

/**
 * @template TReturn of mixed
 */
final class ClosureHandler implements HandlerInterface
{
    /**
     * @param \Closure(Context):TReturn $handler
     */
    public function __construct(
        private readonly \Closure $handler,
    ) {
    }

    /**
     * @return TReturn
     */
    public function handle(Context $context): mixed
    {
        return ($this->handler)($context);
    }
}
