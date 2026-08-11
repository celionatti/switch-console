<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeMigrationCommand extends Command
{
    protected string $signature = 'make:migration {name}';
    protected string $description = 'Create a new database migration file';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if (empty($name)) {
            $this->error('Migration name is required.');
            return 1;
        }

        $snakeName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$snakeName}.php";

        $tableName = 'my_table';
        if (preg_match('/create_(.*)_table/', $snakeName, $matches)) {
            $tableName = $matches[1];
        }

        $basePath = getcwd() ?: '.';
        $dir = $basePath . '/database/migrations';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $fileName;

        $content = <<<PHP
<?php

declare(strict_types=1);

use Switch\Database\Migration\Migration;
use Switch\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        \$this->schema->create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema->drop('{$tableName}');
    }
};
PHP;

        file_put_contents($filePath, $content);
        $this->success("Migration [database/migrations/{$fileName}] created successfully.");
        return 0;
    }
}
