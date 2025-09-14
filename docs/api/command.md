# Command

The `Command` class is the base class for all CLI commands in Yalla.

## Class Definition

```php
namespace Yalla\Commands;

abstract class Command
{
    protected string $name;
    protected string $description;
    protected array $arguments = [];
    protected array $options = [];

    abstract public function execute(array $input, Output $output): int;
}
```

## Properties

### $name

```php
protected string $name
```

The name of the command used to invoke it from the CLI.

### $description

```php
protected string $description
```

A brief description of what the command does, shown in help and list outputs.

### $arguments

```php
protected array $arguments = []
```

Array of registered arguments for the command.

### $options

```php
protected array $options = []
```

Array of registered options for the command.

## Abstract Methods

### execute()

```php
abstract public function execute(array $input, Output $output): int
```

The main execution method that must be implemented by all commands.

#### Parameters

- `$input` (array): Parsed input containing command, arguments, and options
- `$output` (Output): Output instance for displaying results

#### Returns

- `int`: Exit code (0 for success, non-zero for error)

## Public Methods

### getName()

```php
public function getName(): string
```

Returns the command name.

### getDescription()

```php
public function getDescription(): string
```

Returns the command description.

### getArguments()

```php
public function getArguments(): array
```

Returns all registered arguments.

### getOptions()

```php
public function getOptions(): array
```

Returns all registered options.

## Protected Methods

### addArgument()

```php
protected function addArgument(
    string $name,
    string $description,
    bool $required = false
): self
```

Adds an argument to the command.

#### Parameters

- `$name` (string): The argument name
- `$description` (string): Description of the argument
- `$required` (bool): Whether the argument is required

#### Returns

- `self`: For method chaining

#### Example

```php
$this->addArgument('file', 'Input file path', true);
$this->addArgument('output', 'Output file path', false);
```

### addOption()

```php
protected function addOption(
    string $name,
    ?string $shortcut,
    string $description,
    $default = null
): self
```

Adds an option to the command.

#### Parameters

- `$name` (string): The option name (long format)
- `$shortcut` (?string): Single character shortcut (can be null)
- `$description` (string): Description of the option
- `$default` (mixed): Default value if option is not provided

#### Returns

- `self`: For method chaining

#### Example

```php
$this->addOption('force', 'f', 'Force overwrite', false);
$this->addOption('verbose', 'v', 'Verbose output', false);
$this->addOption('timeout', 't', 'Timeout in seconds', 30);
```

### getArgument()

```php
protected function getArgument(
    array $input,
    string $name,
    $default = null
)
```

Gets an argument value from the input.

#### Parameters

- `$input` (array): The parsed input array
- `$name` (string): The argument name or index
- `$default` (mixed): Default value if argument is not found

#### Returns

- `mixed`: The argument value or default

#### Example

```php
$file = $this->getArgument($input, 'file');
$output = $this->getArgument($input, 'output', 'output.txt');
```

### getOption()

```php
protected function getOption(
    array $input,
    string $name,
    $default = null
)
```

Gets an option value from the input.

#### Parameters

- `$input` (array): The parsed input array
- `$name` (string): The option name
- `$default` (mixed): Default value if option is not found

#### Returns

- `mixed`: The option value or default

#### Example

```php
$force = $this->getOption($input, 'force', false);
$timeout = $this->getOption($input, 'timeout', 30);
```

## Creating a Custom Command

### Basic Example

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

class DeployCommand extends Command
{
    public function __construct()
    {
        $this->name = 'deploy';
        $this->description = 'Deploy application to server';

        $this->addArgument('environment', 'Target environment', true);
        $this->addOption('branch', 'b', 'Git branch to deploy', 'main');
        $this->addOption('force', 'f', 'Force deployment', false);
    }

