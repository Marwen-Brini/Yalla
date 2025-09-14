# Basic Usage Examples

This page provides basic examples to get you started with Yalla CLI.

## Hello World Command

The simplest possible command:

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

class HelloCommand extends Command
{
    public function __construct()
    {
        $this->name = 'hello';
        $this->description = 'Say hello';
    }

    public function execute(array $input, Output $output): int
    {
        $output->writeln('Hello, World!');
        return 0;
    }
}
```

## Command with Arguments

Accept and use positional arguments:

```php
class GreetCommand extends Command
{
    public function __construct()
    {
        $this->name = 'greet';
        $this->description = 'Greet someone by name';

        $this->addArgument('name', 'The name to greet', true);
        $this->addArgument('greeting', 'Custom greeting', false);
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $greeting = $this->getArgument($input, 'greeting', 'Hello');

        $output->success("$greeting, $name!");

        return 0;
    }
}
```

Usage:

```bash
./cli greet Alice
# Output: Hello, Alice!

./cli greet Bob "Good morning"
# Output: Good morning, Bob!
```

## Command with Options

Use named options (flags):

```php
class DownloadCommand extends Command
{
    public function __construct()
    {
        $this->name = 'download';
        $this->description = 'Download a file';

        $this->addArgument('url', 'URL to download', true);

        $this->addOption('output', 'o', 'Output filename', null);
        $this->addOption('force', 'f', 'Force overwrite', false);
        $this->addOption('quiet', 'q', 'Quiet mode', false);
        $this->addOption('timeout', 't', 'Timeout in seconds', 30);
    }

    public function execute(array $input, Output $output): int
    {
        $url = $this->getArgument($input, 'url');
        $outputFile = $this->getOption($input, 'output', basename($url));
        $force = $this->getOption($input, 'force', false);
        $quiet = $this->getOption($input, 'quiet', false);
        $timeout = (int) $this->getOption($input, 'timeout', 30);

        if (file_exists($outputFile) && !$force) {
            $output->error("File exists: $outputFile. Use --force to overwrite.");
            return 1;
        }

        if (!$quiet) {
            $output->info("Downloading: $url");
            $output->writeln("Timeout: {$timeout}s");
        }

        // Download logic here...

        if (!$quiet) {
            $output->success("Downloaded to: $outputFile");
        }

        return 0;
    }
}
```

Usage:

```bash
./cli download https://example.com/file.zip
./cli download https://example.com/file.zip --output=myfile.zip
./cli download https://example.com/file.zip -o myfile.zip -f -q
./cli download https://example.com/file.zip --timeout=60 --force
```

## Colored Output

Use different colors and styles:

```php
class StatusCommand extends Command
{
    public function __construct()
    {
        $this->name = 'status';
        $this->description = 'Show system status';
    }

    public function execute(array $input, Output $output): int
    {
        // Semantic colors
        $output->success('✓ All systems operational');
        $output->error('✗ Database connection failed');
        $output->warning('⚠ High memory usage detected');
        $output->info('ℹ 5 updates available');

        // Custom colors
        $output->writeln($output->color('Custom red text', Output::RED));
        $output->writeln($output->color('Green background', Output::BG_GREEN));

        // Text styles
        $output->bold('Bold text');
        $output->dim('Dimmed text');
        $output->underline('Underlined text');

        return 0;
    }
}
```

## Tables

Display data in formatted tables:

```php
class ListUsersCommand extends Command
{
    public function __construct()
    {
        $this->name = 'users:list';
        $this->description = 'List all users';
    }

    public function execute(array $input, Output $output): int
    {
        $users = [
            ['1', 'Alice', 'alice@example.com', 'Admin'],
            ['2', 'Bob', 'bob@example.com', 'User'],
            ['3', 'Charlie', 'charlie@example.com', 'User'],
        ];

        $output->table(
            ['ID', 'Name', 'Email', 'Role'],
            $users
        );

        return 0;
    }
}
```

Output:

```
│ ID │ Name    │ Email              │ Role  │
├────┼─────────┼────────────────────┼───────┤
│ 1  │ Alice   │ alice@example.com  │ Admin │
│ 2  │ Bob     │ bob@example.com    │ User  │
│ 3  │ Charlie │ charlie@example.com │ User  │
```

## Progress Bar

Show progress for long operations:

```php
class ProcessCommand extends Command
{
    public function __construct()
    {
        $this->name = 'process';
        $this->description = 'Process files';

        $this->addArgument('directory', 'Directory to process', true);
    }

    public function execute(array $input, Output $output): int
    {
        $directory = $this->getArgument($input, 'directory');
        $files = glob($directory . '/*');
        $total = count($files);

        $output->info("Processing $total files...");

        foreach ($files as $i => $file) {
            // Show progress
            $output->progressBar($i + 1, $total);

            // Process file
            $this->processFile($file);

            // Small delay to see progress
            usleep(100000);
        }

        $output->writeln(''); // New line after progress
        $output->success('Processing complete!');

        return 0;
    }

