<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

class LazyBuffer extends Buffer
{
    /**
     * @var array|TokenInterface[]
     */
    protected array $buffer = [];

    /**
     * @var \Generator
     */
    protected \Generator $stream;

    /**
     * LazyBuffer constructor.
     *
     * @param iterable $stream
     */
    public function __construct(iterable $stream)
    {
        $this->stream = $this->toGenerator($stream);

        if ($this->stream->valid()) {
            $this->initial = $this->current = $this->stream->key();
            $this->buffer[$this->current] = $this->stream->current();

            $this->stream->next();
        }
    }

    /**
     * @param iterable $stream
     * @return \Generator
     */
    private function toGenerator(iterable $stream): \Generator
    {
        yield from $stream;
    }

    /**
     * @return int
     */
    public function getBufferCurrentSize(): int
    {
        return \count($this->buffer);
    }

    /**
     * {@inheritDoc}
     */
    public function seek($position): void
    {
        if ($position < $this->initial) {
            $message = \sprintf(static::ERROR_STREAM_POSITION_TO_LOW, $position, $this->current());

            throw new \OutOfRangeException($message);
        }

        //
        // In the case that the position value exceeds the part loaded
        // into the buffer, then it must be loaded into the memory of the
        // buffer.
        //
        while ($position > ($last = \array_key_last($this->buffer))) {
            if (! $this->valid()) {
                $message = \sprintf(static::ERROR_STREAM_POSITION_EXCEED, $position, $last);

                throw new \OutOfRangeException($message);
            }

            $this->next();
        }

        $this->current = $position;
    }

    /**
     * {@inheritDoc}
     */
    public function current(): TokenInterface
    {
        return $this->currentFrom($this->buffer);
    }

    /**
     * {@inheritDoc}
     */
    public function valid(): bool
    {
        return isset($this->buffer[$this->current]);
    }

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        $this->nextValid();
    }

    /**
     * @return bool
     */
    protected function nextValid(): bool
    {
        $this->current++;

        if (! isset($this->buffer[$this->current])) {
            $current = $this->stream->current();

            if ($current) {
                $this->buffer[$this->current] = $current;
                $this->stream->next();

                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        if (! $this->valid()) {
            return \array_key_last($this->buffer);
        }

        return parent::key();
    }
}
