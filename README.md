# Yalla CLI

[![Tests](https://github.com/marwen-brini/yalla/actions/workflows/run-tests.yml/badge.svg)](https://github.com/marwen-brini/yalla/actions/workflows/run-tests.yml)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](https://github.com/marwen-brini/yalla)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%20to%208.4-blue)](https://www.php.net)
[![Latest Version](https://img.shields.io/badge/version-1.3.0-orange)](https://github.com/marwen-brini/yalla/releases)
[![Documentation](https://img.shields.io/badge/docs-vitepress-blue)](https://marwen-brini.github.io/Yalla/)

A standalone PHP CLI framework built from scratch without dependencies.

📚 **[Read the full documentation](https://marwen-brini.github.io/Yalla/)**

## Features

- **Zero Dependencies**: Built entirely from scratch without relying on Symfony Console or other frameworks
- **Interactive REPL**: Full-featured Read-Eval-Print-Loop for interactive PHP development
- **Command Routing**: Custom command parser and router
- **Colored Output**: ANSI color support for beautiful terminal output (cross-platform)
- **Table Rendering**: Built-in table formatter with Unicode box drawing
- **Input Parsing**: Handles commands, arguments, and options (long and short formats)
- **Command Scaffolding**: Built-in `create:command` to generate new command boilerplate
- **History & Autocomplete**: REPL with command history and intelligent autocompletion
- **Extensible Architecture**: Plugin system for custom REPL extensions
- **100% Test Coverage**: Fully tested with Pest PHP
- **Cross-Platform**: Works on Windows, macOS, and Linux

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- Composer 2.0+

## Installation

```bash
composer require marwen-brini/yalla
```

## Usage

### Basic Usage

Create a CLI script (e.g., `bin/yalla`):

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Yalla\Application;

$app = new Application('Yalla CLI', '1.1.0');
$app->run();
```

Make it executable:
```bash
chmod +x bin/yalla
```

### Creating Custom Commands

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

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

// Register in your application
$app = new Application('Yalla CLI', '1.1.0');
$app->register(new GreetCommand());
$app->run();
```

### Running Commands

```bash
# List all commands
./bin/yalla list

# Get help for a command
./bin/yalla help greet

# Run a command
./bin/yalla greet World
./bin/yalla greet World --yell
./bin/yalla greet World -y
```

### Command Scaffolding

Yalla includes a built-in command generator to quickly create new commands:

```bash
# Create a new command with default settings
./bin/yalla create:command deploy

# Create with custom class name
./bin/yalla create:command deploy --class=DeployApplicationCommand

# Create in a custom directory
./bin/yalla create:command deploy --dir=src/Commands/Deployment

# Force overwrite if file exists
./bin/yalla create:command deploy --force
```

This will generate a command class with the proper structure:

```php
<?php

declare(strict_types=1);

namespace Yalla\Commands;

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

## Interactive REPL

Yalla includes a powerful REPL (Read-Eval-Print-Loop) for interactive PHP development:

### Starting the REPL

```bash
# Start the REPL
./bin/yalla repl

# With custom configuration
./bin/yalla repl --config=repl.config.php

# Disable colors
./bin/yalla repl --no-colors

# Disable history
./bin/yalla repl --no-history
```

### REPL Commands

- `:help` - Show available commands
- `:exit` - Exit the REPL
- `:vars` - Show defined variables
- `:imports` - Show imported classes
- `:clear` - Clear the screen
- `:history` - Show command history
- `:mode [mode]` - Switch display mode (compact, verbose, json, dump)

### REPL Features

- **Command History**: Navigate through previous commands with up/down arrows
- **Variable Persistence**: Variables defined in the REPL persist across commands
- **Shortcuts**: Define shortcuts for frequently used classes
- **Auto-imports**: Automatically import specified classes
- **Custom Extensions**: Add your own commands and functionality
- **Multiple Display Modes**: Choose between compact, verbose, JSON, or dump output formats
- **Smart Object Display**: Enhanced display for objects with public properties or `__toString()` methods
- **Semicolon Support**: Natural PHP syntax with trailing semicolons
- **ORM-Friendly**: Properly handles objects with protected/private properties

### Display Modes

The REPL supports multiple display modes for different use cases:

```php
# Compact mode (default) - Clean, colorized output
[1] > ['id' => 1, 'name' => 'Alice']
['id' => 1, 'name' => "Alice"]

# Verbose mode - Detailed object/array information
[2] > :mode verbose
[3] > $user
═══ Object Details ═══
Class: User
Properties:
  public $id = 1
  public $name = "Alice"
Public Methods:
  - save($params)
  - delete()

# JSON mode - Perfect for API data
[4] > :mode json
[5] > ['status' => 'success', 'data' => ['id' => 1]]
{
  "status": "success",
  "data": {"id": 1}
}

# Dump mode - Traditional PHP debugging
[6] > :mode dump
[7] > "test"
string(4) "test"
```

### REPL Configuration

Create a `repl.config.php` file:

```php
<?php

return [
    'shortcuts' => [
        'User' => '\App\Models\User',
        'DB' => '\Illuminate\Support\Facades\DB',
    ],
    
    'imports' => [
        ['class' => '\Carbon\Carbon', 'alias' => 'Carbon'],
    ],
    
    'display' => [
        'prompt' => 'myapp> ',
        'performance' => true, // Show execution time and memory
        'mode' => 'compact',   // Options: compact, verbose, json, dump
    ],
    
    'history' => [
        'file' => $_ENV['HOME'] . '/.myapp_history',
        'max_entries' => 1000,
    ],
];
```

### Creating REPL Extensions

```php
use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;

class MyExtension implements ReplExtension
{
    public function register(ReplContext $context): void
    {
        // Add custom commands
        $context->addCommand('models', function($args, $output) {
            // List all models
        });
        
        // Add shortcuts
        $context->addShortcut('User', '\App\Models\User');
        
        // Add custom formatters
        $context->addFormatter('MyClass', function($value, $output) {
            $output->info('Custom formatting for MyClass');
        });
    }
    
    public function boot(): void
    {
        // Bootstrap your extension
    }
    
    public function getName(): string
    {
        return 'My Custom Extension';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function getDescription(): string
    {
        return 'Adds custom functionality to the REPL';
    }
}
```

## Creating Commands

Commands extend the base `Command` class:

```php
use Yalla\Commands\Command;
use Yalla\Output\Output;

class MyCommand extends Command
{
    public function __construct()
    {
        $this->name = 'my:command';
        $this->description = 'Does something awesome';
        
        // Add arguments
        $this->addArgument('input', 'Input file', true);
        $this->addArgument('output', 'Output file', false);
        
        // Add options
        $this->addOption('force', 'f', 'Force overwrite', false);
        $this->addOption('verbose', 'v', 'Verbose output', false);
    }
    
    public function execute(array $input, Output $output): int
    {
        // Get arguments
        $inputFile = $this->getArgument($input, 'input');
        $outputFile = $this->getArgument($input, 'output', 'output.txt');
        
        // Get options
        $force = $this->getOption($input, 'force', false);
        $verbose = $this->getOption($input, 'verbose', false);
        
        // Use output methods
        $output->info('Processing...');
        $output->success('Done!');
        $output->error('Something went wrong!');
        $output->warning('Be careful!');
        
        // Draw tables
        $output->table(
            ['Name', 'Age', 'City'],
            [
                ['John', '30', 'New York'],
                ['Jane', '25', 'London'],
            ]
        );
        
        return 0; // Success
    }
}
```

## Output Formatting

The `Output` class provides various formatting methods:

```php
// Basic output
$output->write('Hello');           // No newline
$output->writeln('Hello');         // With newline

// Colored output
$output->success('Success!');      // Green
$output->error('Error!');          // Red
$output->warning('Warning!');      // Yellow
$output->info('Info');             // Cyan

// Custom colors
$output->writeln($output->color('Custom', Output::MAGENTA));

// Tables
$output->table(['Header 1', 'Header 2'], $rows);
```

## Architecture

Yalla is built with a clean, modular architecture:

- **Application**: Main entry point that handles command registration and execution
- **CommandRegistry**: Manages command registration and retrieval
- **InputParser**: Parses CLI arguments into structured data
- **Output**: Handles all terminal output with color support
- **Command**: Base class for all commands

## Testing

Yalla maintains 100% code coverage with comprehensive test suite using Pest PHP:

```bash
# Run tests
composer test

# Run tests with coverage (requires Xdebug or PCOV)
composer test-coverage

# Generate HTML coverage report
composer test-coverage-html

# Run specific test file
vendor/bin/pest tests/ApplicationTest.php
```

## Documentation

Full documentation is available at [https://marwen-brini.github.io/Yalla/](https://marwen-brini.github.io/Yalla/)

### Quick Links

- [Getting Started Guide](https://marwen-brini.github.io/Yalla/guide/getting-started)
- [API Reference](https://marwen-brini.github.io/Yalla/api/application)
- [Examples](https://marwen-brini.github.io/Yalla/examples/basic-usage)
- [REPL Documentation](https://marwen-brini.github.io/Yalla/guide/repl)

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Quick Start

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Author

**Marwen-Brini** - *Initial work* - [GitHub](https://github.com/marwen-brini)

## Acknowledgments

- Thanks to all contributors who have helped shape Yalla CLI
- Inspired by the simplicity and power of modern CLI tools
- Built with love for the PHP community