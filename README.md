<p align="center">
    <a href="https://railt.org"><img src="https://avatars2.githubusercontent.com/u/49816277?s=128" width="128" alt="Phplrt" /></a>
</p>

## Parser

A recurrence recursive descent LL(k) parser implementation.

## Usage

Let's try to recognize an elementary mathematical expression `2 + 2`. 

### Lexer

The first step will be [a lexer implementation](https://github.com/phplrt/lexer), 
like this:

```php
$lexer = Phplrt\Lexer\Lexer::create([
    1 => '\s+', // whitespaces
    2 => '\d+', // digits
    3 => '\+',  // symbol "+"
], [1]);
```

Please note that any implementation of the 
[`phplrt/lexer-contracts`](https://github.com/phplrt/lexer-contracts) is 
possible, and the implementation from 
[`phplrt/lexer`](https://github.com/phplrt/lexer) used only as an example.

### Grammar

Next, we need to determine the grammar. In this case, it will look like (in BNF):

```bnf
expr ::= digit "+" digit ;
digit ::= "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;
```

However, in [section about lexer](README.md#lexer) we have already defined a 
lexer, where any number (`\d+`) is under the index `2`, and the addition 
symbol (`\+`) is under the index `3`.

It turns out that the grammar is slightly transformed (pseudo BNF code):

```bnf
expr ::= digit #3 digit ;
digit ::= #2;
```

Now that we have decided on the grammar, we can translate it into a 
format that the parser understands:

```php
$rules = [ 
    // Expr is a sequence of [rule #1 then #2 then #1] 
    0 => new Phplrt\Parser\Rule\Concatenation([1, 2, 1]),

    // Rule #1 is a digit (#2) token
    1 => new Phplrt\Parser\Rule\Lexeme(2),

    // Rule #2 is a plus (#3) token
    2 => new Phplrt\Parser\Rule\Lexeme(3),
];
```

### Initialization

Now we can use the parser:

```php
$parser = new Phplrt\Parser\Parser($lexer, $rules);

$parser->parse('2 + 2');    // OK
$parser->parse('2 +');      // Error: Syntax error, unexpected end of input
$parser->parse('2 + 2 2');  // Error: Syntax error, unexpected "23"
$parser->parse('2 * 2');    // Error: Syntax error, unrecognized lexeme "*"
```

Everything works successfully!

### AST Generation

After we have decided on the grammar, we can proceed to the construction 
of abstract syntax tree.

In this case, we can use any implementation of 
[`phplrt/ast-contracts`](https://github.com/phplrt/ast-contracts). But in 
example we can use the existing [`phplrt/ast`](https://github.com/phplrt/ast) 
package which already implements this set of interfaces:

```php
use Phplrt\Ast\TreeNode;
use Phplrt\Contracts\Lexer\TokenInterface;

class Addition extends TreeNode
{
    /**First digit */
    public $a;

    /** Second digit */
    public $b;

    public function __construct(TokenInterface $a, TokenInterface $b) 
    {
        parent::__construct(0);

        $this->a = (int)$a->getValue();
        $this->b = (int)$b->getValue();
    }
}
```

In this case, we created a simple AST node for addition that contains two 
numbers, `$addition->a` and `$addition->b`.

In order to apply it, we should modify the rule for concatenation:

```php
$rules = [
    0 => new Phplrt\Parser\Rule\Concatenation([1, 2, 1], function (array $children) {
        //
        // The $children variable contains three tokens as defined in the rule: 
        //  1) the first digit (#1)
        //  2) the plus sign (#2)
        //  3) the second number (#1)
        // In order to correctly create a node, we should give 
        // it only two digits.
        //
        return new Addition($children[0], $children[2]);
    }),
    // ...
```

And now we can check try to check the result:

```php
$ast = $parser->parse('2 + 2');

var_dump(
    \get_class($ast),
    $ast->a,
    $ast->b
);
// expceted output:
// > string(8) "Addition"
// > int(2)
// > int(2)
```
