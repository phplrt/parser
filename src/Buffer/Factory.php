<?php

declare(strict_types=1);

namespace Phplrt\Parser\Buffer;

final class Factory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(iterable $tokens): BufferInterface
    {
        return new ArrayBuffer($tokens);
    }
}
