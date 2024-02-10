<?php

declare(strict_types=1);

namespace Phplrt\Parser\Environment;

final class Factory implements SelectorInterface
{
    /**
     * @param list<SelectorInterface> $selectors
     */
    public function __construct(
        private readonly array $selectors = [
            new XdebugSelector(),
        ],
    ) {
    }

    public function prepare(): void
    {
        foreach ($this->selectors as $handler) {
            $handler->prepare();
        }
    }

    public function rollback(): void
    {
        foreach ($this->selectors as $handler) {
            $handler->rollback();
        }
    }
}
