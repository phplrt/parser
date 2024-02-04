<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Functional\Stub;

use Phplrt\Parser\Grammar\RuleInterface;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Parser\Tests\Unit
 */
class Rule implements RuleInterface
{
    public static function new(): self
    {
        return new self();
    }

    public function getTerminals(array $rules): iterable
    {
        return [];
    }
}
