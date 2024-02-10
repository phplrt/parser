<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware;

use Phplrt\Parser\Context;

/**
 * Participant in processing an execution context and result.
 */
interface MiddlewareInterface
{
    /**
     * Processes an incoming execution context in order to produce result.
     *
     * If unable to produce the result itself, it may delegate to the provided
     * handler to do so.
     */
    public function process(Context $context, HandlerInterface $handler): mixed;
}
