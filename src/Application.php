<?php

declare(strict_types=1);

namespace Switch\Console;

use Switch\Console\Command\Command;
use Switch\Console\Command\MakeControllerCommand;
use Switch\Console\Command\MakeModelCommand;
use Switch\Console\Command\MakeMigrationCommand;
use Switch\Console\Command\MakeMiddlewareCommand;
use Switch\Console\Command\MakeEventCommand;
use Switch\Console\Command\MakeCommandCommand;
use Switch\Console\Command\ServeCommand;
use Switch\Console\Command\ClearCacheCommand;
use Switch\Console\Command\TinkerCommand;
use Switch\Console\Command\RouteListCommand;
use Switch\Console\Command\MigrateCommand;
use Switch\Console\Output\ConsoleOutput;
use Switch\Console\Signature\SignatureParser;

class Application
{
    private const VERSION = '1.0.0';

    /**
     * @var array<string, Command>
     */
    private array $commands = [];

    private ConsoleOutput $output;
    private ?string $appCommandsPath = null;

    public function __construct()
    {
        $this->output = new ConsoleOutput();
        $this->registerBuiltinCommands();
    }

    /**
     * Register all built-in commands.
     */
    private function registerBuiltinCommands(): void
    {
        $this->add(new MakeControllerCommand());
        $this->add(new MakeModelCommand());
        $this->add(new MakeMigrationCommand());
        $this->add(new MakeMiddlewareCommand());
        $this->add(new MakeEventCommand());
        $this->add(new MakeCommandCommand());
        $this->add(new ServeCommand());
        $this->add(new ClearCacheCommand());
        $this->add(new TinkerCommand());

        // Conditionally register route:list if router package is available
        if (class_exists(\Switch\Router\Router::class)) {
            $this->add(new RouteListCommand());
        }

        // Conditionally register migrate if database package is available
        if (class_exists(\Switch\Database\Connection\Connection::class)) {
            $this->add(new MigrateCommand());
        }
    }

    /**
     * Register a command instance.
     */
    public function add(Command $command): self
    {
        $command->setOutput($this->output);
        $name = $command->getName();
        $this->commands[$name] = $command;
        return $this;
    }

    /**
     * Set a path to auto-discover user app commands.
     */
    public function setAppCommandsPath(string $path): self
    {
        $this->appCommandsPath = $path;
        return $this;
    }

    /**
     * Auto-discover and register user commands from app/Commands/.
     */
    public function discoverCommands(): void
    {
        if ($this->appCommandsPath === null) {
            $basePath = getcwd() ?: '.';
            $this->appCommandsPath = $basePath . '/app/Commands';
        }

        if (!is_dir($this->appCommandsPath)) {
            return;
        }

        $files = glob($this->appCommandsPath . '/*.php');
        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);

            // Include the file to register the class
            if (!class_exists($className, false)) {
                require_once $file;
            }

