<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware;

use Phplrt\Parser\Context;

/**
 * Handles a {@see Context} and produces an execution result.
 *
 * @internal In most cases, this interface is implemented inside the parser.
 * @psalm-internal Phplrt\Parser
 */
interface HandlerInterface
{
    /**
     * Handles a {@see Context} and produces an execution result.
     *
     * May call other collaborating code to generate the result.
     */
    public function handle(Context $context): mixed;
}
