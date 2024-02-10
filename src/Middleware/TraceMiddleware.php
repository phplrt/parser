<?php

declare(strict_types=1);

namespace Phplrt\Parser\Middleware;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Printer\PrettyPrinter;
use Phplrt\Lexer\Printer\PrinterInterface;
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Grammar\TerminalInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class TraceMiddleware implements MiddlewareInterface
{
    /**
     * @var int<0, max>
     */
    private int $depth = 0;

    /**
     * @var int<0, max>
     */
    private int $previousDepth = 0;

    /**
     * @var int<0, max>
     */
    private int $op = 0;

    private readonly PrinterInterface $printer;

    public function __construct(
        private readonly OutputInterface $output = new ConsoleOutput(),
        private readonly string $nesting = ' ',
        PrinterInterface $printer = null,
    ) {
        $this->printer = $printer ?? PrettyPrinter::getInstance();
    }

    private function format(Context $context, string $message, array $args): string
    {
        $state = $this->previousDepth <= $this->depth
            ? \sprintf(' <bg=yellow;fg=black> state: %-4s → </> ', $context->state)
            : \sprintf(' <bg=gray;fg=yellow> state: %-4s ← </> ', $context->state)
        ;

        return $state
            . \sprintf('<fg=gray> %4s ┊ </>', $this->op)
            . \str_repeat($this->nesting, $this->depth)
            . ($args !== [] ? \vsprintf($message, $args) : $message)
        ;
    }

    private function writeln(Context $context, string $message, mixed ...$args): void
    {
        $this->output->writeln($this->format($context, $message, $args));
    }

    private function before(Context $context): void
    {
        $this->previousDepth = $this->depth;

        ++$this->op;

        if (!$context->rule instanceof TerminalInterface) {
            $this->writeln($context, $this->formatRule($context, $context->rule));
        }

        ++$this->depth;
    }

    private function after(Context $context, mixed $result): void
    {
        $this->previousDepth = $this->depth;
        --$this->depth;

        $prefix = $this->formatRule($context, $context->rule);
        $suffix = $this->formatResult($result);

        $this->writeln($context, $prefix . $suffix);
    }

    private function formatRule(Context $context, RuleInterface $rule): string
    {
        return match (true) {
            $rule instanceof Lexeme => $this->formatLexeme($context, $rule),
            $rule instanceof Concatenation => $this->formatConcatenation($rule),
            $rule instanceof Alternation => $this->formatAlternation($rule),
            $rule instanceof Optional => $this->formatOptional($rule),
            $rule instanceof Repetition => $this->formatRepetition($rule),
            default => $rule::class,
        };
    }

    private function formatLexeme(Context $context, Lexeme $lexeme): string
    {
        return \sprintf('<fg=gray>token <fg=white>%s</> is <fg=yellow>%s</></>', $context->token->getName(), $lexeme->token);
    }

    private function formatOptional(Optional $optional): string
    {
        return \sprintf('<fg=gray>optional <fg=yellow>%s</>?</>', $optional->rule);
    }

    private function formatConcatenation(Concatenation $concatenation): string
    {
        $result = [];

        foreach ($concatenation->sequence as $reference) {
            $result[] = '<fg=yellow>' . $reference . '</>';
        }

        if (\count($result) === 1) {
            return \sprintf('<fg=gray>jump to <fg=yellow>%s</></>', $result[0]);
        }

        return \sprintf('<fg=gray>sequence of { %s }</>', \implode('<fg=gray> → </>', $result));
    }

    private function formatRepetition(Repetition $repeat): string
    {
        if ($repeat->from === 0 && \is_infinite($repeat->to)) {
            return \sprintf('<fg=gray>repeat* { <fg=yellow>%s</> }</>', $repeat->rule);
        }

        if ($repeat->from === 1 && \is_infinite($repeat->to)) {
            return \sprintf('<fg=gray>repeat+ { <fg=yellow>%s</> }</>', $repeat->rule);
        }

        return \vsprintf('<fg=gray>repeat(%d, %s) { <fg=yellow>%s</> }</>', [
            $repeat->from,
            \is_infinite($repeat->to) ? '∞' : $repeat->to,
            $repeat->rule,
        ]);
    }

    private function formatAlternation(Alternation $alternation): string
    {
        $result = [];

        foreach ($alternation->sequence as $reference) {
            $result[] = '<fg=yellow>' . $reference . '</>';
        }

        return \sprintf('<fg=gray>one of ( %s )</>', \implode('<fg=gray> | </>', $result));
    }

    private function formatResult(mixed $result): string
    {
        if ($result === null) {
            return \sprintf('<fg=gray> = <fg=red>false</></>');
        }

        return \sprintf('<fg=gray> = </><info>%s</info>', match (true) {
            $result instanceof TokenInterface => $this->printer->printToken($result),
            \is_object($result) => '<fg=white>object(</>' . \get_debug_type($result) . '<fg=white>)</>',
            default => \get_debug_type($result),
        });
    }

    public function process(Context $context, HandlerInterface $handler): mixed
    {
        $this->before($context);

        $result = $handler->handle($context);

        $this->after($context, $result);

        return $result;
    }
}
