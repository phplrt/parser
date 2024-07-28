<?php

declare(strict_types=1);

namespace Phplrt\Parser\Context;

/**
 * @psalm-require-implements ContextOptionsProviderInterface
 *
 * @mixin ContextOptionsProviderInterface
 */
trait ContextOptionsTrait
{
    /**
     * @var array<non-empty-string, mixed>
     */
    protected array $options = [];

    /**
     * @return array<non-empty-string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        //
        // Options access optimisation:
        //
        // Operator "isset" transforms to
        // "ZEND_ISSET_ISEMPTY_VAR" ZendVM opcode which 2-3 times faster than
        // "DO_FCALL" generated by "array_key_exists" function.
        //
        return isset($this->options[$name]) || \array_key_exists($name, $this->options);
    }
}
