# Commands

Commands are the heart of any CLI application. In Yalla, commands are classes that extend the base `Command` class and define their own behavior.

## Creating a Command

### Basic Command Structure

Every command in Yalla extends the `Command` base class:

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

class GreetCommand extends Command
{
    public function __construct()
    {
        $this->name = 'greet';
        $this->description = 'Greet a user with a friendly message';
    }

    public function execute(array $input, Output $output): int
    {
        $output->success('Hello, World!');
        return 0; // Success
    }
}
```

### Command Properties

- **`name`**: The command name used to invoke it (e.g., `greet`)
- **`description`**: A brief description shown in help and list commands

### The Execute Method

The `execute` method is where your command logic lives:

```php
public function execute(array $input, Output $output): int
{
    // Your command logic here
    return 0; // Return 0 for success, non-zero for errors
}
```

## Arguments and Options

### Adding Arguments

Arguments are positional parameters passed to your command:

```php
public function __construct()
{
    $this->name = 'deploy';
    $this->description = 'Deploy application to a server';

    $this->addArgument('environment', 'Target environment', true); // Required
    $this->addArgument('version', 'Version to deploy', false);    // Optional
}
```

### Adding Options

Options are named parameters that can be passed with flags:

```php
public function __construct()
{
    $this->name = 'migrate';
    $this->description = 'Run database migrations';

    $this->addOption('force', 'f', 'Force migration without confirmation', false);
    $this->addOption('seed', 's', 'Seed the database after migration', false);
    $this->addOption('steps', null, 'Number of migrations to run', 1);
}
```

### Accessing Input

In your `execute` method, retrieve arguments and options:

```php
public function execute(array $input, Output $output): int
{
    // Get arguments
    $env = $this->getArgument($input, 'environment');
    $version = $this->getArgument($input, 'version', 'latest'); // With default

    // Get options
    $force = $this->getOption($input, 'force', false);
    $steps = $this->getOption($input, 'steps', 1);

    // Your logic here
    $output->info("Deploying {$version} to {$env}...");

    return 0;
}
```

## Command Examples

### File Processing Command

```php
class ProcessFileCommand extends Command
{
    public function __construct()
    {
        $this->name = 'process:file';
        $this->description = 'Process a file with various transformations';

        $this->addArgument('input', 'Input file path', true);
        $this->addArgument('output', 'Output file path', false);
        $this->addOption('format', 'f', 'Output format (json, csv, xml)', 'json');
        $this->addOption('compress', 'c', 'Compress output', false);
    }

    public function execute(array $input, Output $output): int
    {
        $inputFile = $this->getArgument($input, 'input');
        $outputFile = $this->getArgument($input, 'output', 'output.txt');
        $format = $this->getOption($input, 'format');
        $compress = $this->getOption($input, 'compress');

        if (!file_exists($inputFile)) {
            $output->error("File not found: {$inputFile}");
            return 1;
        }

        $output->info("Processing {$inputFile}...");

        // Show progress
        $output->progressBar(50, 100);

        // Process file...

        $output->success("File processed successfully!");
        $output->writeln("Output saved to: {$outputFile}");

        return 0;
    }
}
```

### Interactive Command

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

        // Display options in a table
        $output->table(
            ['Option', 'Current Value', 'Description'],
            [
                ['Database', 'MySQL', 'Database driver'],
                ['Cache', 'Redis', 'Cache driver'],
                ['Queue', 'Beanstalkd', 'Queue driver'],
            ]
        );

        // Show colored messages
        $output->success('✓ Configuration valid');
        $output->warning('⚠ Cache server not responding');
        $output->error('✗ Database connection failed');

        // Display tree structure
        $output->tree([
            'config' => [
                'app.php' => 'Application settings',
                'database.php' => 'Database configuration',
                'cache.php' => 'Cache settings'
            ]
        ]);

        return 0;
    }
}
```

## Registering Commands

### In Your Application

Register commands with your application:

```php
use Yalla\Application;

$app = new Application('My CLI', '1.0.0');

// Register individual commands
$app->register(new GreetCommand());
$app->register(new ProcessFileCommand());
$app->register(new SetupCommand());

// Run the application
$app->run();
```

### Auto-discovery Pattern

For larger applications, implement auto-discovery:

```php
class CommandLoader
{
    public static function loadCommands(Application $app, string $directory)
    {
        $files = glob($directory . '/*Command.php');

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = "App\\Commands\\{$className}";

            if (class_exists($fullClassName)) {
                $app->register(new $fullClassName());
            }
        }
    }
}

// Usage
CommandLoader::loadCommands($app, __DIR__ . '/src/Commands');
```

## Command Scaffolding

Yalla includes a built-in command generator:

```bash
# Create a new command
./bin/yalla create:command deploy

# With custom class name
./bin/yalla create:command deploy --class=DeployApplicationCommand

# In a custom directory
./bin/yalla create:command deploy --dir=src/Commands/Deployment

# Force overwrite
./bin/yalla create:command deploy --force
```

This generates a command template:

```php
<?php

declare(strict_types=1);

namespace App\Commands;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class DeployCommand extends Command
{
    public function __construct()
    {
        $this->name = 'deploy';
        $this->description = 'Description of your command';

        // Define arguments and options here
    }

    public function execute(array $input, Output $output): int
    {
        $output->info('Executing deploy command...');

        // Your command logic here

        $output->success('deploy completed successfully!');

        return 0;
    }
}
```

## Best Practices

### 1. Single Responsibility
Each command should have one clear purpose.

### 2. Validation
Validate input early and provide clear error messages:

```php
public function execute(array $input, Output $output): int
{
    $file = $this->getArgument($input, 'file');

    if (!file_exists($file)) {
        $output->error("File not found: {$file}");
        return 1;
    }

    if (!is_readable($file)) {
        $output->error("File is not readable: {$file}");
        return 1;
    }

    // Continue processing...
}
```

### 3. Exit Codes
Use standard exit codes:
- `0`: Success
- `1`: General error
- `2`: Misuse of command
- `126`: Command cannot execute
- `127`: Command not found

### 4. Progress Feedback
For long-running operations, provide feedback:

```php
$total = count($items);
foreach ($items as $i => $item) {
    $output->progressBar($i + 1, $total);
    // Process item...
}
```

### 5. Error Handling
Handle exceptions gracefully:

```php
public function execute(array $input, Output $output): int
{
    try {
        // Command logic
        return 0;
    } catch (\Exception $e) {
        $output->error('An error occurred: ' . $e->getMessage());
        return 1;
    }
}
```

## Next Steps

- [Arguments and Options](./arguments-options.md) - Deep dive into input handling
- [Output Formatting](./output.md) - Learn about output styling and formatting
- [Testing Commands](./testing.md) - Write tests for your commands
- [REPL Integration](./repl.md) - Add REPL support to your commands