<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

class BufferPositionUnderflowException extends BufferException
{
    final public const CODE_POSITION_UNDERFLOW = 0x01 + parent::CODE_LAST;

    protected const CODE_LAST = self::CODE_POSITION_UNDERFLOW;

    public static function fromOffsetUnderflow(int $expected, int $first): static
    {
        $message = 'Can not seek to a position %d that is less than the initial '
            . 'value (%d) of the first element of the stream';
        $message = \sprintf($message, $expected, $first);

        return new static($message, self::CODE_POSITION_UNDERFLOW);
    }
}
