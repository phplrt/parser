<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

/**
 * Class Terminal
 */
abstract class Terminal extends Rule implements TerminalInterface
{
    /**
     * @var int
     */
    public $token;

    /**
     * Lexeme constructor.
     *
     * @param int $token
     */
    public function __construct(int $token)
    {
        $this->token = $token;
    }
}
