<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

abstract class Terminal extends Rule implements TerminalInterface
{
    /**
     * @var bool
     */
    public bool $keep = true;

    /**
     * @param bool $keep
     */
    public function __construct(bool $keep)
    {
        $this->keep = $keep;
    }

    /**
     * @return bool
     */
    public function isKeep(): bool
    {
        return $this->keep;
    }
}
