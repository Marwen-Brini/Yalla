# REPL (Read-Eval-Print-Loop)

Yalla includes a powerful interactive REPL for PHP development, testing, and debugging.

## Starting the REPL

```bash
# Basic usage
./cli repl

# With custom configuration
./cli repl --config=repl.config.php

# Without colors
./cli repl --no-colors

# Without history
./cli repl --no-history
```

## Basic Usage

### Evaluating Expressions

```php
> 2 + 2
4

> "Hello " . "World"
"Hello World"

> [1, 2, 3]
[1, 2, 3]

> range(1, 5)
[1, 2, 3, 4, 5]
```

### Working with Variables

```php
> $name = "Alice"
"Alice"

> $age = 30
30

> echo "Name: $name, Age: $age"
Name: Alice, Age: 30

> $user = ['name' => $name, 'age' => $age]
['name' => "Alice", 'age' => 30]
```

### Using PHP Functions

```php
> strlen("Hello World")
11

> array_map(fn($x) => $x * 2, [1, 2, 3])
[2, 4, 6]

> json_encode(['status' => 'success'])
'{"status":"success"}'

> date('Y-m-d H:i:s')
"2025-01-14 10:30:45"
```

## REPL Commands

All REPL commands start with a colon (`:`):

### Basic Commands

- `:help` - Show all available commands
- `:exit` or `:quit` - Exit the REPL
- `:clear` - Clear the screen
- `:vars` - Show all defined variables
- `:imports` - Show imported classes
- `:history` - Show command history

### Display Modes

Switch between different output formats:

```php
> :mode
Current display mode: compact

> :mode verbose
Display mode changed to: verbose

> :mode json
Display mode changed to: json

> :mode dump
Display mode changed to: dump

> :mode compact
Display mode changed to: compact
```

## Display Modes Explained

### Compact Mode (Default)

Clean, colorized output for everyday use:

```php
> ['id' => 1, 'name' => 'Alice']
['id' => 1, 'name' => "Alice"]

> "Hello World"
"Hello World"
```

### Verbose Mode

Detailed information about values:

```php
> :mode verbose
> $user = new stdClass
═══ Object Details ═══
Class: stdClass
Properties: (none)
Public Methods: (none)

> ['a' => 1, 'b' => 2]
═══ Array Details ═══
Type: Associative Array
Count: 2 items
Keys: a, b
Values:
  [a] => 1
  [b] => 2
```

### JSON Mode

Perfect for API data and configuration:

```php
> :mode json
> ['status' => 'success', 'data' => ['id' => 1]]
{
  "status": "success",
  "data": {
    "id": 1
  }
}
```

### Dump Mode

Traditional PHP var_dump output:

```php
> :mode dump
> "test"
string(4) "test"

> [1, 2, 3]
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
```

## Working with Classes

### Creating Objects

```php
> class User {
    public $name;
    public $email;
    public function __construct($name, $email) {
        $this->name = $name;
        $this->email = $email;
    }
}

> $user = new User("Alice", "alice@example.com")
User {
  name: "Alice"
  email: "alice@example.com"
}
```

### Using Namespaced Classes

```php
> use DateTime
> $date = new DateTime()
DateTime object

> use DateInterval
> $interval = new DateInterval('P1D')
DateInterval object
```

## Configuration

### Creating a Configuration File

Create `repl.config.php`:

```php
<?php

return [
    // Class shortcuts
    'shortcuts' => [
        'User' => '\App\Models\User',
        'DB' => '\App\Database\DB',
        'Carbon' => '\Carbon\Carbon',
    ],

    // Auto-imports
    'imports' => [
        ['class' => '\DateTime', 'alias' => 'DateTime'],
        ['class' => '\DateTimeZone', 'alias' => 'DateTimeZone'],
    ],

    // Display settings
    'display' => [
        'prompt' => '>>> ',           // Custom prompt
        'performance' => true,        // Show execution time
        'mode' => 'compact',         // Default display mode
        'colors' => true,            // Enable colors
        'max_depth' => 3,            // Max nesting depth
        'truncate' => 1000,          // Truncate long strings
    ],

    // History settings
    'history' => [
        'enabled' => true,
        'file' => $_ENV['HOME'] . '/.yalla_history',
        'max_entries' => 1000,
        'ignore_duplicates' => true,
    ],

    // Autocomplete settings
    'autocomplete' => [
        'enabled' => true,
        'max_suggestions' => 10,
    ],
];
```

### Using the Configuration

```bash
./cli repl --config=repl.config.php
```

Now you can use shortcuts:

```php
> $user = new User  // Expands to \App\Models\User
> Carbon::now()     // Expands to \Carbon\Carbon::now()
```

## Creating REPL Extensions

### Basic Extension

