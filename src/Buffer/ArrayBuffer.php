<?php

declare(strict_types=1);

namespace Phplrt\Parser\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\BufferPositionOverflowException;
use Phplrt\Parser\Exception\BufferPositionUnderflowException;

final class ArrayBuffer implements BufferInterface
{
    /**
     * @var list<TokenInterface>
     */
    private readonly array $buffer;

    /**
     * @var int<0, max>
     */
    private readonly int $size;

    /**
     * @var int<0, max>
     */
    private readonly int $first;

    /**
     * @var int<0, max>
     */
    private int $current;

    /**
     * @param iterable<int<0, max>, TokenInterface> $stream
     */
    public function __construct(iterable $stream)
    {
        $this->buffer = $this->iterableToArray($stream);
        $this->size = \count($this->buffer);

        \assert($this->size > 0, 'Buffer size must be greater than 0');

        $this->first = $this->current = \array_key_first($this->buffer);
    }

    /**
     * @param iterable<int<0, max>, TokenInterface> $tokens
     * @return list<TokenInterface>
     */
    private function iterableToArray(iterable $tokens): array
    {
        if ($tokens instanceof \Traversable) {
            return \iterator_to_array($tokens, false);
        }

        return $tokens;
    }

    public function rewind(): void
    {
        $this->seek($this->first);
    }

    public function key(): int
    {
        return $this->current;
    }

    public function seek(int $offset): void
    {
        if ($offset < $this->first) {
            throw BufferPositionUnderflowException::fromOffsetUnderflow($offset, $this->first);
        }

        if ($offset >= $this->size) {
            throw BufferPositionOverflowException::fromOffsetOverflow($offset, $this->size - 1);
        }

        $this->current = $offset;
    }

    public function current(): TokenInterface
    {
        if (isset($this->buffer[$this->current])) {
            return $this->buffer[$this->current];
        }

        return $this->buffer[$this->size - 1];
    }

    public function next(): void
    {
        if ($this->current < $this->size) {
            ++$this->current;
        }
    }

    public function valid(): bool
    {
        return $this->current < $this->size;
    }
}
