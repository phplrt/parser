<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware\Pipeline;

use Phplrt\Parser\Context;
use Phplrt\Parser\Middleware\HandlerInterface;
use Phplrt\Parser\Middleware\MiddlewareInterface;

final class ComposeHandler implements HandlerInterface
{
    public function __construct(
        private readonly MiddlewareInterface $middleware,
        private readonly HandlerInterface $handler,
    ) {
    }

    public function handle(Context $context): mixed
    {
        return $this->middleware->process($context, $this->handler);
    }
}
