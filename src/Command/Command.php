<?php

declare(strict_types=1);

namespace Switch\Console\Command;

use Switch\Console\Output\ConsoleOutput;
use Switch\Console\Signature\SignatureParser;

abstract class Command
{
    protected string $signature = '';
    protected string $description = '';
    protected string $category = 'App';

    protected ConsoleOutput $output;

    /**
     * @var array<string, mixed>
     */
    protected array $arguments = [];

    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    public function __construct()
    {
        $this->output = new ConsoleOutput();
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getName(): string
    {
        $parsed = SignatureParser::parse($this->signature);
        return $parsed['name'];
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setOutput(ConsoleOutput $output): void
    {
        $this->output = $output;
    }

    /**
     * Bind raw command line inputs (ARGV) to parsed signature parameters.
     *
     * @param array<int, string> $rawArgs
     */
    public function bindInput(array $rawArgs): void
    {
        $parsed = SignatureParser::parse($this->signature);

        $positional = [];
        $optionsGiven = [];

        foreach ($rawArgs as $arg) {
            if (str_starts_with($arg, '--')) {
                $opt = substr($arg, 2);
                if (str_contains($opt, '=')) {
                    [$name, $val] = explode('=', $opt, 2);
                    $optionsGiven[$name] = $val;
                } else {
                    $optionsGiven[$opt] = true;
                }
            } elseif (str_starts_with($arg, '-') && strlen($arg) === 2) {
                $shortcut = substr($arg, 1);
                foreach ($parsed['options'] as $name => $optDef) {
                    if ($optDef['shortcut'] === $shortcut) {
                        $optionsGiven[$name] = true;
                    }
                }
            } else {
                $positional[] = $arg;
            }
        }

        // Bind arguments
        foreach ($parsed['arguments'] as $i => $argDef) {
            $val = $positional[$i] ?? $argDef['default'];
            $this->arguments[$argDef['name']] = $val;
        }

        // Bind options
        foreach ($parsed['options'] as $name => $optDef) {
            $this->options[$name] = $optionsGiven[$name] ?? $optDef['default'];
        }
    }

    public function argument(string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $default;
    }

    public function option(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return !empty($this->options[$name]);
    }

    // Output Helper Methods
    protected function line(string $message = ''): void
    {
        $this->output->line($message);
    }

    protected function info(string $message): void
    {
        $this->output->info($message);
    }

    protected function success(string $message): void
    {
        $this->output->success($message);
    }

    protected function warning(string $message): void
    {
        $this->output->warning($message);
    }

    protected function error(string $message): void
    {
        $this->output->error($message);
    }

    protected function title(string $title): void
    {
        $this->output->title($title);
    }

    protected function details(array $details): void
    {
        $this->output->details($details);
    }

    protected function table(array $headers, array $rows): void
    {
        $this->output->table($headers, $rows);
    }

    protected function progressBar(int $current, int $total, string $label = 'Processing'): void
    {
        $this->output->progressBar($current, $total, $label);
    }

    /**
     * Ask an interactive prompt question.
     */
    protected function ask(string $question, ?string $default = null): string
    {
        $defaultText = $default !== null ? " [{$default}]" : '';
        echo "  \e[36m? {$question}{$defaultText}:\e[0m ";
        $handle = fopen('php://stdin', 'r');
        $line = $handle ? fgets($handle) : '';
        $trimmed = trim((string) $line);

        return $trimmed !== '' ? $trimmed : ($default ?? '');
    }

    /**
     * Ask an interactive Y/N confirmation prompt.
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? ' [Y/n]' : ' [y/N]';
        echo "  \e[33m? {$question}{$defaultText}:\e[0m ";
        $handle = fopen('php://stdin', 'r');
        $line = strtolower(trim((string) ($handle ? fgets($handle) : '')));

        if ($line === '') {
            return $default;
        }

        return in_array($line, ['y', 'yes', '1', 'true'], true);
    }

    /**
     * Execute the command logic.
     * Must return status code (0 for success, non-zero for error).
     */
    abstract public function handle(): int;
}
