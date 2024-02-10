<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware;

use Phplrt\Parser\Context;
use Phplrt\Parser\Middleware\Pipeline\ComposeHandler;

final class Pipeline implements MutablePipelineInterface
{
    /**
     * @var list<MiddlewareInterface>
     */
    private array $handlers = [];

    /**
     * @var \WeakMap<HandlerInterface, HandlerInterface>
     */
    private \WeakMap $compose;

    public function __construct()
    {
        $this->compose = new \WeakMap();
    }

    public function process(Context $context, HandlerInterface $handler): mixed
    {
        $current = ($this->compose[$handler] ??= $this->compose($handler));

        return $current->handle($context);
    }

    /**
     * @param HandlerInterface $handler
     * @return HandlerInterface
     */
    private function compose(HandlerInterface $handler): HandlerInterface
    {
        foreach (\array_reverse($this->handlers) as $middleware) {
            $handler = $this->next($middleware, $handler);
        }

        return $handler;
    }

    private function next(MiddlewareInterface $middleware, HandlerInterface $handler): HandlerInterface
    {
        return new ComposeHandler($middleware, $handler);
    }

    public function add(MiddlewareInterface $middleware, AddOrder $order = AddOrder::APPEND): void
    {
        if ($order === AddOrder::APPEND) {
            $this->handlers[] = $middleware;
        } else {
            \array_unshift($this->handlers, $middleware);
        }

        $this->cleanup();
    }

    public function with(MiddlewareInterface $middleware, AddOrder $order = AddOrder::APPEND): PipelineInterface
    {
        $self = clone $this;
        $self->add($middleware, $order);

        return $self;
    }

    private function cleanup(): void
    {
        foreach ($this->compose as $key => $value) {
            unset($this->compose[$key]);
        }
    }
}
