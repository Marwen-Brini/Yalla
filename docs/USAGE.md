# Using Yalla as a Composer Dependency

## Installation

```bash
composer require marwen-brini/yalla
```

## Quick Start

### 1. Initialize Your CLI Application

After installing Yalla, run the initialization command:

```bash
./vendor/bin/yalla init --name="My App" --bin=myapp
```

This creates:
- `myapp` - Your CLI entry point
- `yalla.config.php` - Configuration file
- `src/Commands/` - Directory for your commands
- `src/Commands/ExampleCommand.php` - Example command

### 2. Make Your CLI Executable

```bash
chmod +x myapp
```

### 3. Run Your CLI

```bash
./myapp
```

## Creating Commands

### Method 1: Using the Generator (Recommended)

```bash
./myapp make:command greet
```

This creates a new command file and shows you how to register it.

### Method 2: Manual Creation

Create a file `src/Commands/GreetCommand.php`:

```php
<?php

namespace App\Commands;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class GreetCommand extends Command
{
    public function __construct()
    {
        $this->name = 'greet';
        $this->description = 'Greet someone';
        
        $this->addArgument('name', 'Person to greet', 'World');
        $this->addOption('yell', 'y', 'Yell the greeting', false);
    }
    
    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $greeting = "Hello, $name!";
        
        if ($this->getOption($input, 'yell')) {
            $greeting = strtoupper($greeting);
        }
        
        $output->success($greeting);
        
        return 0;
    }
}
```

## Registering Commands

Add your command to `yalla.config.php`:

```php
return [
    'name' => 'My App',
    'version' => '1.0.0',
    
    'commands' => [
        \App\Commands\GreetCommand::class,
        \App\Commands\AnotherCommand::class,
        // Add more commands here
    ],
    
    'command_namespace' => 'App\\Commands',
    'command_directory' => 'src/Commands',
];
```

## Project Structure

After initialization, your project structure should look like:

```
your-project/
├── vendor/
│   └── marwen-brini/yalla/
├── src/
│   └── Commands/
│       ├── ExampleCommand.php
│       └── GreetCommand.php
├── composer.json
├── yalla.config.php
├── repl.config.php (optional, for REPL)
└── myapp (your CLI executable)
```

## Configuration

### yalla.config.php

This file configures your CLI application:

```php
return [
    // Application metadata
    'name' => 'My CLI App',
    'version' => '1.0.0',
    
    // Commands to register
    'commands' => [
        \App\Commands\Command1::class,
        \App\Commands\Command2::class,
    ],
    
    // For make:command generator
    'command_namespace' => 'App\\Commands',
    'command_directory' => 'src/Commands',
];
```

## Advanced Usage

### Custom Application Bootstrap

You can customize the CLI entry point created by `init`:

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Yalla\Application;
use App\Services\Database;

// Load configuration
$config = require __DIR__ . '/yalla.config.php';

// Bootstrap your services
$database = new Database();
$database->connect();

// Create application
$app = new Application($config['name'], $config['version']);

// Register commands with dependency injection
foreach ($config['commands'] as $commandClass) {
    $command = new $commandClass($database);
    $app->register($command);
}

// Run
exit($app->run($argv ?? []));
```

### Using the REPL

Initialize REPL configuration:

```bash
./vendor/bin/yalla init:repl
```

Then run the REPL:

```bash
./vendor/bin/yalla repl
```

Or from your custom CLI:

```bash
./myapp repl
```

### Framework Integration

#### Laravel Package

If building a Laravel package that uses Yalla:

```php
// In your ServiceProvider
public function boot()
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            \YourPackage\Commands\YourCommand::class,
        ]);
    }
}
```

#### Symfony Bundle

```php
// In your Bundle extension
public function load(array $configs, ContainerBuilder $container)
{
    $container->registerForAutoconfiguration(Command::class)
        ->addTag('console.command');
}
```

## Examples

### Database Migration Command

```php
<?php

namespace App\Commands;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class MigrateCommand extends Command
{
    private $database;
    
    public function __construct($database)
    {
        $this->database = $database;
        $this->name = 'migrate';
        $this->description = 'Run database migrations';
        
        $this->addOption('fresh', null, 'Drop all tables first', false);
        $this->addOption('seed', null, 'Run seeders after migration', false);
    }
    
    public function execute(array $input, Output $output): int
    {
        if ($this->getOption($input, 'fresh')) {
            $output->info('Dropping all tables...');
            $this->database->dropAll();
        }
        
        $output->info('Running migrations...');
        
        $migrations = glob('database/migrations/*.php');
        $output->progressBar(0, count($migrations));
        
        foreach ($migrations as $i => $migration) {
            require_once $migration;
            // Run migration...
            $output->progressBar($i + 1, count($migrations));
        }
        
        $output->success('Migrations completed!');
        
        if ($this->getOption($input, 'seed')) {
            $output->info('Running seeders...');
            // Run seeders...
        }
        
        return 0;
    }
}
```

### Interactive Command

```php
<?php

namespace App\Commands;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class SetupCommand extends Command
{
    public function __construct()
    {
        $this->name = 'setup';
        $this->description = 'Interactive project setup';
    }
    
    public function execute(array $input, Output $output): int
    {
        $output->section('Project Setup');
        
        // Ask questions
        echo 'Project name: ';
        $projectName = trim(fgets(STDIN));
        
        echo 'Database name: ';
        $dbName = trim(fgets(STDIN));
        
        // Create config
        $config = [
            'name' => $projectName,
            'database' => $dbName,
        ];
        
        file_put_contents('.env', $this->generateEnv($config));
        
        $output->success('Setup completed!');
        $output->tree([
            'Created files' => [
                '.env',
                'config/',
                'database/',
            ]
        ]);
        
        return 0;
    }
    
    private function generateEnv(array $config): string
    {
        return "APP_NAME={$config['name']}\nDB_DATABASE={$config['database']}\n";
    }
}
```

## Tips

1. **Namespace Detection**: The `init` command tries to detect your namespace from `composer.json`. Make sure your PSR-4 autoloading is configured correctly.

2. **Command Names**: Command names should be kebab-case (e.g., `make:model`, `cache:clear`).

3. **Exit Codes**: Return 0 for success, non-zero for failure.

4. **Output Methods**: Use appropriate output methods:
   - `$output->success()` - Green success messages
   - `$output->error()` - Red error messages  
   - `$output->warning()` - Yellow warnings
   - `$output->info()` - Cyan info messages
   - `$output->dim()` - Gray supplementary text

5. **Testing Commands**: Create unit tests for your commands:

```php
use App\Commands\GreetCommand;
use Yalla\Output\Output;

test('greet command works', function () {
    $command = new GreetCommand();
    $output = new Output();
    
    $result = $command->execute([
        'arguments' => ['name' => 'Alice'],
        'options' => ['yell' => true]
    ], $output);
    
    expect($result)->toBe(0);
});
```