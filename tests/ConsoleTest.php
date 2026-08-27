<?php

declare(strict_types=1);

namespace Switch\Console\Tests;

use PHPUnit\Framework\TestCase;
use Switch\Console\Application;
use Switch\Console\Output\ConsoleOutput;
use Switch\Console\Signature\SignatureParser;
use Switch\Console\Command\Command;

class ConsoleTest extends TestCase
{
    public function testApplicationRegistersBuiltinCommands(): void
    {
        $app = new Application();
        $commands = $app->getCommands();

        $this->assertArrayHasKey('make:controller', $commands);
        $this->assertArrayHasKey('make:model', $commands);
        $this->assertArrayHasKey('make:migration', $commands);
        $this->assertArrayHasKey('make:middleware', $commands);
        $this->assertArrayHasKey('make:event', $commands);
        $this->assertArrayHasKey('make:command', $commands);
        $this->assertArrayHasKey('serve', $commands);
        $this->assertArrayHasKey('clear:cache', $commands);
        $this->assertArrayHasKey('tinker', $commands);
    }

    public function testTinkerCommandOneOffExecution(): void
    {
        $app = new Application();
        ob_start();
        $code = $app->run(['switch', 'tinker', '--execute=1 + 2']);
        $output = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString('3', $output);
    }

    public function testApplicationRunsHelpWithNoArgs(): void
    {
        $app = new Application();
        ob_start();
        $code = $app->run(['switch']);
        $output = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString('Available Commands', $output);
        $this->assertStringContainsString('make:controller', $output);
    }

    public function testApplicationShowsErrorForUnknownCommand(): void
    {
        $app = new Application();
        ob_start();
        $code = $app->run(['switch', 'nonexistent:cmd']);
        $output = ob_get_clean();

        $this->assertEquals(1, $code);
        $this->assertStringContainsString('not found', $output);
    }

    public function testApplicationSuggestsSimilarCommands(): void
    {
        $app = new Application();
        ob_start();
        $code = $app->run(['switch', 'make:controll']);
        $output = ob_get_clean();

        $this->assertEquals(1, $code);
        $this->assertStringContainsString('make:controller', $output);
    }

    public function testSignatureParserParsesArguments(): void
    {
        $parsed = SignatureParser::parse('make:controller {name} {path?}');

        $this->assertEquals('make:controller', $parsed['name']);
        $this->assertCount(2, $parsed['arguments']);
        $this->assertEquals('name', $parsed['arguments'][0]['name']);
        $this->assertTrue($parsed['arguments'][0]['required']);
        $this->assertEquals('path', $parsed['arguments'][1]['name']);
        $this->assertFalse($parsed['arguments'][1]['required']);
    }

    public function testSignatureParserParsesOptions(): void
    {
        $parsed = SignatureParser::parse('make:model {name} {--m|migration} {--path=}');

        $this->assertEquals('make:model', $parsed['name']);
        $this->assertCount(1, $parsed['arguments']);
        $this->assertCount(2, $parsed['options']);

        $migration = $parsed['options']['migration'];
        $this->assertEquals('m', $migration['shortcut']);
        $this->assertFalse($migration['requiresValue']);

        $path = $parsed['options']['path'];
        $this->assertNull($path['shortcut']);
        $this->assertTrue($path['requiresValue']);
    }

    public function testMakeControllerCreatesFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();
            ob_start();
            $code = $app->run(['switch', 'make:controller', 'User']);
            $output = ob_get_clean();

            $this->assertEquals(0, $code);
            $this->assertStringContainsString('UserController', $output);
            $this->assertFileExists($tmpDir . '/app/Controllers/UserController.php');

