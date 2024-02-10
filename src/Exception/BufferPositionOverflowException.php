<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

class BufferPositionOverflowException extends BufferException
{
    final public const CODE_POSITION_OVERFLOW = 0x01 + parent::CODE_LAST;

    protected const CODE_LAST = self::CODE_POSITION_OVERFLOW;

    public static function fromOffsetOverflow(int $expected, int $last): self
    {
        $message = 'Can not seek to position %d, because the last buffer token has an index %d';
        $message = \sprintf($message, $expected, $last);

        return new static($message, self::CODE_POSITION_OVERFLOW);
    }
}