    public function execute(array $input, Output $output): int
    {
        $env = $this->getArgument($input, 'environment');
        $branch = $this->getOption($input, 'branch', 'main');
        $force = $this->getOption($input, 'force', false);

        $output->info("Deploying branch '$branch' to $env");

        if ($force) {
            $output->warning('Force mode enabled');
        }

        // Deployment logic here...

        $output->success('Deployment completed successfully!');

        return 0;
    }
}
```

### Advanced Example

```php
class BackupCommand extends Command
{
    public function __construct()
    {
        $this->name = 'backup';
        $this->description = 'Create a backup of the database';

        // Multiple arguments
        $this->addArgument('database', 'Database name', true);
        $this->addArgument('destination', 'Backup destination', false);

        // Various option types
        $this->addOption('compress', 'c', 'Compress backup', false);
        $this->addOption('format', 'f', 'Backup format (sql|json)', 'sql');
        $this->addOption('exclude', 'e', 'Tables to exclude', []);
        $this->addOption('quiet', 'q', 'Suppress output', false);
    }

    public function execute(array $input, Output $output): int
    {
        $database = $this->getArgument($input, 'database');
        $destination = $this->getArgument($input, 'destination', "./backups/{$database}.sql");

        // Validate format
        $format = $this->getOption($input, 'format', 'sql');
        if (!in_array($format, ['sql', 'json'])) {
            $output->error("Invalid format: $format");
            return 1;
        }

        // Check if destination exists
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            $output->error("Directory does not exist: $dir");
            return 1;
        }

        $quiet = $this->getOption($input, 'quiet', false);

        if (!$quiet) {
            $output->info("Starting backup of database: $database");
        }

        try {
            // Perform backup
            $this->performBackup($database, $destination, $format);

            if (!$quiet) {
                $output->success("Backup saved to: $destination");
            }

            return 0;

        } catch (\Exception $e) {
            $output->error('Backup failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function performBackup(string $db, string $dest, string $format): void
    {
        // Backup implementation
    }
}
```

## Input Structure

The `$input` array passed to `execute()` has this structure:

```php
[
    'command' => 'deploy',
    'arguments' => ['production', 'v2.0'],
    'options' => [
        'force' => true,
        'branch' => 'release',
        'f' => true,  // Shortcut also included
        'b' => 'release'  // Shortcut also included
    ]
]
```

## Exit Codes

Commands should return appropriate exit codes:

- `0`: Success
- `1`: General error
- `2`: Misuse of command
- `126`: Command cannot execute
- `127`: Command not found
- `130`: Script terminated by Ctrl+C

## Best Practices

### 1. Constructor Setup

Always define name, description, arguments, and options in the constructor:

```php
public function __construct()
{
    $this->name = 'command:name';
    $this->description = 'Clear and helpful description';

    // Define all arguments and options here
}
```

### 2. Input Validation

Validate input early in the execute method:

```php
public function execute(array $input, Output $output): int
{
    $file = $this->getArgument($input, 'file');

    if (!$file) {
        $output->error('File argument is required');
        return 1;
    }

    if (!file_exists($file)) {
        $output->error("File not found: $file");
        return 1;
    }

    // Continue with valid input...
}
```

### 3. Error Handling

Always handle exceptions and provide helpful error messages:

```php
try {
    $this->riskyOperation();
} catch (\Exception $e) {
    $output->error('Operation failed: ' . $e->getMessage());
    return 1;
}
```

### 4. Progress Feedback

For long-running operations, provide progress feedback:

```php
$items = $this->getItems();
$total = count($items);

foreach ($items as $i => $item) {
    $output->progressBar($i + 1, $total);
    $this->processItem($item);
}
```

## Testing Commands

```php
test('command executes successfully', function () {
    $command = new MyCommand();

    $input = [
        'command' => 'my:command',
        'arguments' => ['arg1'],
        'options' => ['opt' => 'value']
    ];

    $output = Mockery::mock(Output::class);
    $output->shouldReceive('success')->once();

    $result = $command->execute($input, $output);

    expect($result)->toBe(0);
});
```

## See Also

- [Application](./application.md) - Main application class
- [Output](./output.md) - Output formatting
- [CommandRegistry](./command-registry.md) - Command registration