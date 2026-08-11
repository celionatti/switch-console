# Switch Console (`switch/console`)

> The futuristic, colorful command-line interface for the Switch PHP Framework. Inspired by Artisan, built for modern speed, visual feedback, and simplicity.

---

## 📦 Installation

```bash
composer require switch/console
```

---

## 🚀 Quick Usage

Run the Switch CLI from your project root:

```bash
# Show available commands with colorful banner
php switch

# Run local development server
php switch serve

# View registered HTTP routes in a formatted table
php switch route:list

# Create a controller
php switch make:controller UserController --resource

# Create a model with migration
php switch make:model Product -m

# Create a database migration
php switch make:migration create_orders_table

# Run database migrations
php switch migrate

# Clear compiled views and caches
php switch clear:cache
```

---

## 🛠️ Built-in Commands

| Command | Description |
|---------|-------------|
| **`serve`** | Starts local PHP dev server (`http://127.0.0.1:8000`) |
| **`route:list`** | Formatted ANSI table of registered routes (Method, URI, Name, Middleware) |
| **`make:controller {name} {--resource}`** | Generates a controller class in `app/Controllers/` |
| **`make:model {name} {--migration}`** | Generates an ORM model in `app/Models/` |
| **`make:migration {name}`** | Generates timestamped migration file in `database/migrations/` |
| **`make:middleware {name}`** | Generates PSR-15 middleware in `app/Middleware/` |
| **`make:event {name}`** | Generates an event class in `app/Events/` |
| **`make:command {name}`** | Generates custom Switch CLI command in `app/Commands/` |
| **`migrate {--rollback} {--refresh}`** | Runs, rolls back, or refreshes database migrations |
| **`clear:cache`** | Clears compiled template views and storage caches |

---

## ⚡ Creating Custom Commands

Create a command class using the generator:
```bash
php switch make:command ImportData
```

Or write your own in `app/Commands/ImportDataCommand.php`:

```php
namespace App\Commands;

use Switch\Console\Command\Command;

class ImportDataCommand extends Command
{
    protected string $signature = 'db:import {file} {--force}';
    protected string $description = 'Import data from JSON or CSV';
    protected string $category = 'App';

    public function handle(): int
    {
        $file = $this->argument('file');
        
        $this->title("Importing {$file}");
        $this->info("Processing rows...");

        $this->progressBar(50, 100, "Progress");

        $this->success("Import completed successfully!");
        return 0;
    }
}
```

The CLI will automatically discover and register your command in `app/Commands/`!

---

## 📄 License
MIT License.
