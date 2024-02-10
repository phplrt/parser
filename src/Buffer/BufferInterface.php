<?php

declare(strict_types=1);

namespace Phplrt\Parser\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\BufferExceptionInterface;

/**
 * @template-extends \SeekableIterator<int<0, max>, TokenInterface>
 */
interface BufferInterface extends \SeekableIterator
{
    /**
     * Rewind the BufferInterface to the target token element.
     *
     * @link https://php.net/manual/en/seekableiterator.seek.php
     * @see \SeekableIterator::seek()
     *
     * @param int<0, max> $offset
     * @return void
     * @throws BufferExceptionInterface
     */
    public function seek(int $offset): void;

    /**
     * Return the current token.
     *
     * @link https://php.net/manual/en/iterator.current.php
     * @see \Iterator::current()
     *
     * @return TokenInterface
     */
    public function current(): TokenInterface;

    /**
     * Return the ordinal id of the current token element.
     *
     * @link https://php.net/manual/en/iterator.key.php
     * @see \Iterator::key()
     *
     * @return int<0, max>
     */
    public function key(): int;

    /**
     * Checks if current position is valid and iterator not completed.
     *
     * @link https://php.net/manual/en/iterator.valid.php
     * @see \Iterator::valid()
     *
     * @return bool
     */
    public function valid(): bool;

    /**
     * Rewind the BufferInterface to the first token element.
     *
     * @link https://php.net/manual/en/iterator.rewind.php
     * @see \Iterator::rewind()
     *
     * @return void
     * @throws BufferExceptionInterface
     */
    public function rewind(): void;

    /**
     * Move forward to next token element.
     *
     * @link https://php.net/manual/en/iterator.next.php
     * @see \Iterator::next()
     *
     * @return void
     */
    public function next(): void;
}