<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class TinkerCommand extends Command
{
    protected string $signature = 'tinker {--execute=}';
    protected string $description = 'Interact with your Switch application in an interactive REPL shell';
    protected string $category = 'Development';

    public function handle(): int
    {
        $this->title('Switch Interactive REPL (Tinker)');
        $this->info("Type 'exit', 'quit', or Ctrl+C to exit the REPL shell.");
        $this->line();

        // 1. If PsySH is installed, use PsySH Shell
        if (class_exists(\Psy\Shell::class)) {
            $shell = new \Psy\Shell();
            $shell->run();
            return 0;
        }

        // 2. One-off execution mode `--execute="code"`
        $executeCode = $this->option('execute');
        if (!empty($executeCode)) {
            $this->evaluateExpression((string) $executeCode, []);
            return 0;
        }

        // 3. Fallback Built-in Interactive REPL Shell with State Persistence
        $variables = [];
        $stdin = fopen('php://stdin', 'r');

        while (true) {
            if ($this->output->isDecorated()) {
                echo "\e[1;36mswitch>\e[0m ";
            } else {
                echo "switch> ";
            }

            $line = $stdin ? fgets($stdin) : false;
            if ($line === false) {
                echo PHP_EOL;
                break;
            }

            $code = trim($line);

            if (in_array(strtolower($code), ['exit', 'quit', 'q'], true)) {
                $this->info('Exiting Tinker.');
                break;
            }

            if ($code === '') {
                continue;
            }

            // Ensure statement ends with semicolon if missing
            if (!str_ends_with($code, ';') && !str_ends_with($code, '}')) {
                $code .= ';';
            }

            $variables = $this->evaluateExpression($code, $variables);
        }

        if ($stdin) {
            fclose($stdin);
        }

        return 0;
    }

    /**
     * Safely evaluate expression and dump formatted result or error.
     *
     * @param array<string, mixed> $__variables State variables carried across REPL prompts
     * @return array<string, mixed> Updated state variables
     */
    private function evaluateExpression(string $__code, array $__variables): array
    {
        extract($__variables, EXTR_SKIP);

        // Auto-prefix return for value dumping if expression doesn't assign or echo
        if (!preg_match('/^\s*(echo|print|return|\$|var_dump|class|interface|function|if|foreach|while|for)\b/i', $__code)) {
            $__evalCode = "return ({$__code});";
        } else {
            $__evalCode = $__code;
        }

        try {
            ob_start();
            $__result = eval($__evalCode);
            $__outputBuffer = ob_get_clean();

            if (!empty($__outputBuffer)) {
                echo $__outputBuffer . PHP_EOL;
            }

            if ($__result !== null) {
                $this->dumpResult($__result);
            }

            // Capture newly defined user variables
            $defined = get_defined_vars();
            unset($defined['__code'], $defined['__variables'], $defined['__evalCode'], $defined['__result'], $defined['__outputBuffer']);
            return $defined;
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $this->error($e->getClass() . ': ' . $e->getMessage());
            if ($this->output->isDecorated()) {
                echo "  \e[38;5;244mat " . $e->getFile() . ':' . $e->getLine() . "\e[0m" . PHP_EOL;
            } else {
                echo "  at " . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
            }

            return $__variables;
        }
    }

    /**
     * Pretty-print Tinker evaluation results.
     */
    private function dumpResult(mixed $result): void
    {
        if (is_bool($result)) {
            $str = $result ? 'true' : 'false';
            echo "=> \e[1;33m{$str}\e[0m" . PHP_EOL;
        } elseif (is_null($result)) {
            echo "=> \e[1;30mnull\e[0m" . PHP_EOL;
        } elseif (is_string($result)) {
            echo "=> \e[32m\"{$result}\"\e[0m" . PHP_EOL;
        } elseif (is_numeric($result)) {
            echo "=> \e[1;36m{$result}\e[0m" . PHP_EOL;
        } elseif (is_array($result)) {
            echo "=> \e[1;37m" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\e[0m" . PHP_EOL;
        } elseif (is_object($result)) {
            $class = get_class($result);
            echo "=> \e[1;35m{$class}\e[0m " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } else {
            var_dump($result);
        }
    }
}
