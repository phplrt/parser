<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Unit;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Context\ContextOptionsProviderInterface;
use Phplrt\Parser\Environment\SelectorInterface;
use Phplrt\Parser\Exception\ParserExceptionInterface;
use Phplrt\Parser\Grammar\ProductionInterface;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\TerminalInterface;
use Phplrt\Parser\ParserConfigsInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Note: Changing the behavior of these tests is allowed ONLY when updating
 *       a MAJOR version of the package.
 */
#[Group('phplrt/parser'), Group('unit')]
class CompatibilityTest extends TestCase
{
    public function testBuilderCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements BuilderInterface {
            public function build(Context $context, mixed $result): mixed {}
        };
    }

    public function testProductionCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements ProductionInterface {
            public function reduce(BufferInterface $buffer, \Closure $reduce): mixed {}
            public function getTerminals(array $rules): iterable {}
        };
    }

    public function testRuleCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements RuleInterface {
            public function getTerminals(array $rules): iterable {}
        };
    }

    public function testTerminalCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements TerminalInterface {
            public function getTerminals(array $rules): iterable {}
            public function reduce(BufferInterface $buffer): ?TokenInterface {}
            public function isKeep(): bool {}
        };
    }

    public function testSelectorCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements SelectorInterface {
            public function prepare(): void {}
            public function rollback(): void {}
        };
    }

    public function testContextOptionsProviderCompatibility(): void
    {
        self::expectNotToPerformAssertions();

        new class () implements ContextOptionsProviderInterface {
            public function getOptions(): array {}
            public function getOption(string $name, mixed $default = null): mixed {}
            public function hasOption(string $name): bool {}
        };
    }
}
