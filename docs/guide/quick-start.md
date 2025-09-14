# Quick Start

This guide will help you create your first Yalla CLI application in just a few minutes.

## Step 1: Install Yalla

```bash
composer require marwen-brini/yalla
```

## Step 2: Create Your CLI Script

Create a new file `cli` (or any name you prefer):

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Yalla\Application;
use Yalla\Commands\Command;
use Yalla\Output\Output;

// Create a simple greeting command
class GreetCommand extends Command
{
    public function __construct()
    {
        $this->name = 'greet';
        $this->description = 'Greet someone';

        $this->addArgument('name', 'The name to greet', true);
        $this->addOption('yell', 'y', 'Yell the greeting', false);
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $message = "Hello, $name!";

        if ($this->getOption($input, 'yell')) {
            $message = strtoupper($message);
        }

        $output->success($message);

        return 0;
    }
}

// Create and run the application
$app = new Application('My CLI', '1.0.0');
$app->register(new GreetCommand());
$app->run();
```

## Step 3: Make It Executable

```bash
chmod +x cli
```

## Step 4: Run Your Command

```bash
# Show available commands
./cli list

# Get help for your command
./cli help greet

# Run your command
./cli greet World
# Output: Hello, World!

# Run with options
./cli greet World --yell
# Output: HELLO, WORLD!
```

## Using the Command Generator

Yalla includes a built-in command generator to speed up development:

```bash
# Generate a new command
./cli create:command deploy

# This creates a file with the following structure:
```

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
        // $this->addArgument('name', 'description', required: true);
        // $this->addOption('name', 'shortcut', 'description', default);
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

## Using the Interactive REPL

Yalla includes a powerful REPL for interactive PHP development:

```bash
# Start the REPL
./cli repl

# In the REPL, you can:
> $x = 5
> $y = 10
> $x + $y
15

> :help  # Show available commands
> :vars  # Show defined variables
> :exit  # Exit the REPL
```

## Project Structure

Here's a recommended project structure for a Yalla CLI application:

```
my-cli-app/
├── bin/
│   └── cli              # Main CLI entry point
├── src/
│   └── Commands/        # Your custom commands
│       ├── DeployCommand.php
│       ├── MigrateCommand.php
│       └── TestCommand.php
├── tests/               # Tests for your commands
├── composer.json
└── README.md
```

## Next Steps

Now that you have a working CLI application:

1. **Add More Commands**: Create additional commands for your application
2. **Use Output Formatting**: Explore tables, progress bars, and colored output
3. **Add Validation**: Validate input arguments and options
4. **Write Tests**: Test your commands using Pest or PHPUnit
5. **Configure REPL**: Customize the REPL with extensions and shortcuts

## Common Patterns

### Command with Multiple Arguments

```php
$this->addArgument('source', 'Source file', true);
$this->addArgument('destination', 'Destination file', true);
$this->addArgument('format', 'Output format', false);
```

### Command with Various Options

```php
$this->addOption('force', 'f', 'Force overwrite', false);
$this->addOption('verbose', 'v', 'Verbose output', false);
$this->addOption('dry-run', null, 'Simulate without changes', false);
$this->addOption('timeout', 't', 'Timeout in seconds', 30);
```

### Interactive Progress

```php
$items = range(1, 100);
foreach ($items as $i) {
    $output->progressBar($i, 100);
    // Process item...
    usleep(10000); // Simulate work
}
```

### Formatted Output

```php
// Tables
$output->table(
    ['ID', 'Name', 'Status'],
    [
        ['1', 'Server A', 'Running'],
        ['2', 'Server B', 'Stopped'],
    ]
);

// Colored messages
$output->success('✓ Task completed');
$output->error('✗ Task failed');
$output->warning('⚠ Warning: Check configuration');
$output->info('ℹ Processing...');
```

## Tips and Tricks

1. **Use Descriptive Names**: Command names should be clear and follow a pattern (e.g., `db:migrate`, `cache:clear`)

2. **Provide Help Text**: Always add descriptions for commands, arguments, and options

3. **Return Proper Exit Codes**: Return 0 for success, non-zero for errors

4. **Handle Errors Gracefully**: Catch exceptions and provide helpful error messages

5. **Use the REPL for Testing**: Test your code snippets quickly in the REPL before implementing them in commands