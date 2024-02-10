<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware;

interface MutablePipelineInterface extends PipelineInterface
{
    public function add(MiddlewareInterface $middleware, AddOrder $order = AddOrder::APPEND): void;
}
