# Yalla CLI

A standalone PHP CLI framework built from scratch without dependencies.

## Features

- **Zero Dependencies**: Built entirely from scratch without relying on Symfony Console or other frameworks
- **Command Routing**: Custom command parser and router
- **Colored Output**: ANSI color support for beautiful terminal output
- **Table Rendering**: Built-in table formatter for structured data
- **Input Parsing**: Handles commands, arguments, and options (long and short formats)
- **Extensible**: Easy to add custom commands

## Installation

```bash
composer require marwen-brini/yalla
```

## Usage

### Basic Usage

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Yalla\Application;
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

$app = new Application('My CLI', '1.0.0');
$app->register(new GreetCommand());
$app->run();
```

### Running Commands

```bash
# List all commands
./my-cli list

# Get help for a command
./my-cli help greet

# Run a command
./my-cli greet World
./my-cli greet World --yell
./my-cli greet World -y
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

## Testing

```bash
composer test
```

## License

MIT