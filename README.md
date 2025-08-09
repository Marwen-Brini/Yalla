# Yalla CLI

[![Tests](https://github.com/Marwen-Brini/Yalla/actions/workflows/run-tests.yml/badge.svg)](https://github.com/Marwen-Brini/Yalla/actions/workflows/run-tests.yml)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](https://github.com/Marwen-Brini/Yalla)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue)](https://www.php.net)

A standalone PHP CLI framework built from scratch without dependencies.

## Features

- **Zero Dependencies**: Built entirely from scratch without relying on Symfony Console or other frameworks
- **Command Routing**: Custom command parser and router
- **Colored Output**: ANSI color support for beautiful terminal output (cross-platform)
- **Table Rendering**: Built-in table formatter with Unicode box drawing
- **Input Parsing**: Handles commands, arguments, and options (long and short formats)
- **Command Scaffolding**: Built-in `create:command` to generate new command boilerplate
- **100% Test Coverage**: Fully tested with Pest PHP
- **Extensible**: Easy to add custom commands
- **Cross-Platform**: Works on Windows, macOS, and Linux

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

# Run tests with coverage report
composer test -- --coverage

# Run specific test file
vendor/bin/pest tests/ApplicationTest.php
```

## License

MIT