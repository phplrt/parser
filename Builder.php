<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Ast\Leaf;
use Phplrt\Ast\Node;
use Phplrt\Ast\RuleInterface;
use Phplrt\Ast\Rule;
use Phplrt\Parser\Trace\Entry;
use Phplrt\Parser\Trace\Escape;
use Phplrt\Parser\Exception\GrammarException;

/**
 * Class Builder
 */
class Builder implements BuilderInterface
{
    /**
     * @var array
     */
    private $trace;

    /**
     * @var GrammarInterface
     */
    private $grammar;

    /**
     * Builder constructor.
     *
     * @param array $trace
     * @param GrammarInterface $grammar
     */
    public function __construct(array $trace, GrammarInterface $grammar)
    {
        $this->trace = $trace;
        $this->grammar = $grammar;
    }

    /**
     * @return RuleInterface|mixed
     * @throws \LogicException
     * @throws GrammarException
     */
    public function build()
    {
        return $this->buildTree();
    }

    /**
     * Build AST from trace.
     * Walk through the trace iteratively and recursively.
     *
     * @param int|mixed $i Current trace index.
     * @param array &$children Collected children.
     * @return Node|int|mixed
     * @throws \LogicException
     * @throws GrammarException
     */
    protected function buildTree(int $i = 0, array &$children = [])
    {
        $max = \count($this->trace);

        while ($i < $max) {
            $trace = $this->trace[$i];

            if ($trace instanceof Entry) {
                $ruleName = $trace->getRule();
                $rule = $this->grammar->fetch($ruleName);
                $isRule = $trace->isTransitional() === false;
                $nextTrace = $this->trace[$i + 1];
                $id = $rule->getNodeId();

                // Optimization: Skip empty trace sequence.
                if ($nextTrace instanceof Escape && $ruleName === $nextTrace->getRule()) {
                    $i += 2;

                    continue;
                }

                if ($isRule === true) {
                    $children[] = $ruleName;
                }

                if ($id !== null) {
                    $children[] = [$id];
                }

                $i = $this->buildTree($i + 1, $children);

                if ($isRule === false) {
                    continue;
                }

                $handle = [];
                $childId = null;

                do {
                    $pop = \array_pop($children);

                    if (\is_object($pop) === true) {
                        $handle[] = $pop;
                    } elseif (\is_array($pop) && $childId === null) {
                        $childId = \reset($pop);
                    } elseif ($ruleName === $pop) {
                        break;
                    }
                } while ($pop !== null);

                if ($childId === null) {
                    $childId = $rule->getDefaultId();
                }

                if ($childId === null) {
                    for ($j = \count($handle) - 1; $j >= 0; --$j) {
                        $children[] = $handle[$j];
                    }

                    continue;
                }

                $children[] = $this->getRule(
                    (string)($id ?: $childId),
                    \array_reverse($handle),
                    $trace->getOffset()
                );
            } elseif ($trace instanceof Escape) {
                return $i + 1;
            } else {
                if (! $trace->isKept()) {
                    ++$i;
                    continue;
                }

                $children[] = new Leaf($trace->getToken());
                ++$i;
            }
        }

        return $children[0];
    }

    /**
     * @param string $name
     * @param array $children
     * @param int $offset
     * @return Rule|mixed
     * @throws \LogicException
     * @throws GrammarException
     */
    protected function getRule(string $name, array $children, int $offset)
    {
        $class = $this->getRuleClass($name);

        try {
            return new $class($name, $children, $offset);
        } catch (\Throwable $e) {
            throw $this->throwInitializationError($e, $class);
        }
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getRuleClass(string $name): string
    {
        return $this->grammar->delegate($name) ?? Rule::class;
    }

    /**
     * @param \Throwable $e
     * @param string $class
     * @return GrammarException
     */
    protected function throwInitializationError(\Throwable $e, string $class): GrammarException
    {
        $error = \sprintf('Error while %s initialization: %s', $class, $e->getMessage());

        return new GrammarException($error);
    }
}
