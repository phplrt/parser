<?php

declare(strict_types=1);

namespace Phplrt\Parser;

interface BuilderInterface
{
    /**
     * @return mixed|null
     */
    public function build(Context $context, mixed $result): mixed;
}
