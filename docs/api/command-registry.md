# CommandRegistry

The `CommandRegistry` class manages command registration and retrieval in Yalla CLI applications.

## Class Definition

```php
namespace Yalla\Commands;

class CommandRegistry
{
    private array $commands = [];
}
```

## Methods

### register()

```php
public function register(Command $command): void
```

Registers a command with the registry.

#### Parameters

- `$command` (Command): The command instance to register

#### Example

```php
$registry = new CommandRegistry();
$registry->register(new DeployCommand());
$registry->register(new BackupCommand());
```

### get()

```php
public function get(string $name): ?Command
```

Retrieves a command by name.

#### Parameters

- `$name` (string): The command name

#### Returns

- `?Command`: The command instance or null if not found

#### Example

```php
$command = $registry->get('deploy');

if ($command) {
    $command->execute($input, $output);
} else {
    echo "Command not found";
}
```

### all()

```php
public function all(): array
```

Returns all registered commands.

#### Returns

- `array`: Array of Command instances indexed by name

#### Example

```php
$commands = $registry->all();

foreach ($commands as $name => $command) {
    echo $name . ': ' . $command->getDescription() . PHP_EOL;
}
```

### has()

```php
public function has(string $name): bool
```

Checks if a command is registered.

#### Parameters

- `$name` (string): The command name to check

#### Returns

- `bool`: True if command exists, false otherwise

#### Example

```php
if ($registry->has('deploy')) {
    $command = $registry->get('deploy');
    // Use command...
}
```

## Usage Examples

### Basic Usage

```php
use Yalla\Commands\CommandRegistry;
use App\Commands\DeployCommand;
use App\Commands\BackupCommand;
use App\Commands\MigrateCommand;

// Create registry
$registry = new CommandRegistry();

// Register commands
$registry->register(new DeployCommand());
$registry->register(new BackupCommand());
$registry->register(new MigrateCommand());

// Get a command
$command = $registry->get('deploy');

// Check if command exists
if ($registry->has('deploy')) {
    // Command exists
}

// Get all commands
$allCommands = $registry->all();
```

### Integration with Application

```php
class Application
{
    private CommandRegistry $registry;

    public function __construct()
    {
        $this->registry = new CommandRegistry();
        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands(): void
    {
        $this->registry->register(new HelpCommand($this->registry));
        $this->registry->register(new ListCommand($this->registry));
    }

    public function register(Command $command): self
    {
        $this->registry->register($command);
        return $this;
    }

    public function run(): int
    {
        $commandName = $this->getCommandName();

        if (!$this->registry->has($commandName)) {
            echo "Command '$commandName' not found.\n";
            return 1;
        }

        $command = $this->registry->get($commandName);
        return $command->execute($input, $output);
    }
}
```

### Building a Help Command

```php
class HelpCommand extends Command
{
    private CommandRegistry $registry;

    public function __construct(CommandRegistry $registry)
    {
        $this->registry = $registry;
        $this->name = 'help';
        $this->description = 'Show help for a command';

        $this->addArgument('command', 'Command to show help for', false);
    }

    public function execute(array $input, Output $output): int
    {
        $commandName = $this->getArgument($input, 'command');

        if ($commandName) {
            // Show help for specific command
            if (!$this->registry->has($commandName)) {
                $output->error("Command '$commandName' not found");
                return 1;
            }

            $command = $this->registry->get($commandName);
            $this->showCommandHelp($command, $output);
        } else {
            // Show general help
            $this->showGeneralHelp($output);
        }

        return 0;
    }

    private function showCommandHelp(Command $command, Output $output): void
    {
        $output->section($command->getName());
        $output->writeln($command->getDescription());

        // Show arguments
        if ($arguments = $command->getArguments()) {
            $output->writeln('');
            $output->bold('Arguments:');
            foreach ($arguments as $arg) {
                $required = $arg['required'] ? ' (required)' : ' (optional)';
                $output->writeln("  {$arg['name']}{$required}");
                $output->dim("    {$arg['description']}");
            }
        }

        // Show options
        if ($options = $command->getOptions()) {
            $output->writeln('');
            $output->bold('Options:');
            foreach ($options as $opt) {
                $shortcut = $opt['shortcut'] ? "-{$opt['shortcut']}, " : '    ';
                $output->writeln("  {$shortcut}--{$opt['name']}");
                $output->dim("    {$opt['description']}");
            }
        }
    }

    private function showGeneralHelp(Output $output): void
    {
        $output->section('Available Commands');

        $commands = $this->registry->all();
        $maxLength = max(array_map('strlen', array_keys($commands)));

        foreach ($commands as $name => $command) {
            $padding = str_repeat(' ', $maxLength - strlen($name) + 2);
            $output->writeln("  {$name}{$padding}{$command->getDescription()}");
        }
    }
}
```