            $content = file_get_contents($tmpDir . '/app/Controllers/UserController.php');
            $this->assertStringContainsString('class UserController', $content);
        } finally {
            chdir($oldCwd ?: '.');
            // Cleanup
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testMakeControllerWithResourceOption(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();
            ob_start();
            $code = $app->run(['switch', 'make:controller', 'Post', '--resource']);
            $output = ob_get_clean();

            $this->assertEquals(0, $code);
            $content = file_get_contents($tmpDir . '/app/Controllers/PostController.php');
            $this->assertStringContainsString('public function index()', $content);
            $this->assertStringContainsString('public function store()', $content);
            $this->assertStringContainsString('public function show($id)', $content);
            $this->assertStringContainsString('public function destroy($id)', $content);
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testMakeModelCreatesFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();
            ob_start();
            $code = $app->run(['switch', 'make:model', 'Product']);
            $output = ob_get_clean();

            $this->assertEquals(0, $code);
            $this->assertFileExists($tmpDir . '/app/Models/Product.php');

            $content = file_get_contents($tmpDir . '/app/Models/Product.php');
            $this->assertStringContainsString('class Product extends Model', $content);
            $this->assertStringContainsString("protected string \$table = 'products'", $content);
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testMakeMigrationCreatesFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();
            ob_start();
            $code = $app->run(['switch', 'make:migration', 'create_users_table']);
            $output = ob_get_clean();

            $this->assertEquals(0, $code);
            $this->assertStringContainsString('created successfully', $output);

            $migFiles = glob($tmpDir . '/database/migrations/*.php');
            $this->assertNotEmpty($migFiles);

            $content = file_get_contents($migFiles[0]);
            $this->assertStringContainsString("'users'", $content);
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testMakeMiddlewareCreatesFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();
            ob_start();
            $code = $app->run(['switch', 'make:middleware', 'Auth']);
            $output = ob_get_clean();

            $this->assertEquals(0, $code);
            $this->assertFileExists($tmpDir . '/app/Middleware/AuthMiddleware.php');

            $content = file_get_contents($tmpDir . '/app/Middleware/AuthMiddleware.php');
            $this->assertStringContainsString('class AuthMiddleware', $content);
            $this->assertStringContainsString('MiddlewareInterface', $content);
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testMakeEventCreatesFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();
            ob_start();
            $code = $app->run(['switch', 'make:event', 'UserRegistered']);
            $output = ob_get_clean();

            $this->assertEquals(0, $code);
            $this->assertFileExists($tmpDir . '/app/Events/UserRegisteredEvent.php');
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testMakeCommandCreatesFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();
            ob_start();
            $code = $app->run(['switch', 'make:command', 'ImportData']);
            $output = ob_get_clean();

            $this->assertEquals(0, $code);
            $this->assertFileExists($tmpDir . '/app/Commands/ImportDataCommand.php');

            $content = file_get_contents($tmpDir . '/app/Commands/ImportDataCommand.php');
            $this->assertStringContainsString('class ImportDataCommand extends Command', $content);
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testClearCacheCommand(): void
    {
        $app = new Application();
        ob_start();
        $code = $app->run(['switch', 'clear:cache']);
        $output = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString('cache cleared', $output);
    }

    public function testCommandBindInputWithOptions(): void
    {
        $cmd = new class extends Command {
            protected string $signature = 'test:cmd {name} {--verbose} {--format=json}';
            protected string $description = 'Test command';

            public function handle(): int
            {
                return 0;
            }

            public function exposeArgs(): array
            {
                return [$this->arguments, $this->options];
            }
        };

        $cmd->bindInput(['Alice', '--verbose', '--format=xml']);

        [$args, $opts] = $cmd->exposeArgs();
        $this->assertEquals('Alice', $args['name']);
        $this->assertTrue($opts['verbose']);
        $this->assertEquals('xml', $opts['format']);
    }

    public function testConsoleOutputRendersMethods(): void
    {
        $output = new ConsoleOutput(false); // No ANSI

        ob_start();
        $output->info('Test info');
        $output->success('Test success');
        $output->warning('Test warning');
        $output->error('Test error');
        $result = ob_get_clean();

        $this->assertStringContainsString('Test info', $result);
        $this->assertStringContainsString('Test success', $result);
        $this->assertStringContainsString('Test warning', $result);
        $this->assertStringContainsString('Test error', $result);
    }

    public function testConsoleOutputTable(): void
    {
        $output = new ConsoleOutput(false);

        ob_start();
        $output->table(['Name', 'Role'], [
            ['Alice', 'Admin'],
            ['Bob', 'User'],
        ]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Name', $result);
        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('Admin', $result);
        $this->assertStringContainsString('Bob', $result);
    }

    public function testHelpCommandShowsDetails(): void
    {
        $app = new Application();
        ob_start();
        $code = $app->run(['switch', 'help', 'make:controller']);
        $output = ob_get_clean();

        $this->assertEquals(0, $code);
        $this->assertStringContainsString('make:controller', $output);
        $this->assertStringContainsString('name', $output);
    }

    public function testDuplicateControllerWarning(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_cli_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $oldCwd = getcwd();
        chdir($tmpDir);

        try {
            $app = new Application();

            // Create once
            ob_start();
            $app->run(['switch', 'make:controller', 'Duplicate']);
            ob_get_clean();

            // Create again — should warn
            ob_start();
            $code = $app->run(['switch', 'make:controller', 'Duplicate']);
            $output = ob_get_clean();

            $this->assertEquals(1, $code);
            $this->assertStringContainsString('already exists', $output);
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    public function testMakeActionSeederProviderMailGenerators(): void
    {
        $tmpDir = sys_get_temp_dir() . '/switch_console_gens_' . uniqid();
        mkdir($tmpDir, 0777, true);
        $oldCwd = getcwd();

        try {
            chdir($tmpDir);
            $app = new Application();

            // Test make:action
            ob_start();
            $codeAction = $app->run(['switch', 'make:action', 'ProcessOrder']);
            $outAction = ob_get_clean();
            $this->assertEquals(0, $codeAction);
            $this->assertFileExists($tmpDir . '/app/Actions/ProcessOrderAction.php');

            // Test make:seeder
            ob_start();
            $codeSeeder = $app->run(['switch', 'make:seeder', 'User']);
            $outSeeder = ob_get_clean();
            $this->assertEquals(0, $codeSeeder);
            $this->assertFileExists($tmpDir . '/database/seeders/UserSeeder.php');

            // Test make:provider
            ob_start();
            $codeProvider = $app->run(['switch', 'make:provider', 'Payment']);
            $outProvider = ob_get_clean();
            $this->assertEquals(0, $codeProvider);
            $this->assertFileExists($tmpDir . '/app/Providers/PaymentServiceProvider.php');

            // Test make:mail
            ob_start();
            $codeMail = $app->run(['switch', 'make:mail', 'Welcome']);
            $outMail = ob_get_clean();
            $this->assertEquals(0, $codeMail);
            $this->assertFileExists($tmpDir . '/app/Mail/WelcomeMail.php');
        } finally {
            chdir($oldCwd ?: '.');
            $this->recursiveDelete($tmpDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
