<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware;

/**
 * The handler responsible for calling the parser rules.
 */
interface PipelineInterface extends MiddlewareInterface
{
    /**
     * @psalm-immutable
     */
    public function with(MiddlewareInterface $middleware, AddOrder $order = AddOrder::APPEND): self;
}