### Building a List Command

```php
class ListCommand extends Command
{
    private CommandRegistry $registry;

    public function __construct(CommandRegistry $registry)
    {
        $this->registry = $registry;
        $this->name = 'list';
        $this->description = 'List all available commands';
    }

    public function execute(array $input, Output $output): int
    {
        $commands = $this->registry->all();

        if (empty($commands)) {
            $output->warning('No commands registered');
            return 0;
        }

        // Group commands by namespace
        $grouped = $this->groupCommands($commands);

        foreach ($grouped as $namespace => $cmds) {
            if ($namespace) {
                $output->section($namespace);
            }

            $rows = [];
            foreach ($cmds as $name => $command) {
                $rows[] = [
                    $output->color($name, Output::GREEN),
                    $command->getDescription()
                ];
            }

            $output->table(['Command', 'Description'], $rows);
            $output->writeln('');
        }

        return 0;
    }

    private function groupCommands(array $commands): array
    {
        $grouped = [];

        foreach ($commands as $name => $command) {
            if (strpos($name, ':') !== false) {
                [$namespace] = explode(':', $name, 2);
                $grouped[$namespace][$name] = $command;
            } else {
                $grouped[''][$name] = $command;
            }
        }

        return $grouped;
    }
}
```

## Auto-Registration Pattern

```php
class CommandLoader
{
    public static function loadFromDirectory(
        CommandRegistry $registry,
        string $directory,
        string $namespace = 'App\\Commands'
    ): void {
        $files = glob($directory . '/*Command.php');

        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = $namespace . '\\' . $className;

            if (class_exists($fullClassName)) {
                $reflection = new ReflectionClass($fullClassName);

                if (!$reflection->isAbstract() &&
                    $reflection->isSubclassOf(Command::class)) {
                    $registry->register(new $fullClassName());
                }
            }
        }
    }
}

// Usage
$registry = new CommandRegistry();
CommandLoader::loadFromDirectory($registry, __DIR__ . '/src/Commands');
```

## Performance Considerations

The CommandRegistry uses a hash map internally for O(1) command lookups:

```php
// Efficient lookup
$command = $registry->get('deploy');  // O(1)

// Checking existence
if ($registry->has('deploy')) {  // O(1)
    // ...
}

// Getting all commands
$all = $registry->all();  // O(1) - returns reference
```

## Thread Safety

The CommandRegistry is not thread-safe. If you need thread safety, implement synchronization:

```php
class ThreadSafeCommandRegistry extends CommandRegistry
{
    private $mutex;

    public function register(Command $command): void
    {
        $this->mutex->lock();
        try {
            parent::register($command);
        } finally {
            $this->mutex->unlock();
        }
    }
}
```

## Testing

```php
test('registry registers and retrieves commands', function () {
    $registry = new CommandRegistry();
    $command = new TestCommand();

    $registry->register($command);

    expect($registry->has('test'))->toBeTrue();
    expect($registry->get('test'))->toBe($command);
});

test('registry returns null for non-existent command', function () {
    $registry = new CommandRegistry();

    expect($registry->get('nonexistent'))->toBeNull();
    expect($registry->has('nonexistent'))->toBeFalse();
});

test('registry returns all registered commands', function () {
    $registry = new CommandRegistry();

    $command1 = new Command1();
    $command2 = new Command2();

    $registry->register($command1);
    $registry->register($command2);

    $all = $registry->all();

    expect($all)->toHaveCount(2);
    expect($all['command1'])->toBe($command1);
    expect($all['command2'])->toBe($command2);
});
```

## See Also

- [Command](./command.md) - Base command class
- [Application](./application.md) - Main application class