            if (class_exists($className) && is_subclass_of($className, Command::class)) {
                $this->add(new $className());
            }
        }
    }

    /**
     * Run the CLI application.
     *
     * @param array<int, string> $argv
     * @return int Exit status code
     */
    public function run(array $argv = []): int
    {
        if (empty($argv)) {
            $argv = $_SERVER['argv'] ?? [];
        }

        // Remove the script name (e.g. "switch")
        array_shift($argv);

        // No command given: show help
        if (empty($argv) || $argv[0] === 'list' || $argv[0] === '--help' || $argv[0] === '-h') {
            $this->showCommandList();
            return 0;
        }

        $commandName = array_shift($argv);

        // Special shortcut: "help <command>"
        if ($commandName === 'help' && !empty($argv)) {
            $commandName = $argv[0];
            if (isset($this->commands[$commandName])) {
                $this->showCommandHelp($this->commands[$commandName]);
                return 0;
            }
        }

        if (!isset($this->commands[$commandName])) {
            $this->output->error("Command '{$commandName}' not found.");

            // Suggest similar commands
            $suggestions = $this->findSimilarCommands($commandName);
            if (!empty($suggestions)) {
                $this->output->line();
                $this->output->info("Did you mean one of these?");
                foreach ($suggestions as $suggestion) {
                    $this->output->line("    \e[33m{$suggestion}\e[0m");
                }
            }

            return 1;
        }

        $command = $this->commands[$commandName];

        // If --help flag is in args, show help for that command
        if (in_array('--help', $argv) || in_array('-h', $argv)) {
            $this->showCommandHelp($command);
            return 0;
        }

        $command->bindInput($argv);

        try {
            return $command->handle();
        } catch (\Throwable $e) {
            $this->output->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Show the full command list grouped by category.
     */
    private function showCommandList(): void
    {
        $this->output->renderHeader(self::VERSION);

        $this->output->title('Available Commands');

        // Group by category
        $grouped = [];
        foreach ($this->commands as $name => $cmd) {
            $cat = $cmd->getCategory();
            $grouped[$cat][$name] = $cmd->getDescription();
        }

        ksort($grouped);

        foreach ($grouped as $category => $commands) {
            if ($this->output->isDecorated()) {
                echo "  \e[1;33m{$category}\e[0m" . PHP_EOL;
            } else {
                echo "  [{$category}]" . PHP_EOL;
            }

            ksort($commands);
            $maxLen = max(array_map('mb_strlen', array_keys($commands)));

            foreach ($commands as $cmdName => $description) {
                $padded = str_pad($cmdName, $maxLen + 4, ' ');
                if ($this->output->isDecorated()) {
                    echo "    \e[32m{$padded}\e[0m \e[38;5;244m{$description}\e[0m" . PHP_EOL;
                } else {
                    echo "    {$padded} {$description}" . PHP_EOL;
                }
            }

            echo PHP_EOL;
        }

        if ($this->output->isDecorated()) {
            echo "  \e[38;5;244mRun \e[0m\e[36mphp switch help <command>\e[0m\e[38;5;244m for more info on a specific command.\e[0m" . PHP_EOL . PHP_EOL;
        } else {
            echo "  Run 'php switch help <command>' for more info on a specific command." . PHP_EOL . PHP_EOL;
        }
    }

    /**
     * Show help details for a single command.
     */
    private function showCommandHelp(Command $command): void
    {
        $parsed = SignatureParser::parse($command->getSignature());

        $this->output->title($parsed['name']);

        $this->output->details([
            'Description' => $command->getDescription(),
            'Usage' => 'php switch ' . $command->getSignature(),
        ]);

        if (!empty($parsed['arguments'])) {
            $this->output->line();
            $this->output->info('Arguments:');
            foreach ($parsed['arguments'] as $arg) {
                $req = $arg['required'] ? '(required)' : '(optional)';
                $this->output->line("    \e[32m{$arg['name']}\e[0m  {$req}");
            }
        }

        if (!empty($parsed['options'])) {
            $this->output->line();
            $this->output->info('Options:');
            foreach ($parsed['options'] as $opt) {
                $shortcut = $opt['shortcut'] ? "-{$opt['shortcut']}|" : '';
                $needsVal = $opt['requiresValue'] ? '=<value>' : '';
                $this->output->line("    \e[33m{$shortcut}--{$opt['name']}{$needsVal}\e[0m");
            }
        }

        $this->output->line();
    }

    /**
     * Find commands similar to the given input (fuzzy matching).
     *
     * @return array<int, string>
     */
    private function findSimilarCommands(string $input): array
    {
        $suggestions = [];

        foreach (array_keys($this->commands) as $cmdName) {
            $distance = levenshtein($input, $cmdName);
            if ($distance <= 3 || str_contains($cmdName, $input)) {
                $suggestions[] = $cmdName;
            }
        }

        return array_slice($suggestions, 0, 5);
    }

    /**
     * Get all registered commands.
     *
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getOutput(): ConsoleOutput
    {
        return $this->output;
    }

    public static function getVersion(): string
    {
        return self::VERSION;
    }
}
