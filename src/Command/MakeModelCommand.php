<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class MakeModelCommand extends Command
{
    protected string $signature = 'make:model {name} {--m|migration}';
    protected string $description = 'Create a new ORM Model class (optional --migration flag)';
    protected string $category = 'Generators (make)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        if (empty($name)) {
            $this->error('Model name is required.');
            return 1;
        }

        $className = ucfirst($name);
        $tableName = strtolower($className) . 's';

        $basePath = getcwd() ?: '.';
        $dir = $basePath . '/app/Models';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/' . $className . '.php';
        if (file_exists($filePath)) {
            $this->warning("Model {$className} already exists at app/Models/{$className}.php");
            return 1;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

use Switch\Database\ORM\Model;

class {$className} extends Model
{
    protected string \$table = '{$tableName}';
    protected string \$primaryKey = 'id';

    /**
     * Mass assignable attributes.
     */
    protected array \$fillable = [
        // 'name', 'email', ...
    ];
}
PHP;

        file_put_contents($filePath, $content);
        $this->success("Model [app/Models/{$className}.php] created successfully.");

        if ($this->hasOption('migration')) {
            $migCmd = new MakeMigrationCommand();
            $migCmd->setOutput($this->output);
            $migCmd->bindInput(["create_{$tableName}_table"]);
            $migCmd->handle();
        }

        return 0;
    }
}
