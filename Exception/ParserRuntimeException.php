<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Exception\SourceException;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * Class ParserRuntimeException
 */
class ParserRuntimeException extends SourceException implements RuntimeExceptionInterface
{

}
