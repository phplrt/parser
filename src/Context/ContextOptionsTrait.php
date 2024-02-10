<?php

declare(strict_types=1);

namespace Phplrt\Parser\Context;

/**
 * @mixin ContextOptionsProviderInterface
 * @psalm-require-implements ContextOptionsProviderInterface
 */
trait ContextOptionsTrait
{
    /**
     * @var array<non-empty-string, mixed>
     */
    protected readonly array $options;

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]) || \array_key_exists($name, $this->options);
    }
}
