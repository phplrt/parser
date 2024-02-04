<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Functional\Stub;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Parser\Tests\Unit
 */
class AstNode implements NodeInterface
{
    public function __construct(public string $name, public array $children = []) {}

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function __set($name, $value)
    {
        $this->children[$name] = $value;
    }
}
