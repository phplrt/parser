<?php
use Phplrt\Source\File;
use Phplrt\Parser\Grammar;
use Phplrt\Ast\AttributesTrait;
use Phplrt\Parser\RecursiveDescent;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Compiler\Builder\Lexer\StateBuilderInterface;

require __DIR__ . '/vendor/autoload.php';

class Node implements NodeInterface
{
    use AttributesTrait;
    public function __construct()
    {
        unset($this->attributes);
    }

    public function getOffset(): int
    {
        return $this->getAttribute('offset', 0);
    }
    public function getType(): int
    {
        return 0;
    }
    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}

class Value extends Node
{
    public $of;
    public function __construct(TokenInterface $scalar)
    {
        $this->of = $scalar->getValue();
        parent::__construct();
    }
}

class Operator extends Node
{
    public $type;
    public function __construct(TokenInterface $token)
    {
        $this->type = $token->getValue();
        parent::__construct();
    }
}

class Statement extends Node
{
    public $expression;
    public $value;
    public function __construct(Expression $expr, Value $value = null)
    {
        $this->expression = $expr;
        $this->value = $value;
        parent::__construct();
    }
}

class Expression extends Node
{
    public $a;
    public $operator;
    public $b;

    public function __construct(Value $a, Operator $op, $b)
    {
        $this->a = $a;
        $this->operator = $op;
        $this->b = $b;
        parent::__construct();
    }
}


$builder = new Grammar(static function (Grammar $grammar) {
    $grammar->lexer(static function (StateBuilderInterface $global) {
        $global->skip('T_WHITESPACE', '\s+');
    });

    yield 'Statement' => $grammar->sequenceOf([
        yield 'Expression' => $grammar->sequenceOf([
            yield 'Value' => $grammar->sequenceOf([
                yield $grammar->token('\d+', 'T_DIGIT'),
            ], static function (array $children) {
                return new Value($children[0]);
            }),

            yield $grammar->oneOf([
                yield $grammar->token('\+'),
                yield $grammar->token('\-'),
                yield $grammar->token('\*'),
                yield $grammar->token('/'),
            ], static function (array $children) {
                return new Operator($children[0]);
            }),

            yield $grammar->oneOf(['Expression', 'Value']),
        ], static function (array $children) {
            return new Expression(...$children);
        }),
        yield $grammar->optional([
            yield $grammar->token('='),
            'Value',
        ])
    ], static function (array $children) {
        return new Statement($children[0], $children[2] ?? null);
    });

}, 'Statement');


$parser = new RecursiveDescent($builder->getRules(), $builder->getLexer());

$ms = \microtime(true);

$result = $parser->parse(File::fromPathName(__DIR__ . '/test.txt'));


echo 'Jumps: ' . $parser->cycles . "\n";
echo 'Rollbacks: ' . $parser->rollbacks . "\n";
echo 'Elapsed Time: ' . \number_format(\microtime(true) - $ms, 4) . "s\n\n";

dump($result);



