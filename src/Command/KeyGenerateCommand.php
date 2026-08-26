<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class KeyGenerateCommand extends Command
{
    protected string $signature = 'key:generate {--show : Display the key instead of modifying files}';
    protected string $description = 'Set the application encryption key';
    protected string $category = 'Security';

    public function handle(): int
    {
        $key = 'base64:' . base64_encode(random_bytes(32));

        if ($this->hasOption('show')) {
            $this->line("  \e[32;1m{$key}\e[0m");
            return 0;
        }

        $basePath = defined('SWITCH_BASE_PATH') ? constant('SWITCH_BASE_PATH') : getcwd();
        $envPath = $basePath . '/.env';

        if (!file_exists($envPath)) {
            if (file_exists($basePath . '/.env.example')) {
                copy($basePath . '/.env.example', $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $envContent = (string) file_get_contents($envPath);

        if (preg_match('/^APP_KEY=(.*)$/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=(.*)$/m', 'APP_KEY=' . $key, $envContent);
        } else {
            $envContent .= "\nAPP_KEY=" . $key . "\n";
        }

        file_put_contents($envPath, $envContent);

        $this->success("Application key [{$key}] set successfully in .env.");
        return 0;
    }
}