    private function processFile(string $file): void
    {
        // Processing logic
    }
}
```

## Interactive Input

Get input from the user:

```php
class SetupCommand extends Command
{
    public function __construct()
    {
        $this->name = 'setup';
        $this->description = 'Interactive setup wizard';
    }

    public function execute(array $input, Output $output): int
    {
        $output->section('Setup Wizard');

        // Get user input
        $output->write('Enter your name: ');
        $name = trim(fgets(STDIN));

        // Yes/No confirmation
        $output->write('Enable debug mode? (y/n): ');
        $debug = strtolower(trim(fgets(STDIN))) === 'y';

        // Password input (hidden)
        $output->write('Enter password: ');
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        $output->writeln(''); // New line after password

        // Show configuration
        $output->section('Configuration');
        $output->writeln("Name: $name");
        $output->writeln("Debug: " . ($debug ? 'Enabled' : 'Disabled'));
        $output->writeln("Password: " . str_repeat('*', strlen($password)));

        // Confirm
        $output->write('Save configuration? (y/n): ');
        if (strtolower(trim(fgets(STDIN))) !== 'y') {
            $output->warning('Setup cancelled');
            return 1;
        }

        $output->success('Configuration saved!');

        return 0;
    }
}
```

## Error Handling

Handle errors gracefully:

```php
class BackupCommand extends Command
{
    public function __construct()
    {
        $this->name = 'backup';
        $this->description = 'Backup database';

        $this->addArgument('database', 'Database name', true);
        $this->addOption('output', 'o', 'Output file', null);
    }

    public function execute(array $input, Output $output): int
    {
        $database = $this->getArgument($input, 'database');
        $outputFile = $this->getOption($input, 'output', $database . '_backup.sql');

        try {
            // Validate database exists
            if (!$this->databaseExists($database)) {
                $output->error("Database '$database' not found");
                return 1;
            }

            // Check disk space
            if (!$this->hasEnoughSpace()) {
                $output->error('Insufficient disk space for backup');
                return 1;
            }

            $output->info("Backing up database: $database");

            // Perform backup
            $this->performBackup($database, $outputFile);

            $output->success("Backup saved to: $outputFile");

            return 0;

        } catch (\Exception $e) {
            $output->error('Backup failed: ' . $e->getMessage());

            // Clean up partial backup
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }

            return 1;
        }
    }

    private function databaseExists(string $name): bool
    {
        // Check if database exists
        return true; // Placeholder
    }

    private function hasEnoughSpace(): bool
    {
        // Check available disk space
        return disk_free_space('/') > 1000000000; // 1GB
    }

    private function performBackup(string $database, string $output): void
    {
        // Backup logic
    }
}
```

## Complete Application

Putting it all together:

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Yalla\Application;
use Yalla\Commands\Command;
use Yalla\Output\Output;

// Define commands
class InfoCommand extends Command
{
    public function __construct()
    {
        $this->name = 'info';
        $this->description = 'Show PHP information';
    }

    public function execute(array $input, Output $output): int
    {
        $output->section('PHP Information');
        $output->writeln('Version: ' . PHP_VERSION);
        $output->writeln('OS: ' . PHP_OS);
        $output->writeln('Memory Limit: ' . ini_get('memory_limit'));

        $output->section('Loaded Extensions');
        $extensions = get_loaded_extensions();
        sort($extensions);

        foreach (array_chunk($extensions, 4) as $chunk) {
            $output->writeln(implode(', ', $chunk));
        }

        return 0;
    }
}

class ClearCommand extends Command
{
    public function __construct()
    {
        $this->name = 'clear';
        $this->description = 'Clear cache';

        $this->addOption('all', 'a', 'Clear all caches', false);
        $this->addOption('force', 'f', 'Force clear without confirmation', false);
    }

    public function execute(array $input, Output $output): int
    {
        $all = $this->getOption($input, 'all', false);
        $force = $this->getOption($input, 'force', false);

        if (!$force) {
            $output->write('Clear cache? (y/n): ');
            if (strtolower(trim(fgets(STDIN))) !== 'y') {
                $output->info('Cancelled');
                return 0;
            }
        }

        $output->info('Clearing cache...');

        // Clear specific caches
        $caches = $all
            ? ['app', 'config', 'route', 'view']
            : ['app'];

        foreach ($caches as $cache) {
            $output->writeln("Clearing $cache cache...");
            // Clear logic here
            usleep(200000); // Simulate work
        }

        $output->success('Cache cleared successfully!');

        return 0;
    }
}

// Create and run application
$app = new Application('My CLI App', '1.0.0');

$app->register(new InfoCommand())
    ->register(new ClearCommand());

exit($app->run());
```

## Running the Examples

Save any example as a PHP file and run:

```bash
# Make executable
chmod +x mycli.php

# Run commands
./mycli.php info
./mycli.php clear
./mycli.php clear --all --force
./mycli.php help clear
./mycli.php list
```