```php
<?php

use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;

class DatabaseExtension implements ReplExtension
{
    private $connection;

    public function register(ReplContext $context): void
    {
        // Add custom commands
        $context->addCommand('db:tables', function($args, $output) {
            $tables = $this->getTables();
            $output->table(['Table Name'], array_map(fn($t) => [$t], $tables));
        });

        $context->addCommand('db:query', function($args, $output) {
            $result = $this->query($args);
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
        });

        // Add shortcuts
        $context->addShortcut('DB', '\App\Database');

        // Add custom variables
        $context->setVariable('db', $this->connection);
    }

    public function boot(): void
    {
        // Initialize database connection
        $this->connection = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
    }

    public function getName(): string
    {
        return 'Database Extension';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Adds database interaction to the REPL';
    }

    private function getTables(): array
    {
        // Implementation...
        return ['users', 'posts', 'comments'];
    }

    private function query(string $sql)
    {
        // Implementation...
        return [];
    }
}
```

### Registering Extensions

```php
use Yalla\Repl\ReplCommand;

$replCommand = new ReplCommand();
$replCommand->registerExtension(new DatabaseExtension());
```

## Advanced Features

### Custom Display Formatters

```php
// In your extension
$context->addFormatter('App\Models\User', function($user, $output) {
    $output->writeln("User: {$user->name} ({$user->email})");
    $output->writeln("Created: {$user->created_at}");
});
```

### Middleware

Process input and output:

```php
// Input middleware
$context->addMiddleware('input', function($input) {
    // Expand shortcuts
    return str_replace('$$', '$this->', $input);
});

// Output middleware
$context->addMiddleware('output', function($output) {
    // Format output
    if (is_array($output) && count($output) > 10) {
        return array_slice($output, 0, 10) + ['...' => 'truncated'];
    }
    return $output;
});
```

### Custom Evaluators

```php
$context->addEvaluator(function($input) {
    // Handle SQL queries
    if (str_starts_with($input, 'SQL:')) {
        $sql = substr($input, 4);
        return $this->database->query($sql);
    }
    return null; // Let default evaluator handle it
});
```

## Practical Examples

### Working with Databases

```php
> $pdo = new PDO('sqlite::memory:')
> $pdo->exec('CREATE TABLE users (id INTEGER, name TEXT)')
> $pdo->exec("INSERT INTO users VALUES (1, 'Alice')")
> $stmt = $pdo->query('SELECT * FROM users')
> $stmt->fetchAll()
[
  ['id' => 1, 'name' => 'Alice']
]
```

### Testing Code Snippets

```php
> function fibonacci($n) {
    if ($n <= 1) return $n;
    return fibonacci($n - 1) + fibonacci($n - 2);
  }

> fibonacci(10)
55

> array_map('fibonacci', range(0, 10))
[0, 1, 1, 2, 3, 5, 8, 13, 21, 34, 55]
```

### Working with JSON

```php
> $json = '{"name": "Alice", "age": 30}'
> $data = json_decode($json, true)
['name' => 'Alice', 'age' => 30]

> $data['age'] += 1
> json_encode($data)
'{"name":"Alice","age":31}'
```

### File Operations

```php
> file_put_contents('test.txt', 'Hello World')
11

> file_get_contents('test.txt')
"Hello World"

> unlink('test.txt')
true
```

## Tips and Tricks

### 1. Use Tab Completion

The REPL supports tab completion for:
- PHP built-in functions
- Defined variables (starting with `$`)
- Class shortcuts
- REPL commands (starting with `:`)

### 2. Multi-line Input

For multi-line code, the REPL automatically detects incomplete statements:

```php
> function hello($name) {
    return "Hello, $name!";
  }
```

### 3. Command History

Use up/down arrows to navigate through command history.

### 4. Quick Testing

Test your command logic quickly:

```php
> $output = new Yalla\Output\Output
> $output->success('It works!')
It works!

> $output->table(['Name', 'Age'], [['Alice', 30], ['Bob', 25]])
│ Name  │ Age │
├───────┼─────┤
│ Alice │ 30  │
│ Bob   │ 25  │
```

### 5. Performance Testing

With performance display enabled:

```php
> range(1, 1000000)
[Execution time: 0.0234s] [Memory: 32.5 MB]
```

## Troubleshooting

### Common Issues

**Issue**: Colors not displaying
**Solution**: Check terminal support or use `--no-colors`

**Issue**: History not saving
**Solution**: Check write permissions for history file

**Issue**: Autocomplete not working
**Solution**: Ensure readline extension is installed

**Issue**: Class not found
**Solution**: Use fully qualified names or configure shortcuts

## Security Considerations

The REPL executes PHP code with full access to your system. Be careful when:

1. Running code from untrusted sources
2. Executing system commands
3. Working with sensitive data
4. Using in production environments

Always use the REPL in a controlled environment and never expose it to untrusted users.