# Application

The `Application` class is the main entry point for your CLI application. It handles command registration, input parsing, and command execution.

## Class Definition

```php
namespace Yalla;

class Application
{
    private string $name;
    private string $version;
    private CommandRegistry $registry;
    private Output $output;
    private InputParser $input;
}
```

## Constructor

```php
public function __construct(string $name = 'Yalla CLI', string $version = '1.0.0')
```

Creates a new Application instance.

### Parameters

- `$name` (string): The name of your CLI application
- `$version` (string): The version of your CLI application

### Example

```php
$app = new Application('My CLI', '2.0.0');
```

## Methods

### register()

```php
public function register($command): self
```

Registers a command with the application.

#### Parameters

- `$command` (Command): The command instance to register

#### Returns

- `self`: The Application instance for method chaining

#### Example

```php
$app->register(new DeployCommand())
    ->register(new BackupCommand())
    ->register(new MigrateCommand());
```

### run()

```php
public function run(): int
```

Runs the application by parsing input and executing the appropriate command.

#### Returns

- `int`: Exit code (0 for success, non-zero for errors)

#### Example

```php
$exitCode = $app->run();
exit($exitCode);
```

### getName()

```php
public function getName(): string
```

Returns the application name.

#### Returns

- `string`: The application name

### getVersion()

```php
public function getVersion(): string
```

Returns the application version.

#### Returns

- `string`: The application version

## Default Commands

The Application automatically registers these built-in commands:

- `help` - Show help for a command
- `list` - List all available commands
- `create:command` - Generate a new command class
- `repl` - Start an interactive REPL session
- `init:repl` - Create REPL configuration file
- `init` - Initialize a new CLI project

## Complete Example

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Yalla\Application;
use App\Commands\DeployCommand;
use App\Commands\BackupCommand;
use App\Commands\TestCommand;

// Create application
$app = new Application('DevOps CLI', '1.5.0');

// Register custom commands
$app->register(new DeployCommand())
    ->register(new BackupCommand())
    ->register(new TestCommand());

// Run application
$exitCode = $app->run();

// Exit with appropriate code
exit($exitCode);
```

## Error Handling

The Application class handles errors gracefully:

```php
try {
    $app->run();
} catch (\Exception $e) {
    // Errors are caught and displayed
    // Exit code 1 is returned
}
```

### Command Not Found

When a command is not found:

```
Command 'unknown' not found.
```

Exit code: 1

### Missing Arguments

When required arguments are missing, the command should handle validation:

```php
public function execute(array $input, Output $output): int
{
    $file = $this->getArgument($input, 'file');

    if (!$file) {
        $output->error('File argument is required');
        return 1;
    }

    // Continue...
    return 0;
}
```

## Input Handling

The Application uses `InputParser` to parse command-line arguments:

```bash
./cli command arg1 arg2 --option=value -f
```

Parsed as:

```php
[
    'command' => 'command',
    'arguments' => ['arg1', 'arg2'],
    'options' => [
        'option' => 'value',
        'f' => true
    ]
]
```

## Default Behavior

When no command is specified, the `list` command is executed:

```bash
./cli
# Equivalent to: ./cli list
```

## Extending Application

You can extend the Application class for custom behavior:

```php
class MyApplication extends Application
{
    protected function registerDefaultCommands(): void
    {
        parent::registerDefaultCommands();

        // Add your default commands
        $this->registry->register(new CustomDefaultCommand());
    }

    public function run(): int
    {
        // Custom pre-run logic
        $this->output->writeln($this->getBanner());

        // Run application
        return parent::run();
    }

    private function getBanner(): string
    {
        return <<<BANNER
        ╔═══════════════════════════╗
        ║   {$this->getName()} v{$this->getVersion()}   ║
        ╚═══════════════════════════╝
        BANNER;
    }
}
```

## Testing

Testing an Application:

```php
test('application runs command', function () {
    $app = new Application('Test', '1.0');
    $app->register(new TestCommand());

    $_SERVER['argv'] = ['cli', 'test', 'arg'];

    $result = $app->run();

    expect($result)->toBe(0);
});
```

## Environment Variables

The Application respects these environment variables:

- `NO_COLOR` - Disable colored output
- `TERM` - Terminal type for color support detection

## Thread Safety

The Application class is not thread-safe. Each thread should have its own Application instance.

## Performance Considerations

- Commands are lazy-loaded when accessed
- The registry uses a hash map for O(1) command lookup
- Input parsing is done once per run

## See Also

- [Command](./command.md) - Base command class
- [CommandRegistry](./command-registry.md) - Command registration and management
- [Output](./output.md) - Output formatting
- [InputParser](./input-parser.md) - Input parsing