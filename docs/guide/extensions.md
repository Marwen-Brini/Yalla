# Extensions

Yalla provides a powerful extension system that allows you to add custom functionality to both the CLI application and the REPL.

## Creating Extensions

### Basic Extension Structure

```php
<?php

namespace App\Extensions;

use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;

class MyExtension implements ReplExtension
{
    public function register(ReplContext $context): void
    {
        // Register commands, shortcuts, formatters, etc.
    }

    public function boot(): void
    {
        // Initialize the extension
    }

    public function getName(): string
    {
        return 'My Extension';
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

## REPL Extensions

### Adding Custom Commands

```php
public function register(ReplContext $context): void
{
    // Simple command
    $context->addCommand('time', function($args, $output) {
        $output->info('Current time: ' . date('Y-m-d H:i:s'));
    });

    // Command with arguments
    $context->addCommand('calc', function($args, $output) {
        if (empty($args)) {
            $output->error('Usage: :calc <expression>');
            return;
        }

        try {
            $result = eval('return ' . $args . ';');
            $output->success("Result: $result");
        } catch (\Exception $e) {
            $output->error('Invalid expression');
        }
    });

    // Command with complex logic
    $context->addCommand('db:tables', [$this, 'showTables']);
}

public function showTables($args, $output): void
{
    $tables = $this->getDatabaseTables();

    $output->table(
        ['Table Name', 'Rows', 'Size'],
        $tables
    );
}
```

### Adding Shortcuts

Shortcuts allow you to use short aliases for frequently used classes:

```php
public function register(ReplContext $context): void
{
    // Add class shortcuts
    $context->addShortcut('Carbon', '\Carbon\Carbon');
    $context->addShortcut('Str', '\Illuminate\Support\Str');
    $context->addShortcut('User', '\App\Models\User');
    $context->addShortcut('DB', '\App\Database\DB');
}
```

Now in the REPL:

```php
> Carbon::now()  // Expands to \Carbon\Carbon::now()
> Str::random(10)  // Expands to \Illuminate\Support\Str::random(10)
```

### Adding Custom Formatters

Format specific types of objects for better display:

```php
public function register(ReplContext $context): void
{
    // Format DateTime objects
    $context->addFormatter(\DateTime::class, function($date, $output) {
        $output->writeln('DateTime: ' . $date->format('Y-m-d H:i:s'));
        $output->writeln('Timezone: ' . $date->getTimezone()->getName());
        $output->writeln('Timestamp: ' . $date->getTimestamp());
    });

    // Format custom model objects
    $context->addFormatter('App\Models\User', function($user, $output) {
        $output->section('User Details');
        $output->writeln('ID: ' . $user->id);
        $output->writeln('Name: ' . $user->name);
        $output->writeln('Email: ' . $user->email);
        $output->writeln('Created: ' . $user->created_at);
    });

    // Format collections
    $context->addFormatter('Illuminate\Support\Collection', function($collection, $output) {
        if ($collection->isEmpty()) {
            $output->dim('Empty collection');
            return;
        }

        $output->info('Collection (' . $collection->count() . ' items)');
        $output->writeln($collection->toJson(JSON_PRETTY_PRINT));
    });
}
```

### Adding Middleware

Process input and output through middleware:

```php
public function register(ReplContext $context): void
{
    // Input middleware - process before evaluation
    $context->addMiddleware('input', function($input) {
        // Expand custom syntax
        $input = str_replace('$$', '$this->', $input);

        // Add automatic use statements
        if (preg_match('/^(\w+)::/', $input, $matches)) {
            $class = $matches[1];
            if (!class_exists($class)) {
                $input = "use $class; $input";
            }
        }

        return $input;
    });

    // Output middleware - process after evaluation
    $context->addMiddleware('output', function($output) {
        // Limit array size
        if (is_array($output) && count($output) > 100) {
            return array_slice($output, 0, 100) + ['...' => 'truncated'];
        }

        // Format numbers
        if (is_float($output)) {
            return number_format($output, 2);
        }

        return $output;
    });
}
```

## Practical Extension Examples

### Database Extension

```php
<?php

namespace App\Extensions;

use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;
use PDO;

class DatabaseExtension implements ReplExtension
{
    private ?PDO $connection = null;

    public function register(ReplContext $context): void
    {
        // Add database commands
        $context->addCommand('db:connect', [$this, 'connect']);
        $context->addCommand('db:query', [$this, 'query']);
        $context->addCommand('db:tables', [$this, 'showTables']);
        $context->addCommand('db:describe', [$this, 'describeTable']);

        // Add shortcuts
        $context->addShortcut('DB', self::class);

        // Make connection available
        $context->setVariable('db', $this);
    }

    public function boot(): void
    {
        // Auto-connect if credentials are available
        if (getenv('DB_HOST')) {
            $this->connect();
        }
    }

    public function connect($args = null, $output = null): void
    {
        try {
            $dsn = $args ?: 'mysql:host=localhost;dbname=test';
            $this->connection = new PDO($dsn, 'user', 'pass');

            if ($output) {
                $output->success('Connected to database');
            }
        } catch (\Exception $e) {
            if ($output) {
                $output->error('Connection failed: ' . $e->getMessage());
            }
        }
    }

    public function query($sql, $output = null)
    {
        if (!$this->connection) {
            if ($output) {
                $output->error('Not connected to database');
            }
            return null;
        }

        try {
            $stmt = $this->connection->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($output) {
                if (empty($result)) {
                    $output->info('Query executed, no results');
                } else {
                    $headers = array_keys($result[0]);
                    $rows = array_map('array_values', $result);
                    $output->table($headers, $rows);
                }
            }

            return $result;
        } catch (\Exception $e) {
            if ($output) {
                $output->error('Query failed: ' . $e->getMessage());
            }
            return null;
        }
    }

    public function showTables($args, $output): void
    {
        $tables = $this->query("SHOW TABLES");

        if ($tables) {
            $output->table(['Table'], array_map('array_values', $tables));
        }
    }

    public function describeTable($table, $output): void
    {
        $columns = $this->query("DESCRIBE $table");

        if ($columns) {
            $output->table(
                ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                array_map('array_values', $columns)
            );
        }
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
        return 'Adds database interaction capabilities to the REPL';
    }
}
```

Usage in REPL:

```php
> :db:connect mysql:host=localhost;dbname=myapp
Connected to database

> :db:tables
│ Table     │
├───────────┤
│ users     │
│ posts     │
│ comments  │

> :db:query SELECT * FROM users LIMIT 2
│ id │ name  │ email           │
├────┼───────┼─────────────────┤
│ 1  │ Alice │ alice@test.com  │
│ 2  │ Bob   │ bob@test.com    │

> $db->query("SELECT COUNT(*) as count FROM users")
[['count' => 42]]
```

### HTTP Client Extension

```php
<?php

namespace App\Extensions;

use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;
use GuzzleHttp\Client;

class HttpExtension implements ReplExtension
{
    private Client $client;

    public function register(ReplContext $context): void
    {
        $this->client = new Client();

        // Add HTTP commands
        $context->addCommand('http:get', [$this, 'get']);
        $context->addCommand('http:post', [$this, 'post']);
        $context->addCommand('http:api', [$this, 'api']);

        // Add shortcuts
        $context->addShortcut('Http', self::class);

        // Make client available
        $context->setVariable('http', $this->client);
    }

    public function get($url, $output): void
    {
        try {
            $response = $this->client->get($url);
            $body = $response->getBody()->getContents();

            $output->info('Status: ' . $response->getStatusCode());

            if ($this->isJson($body)) {
                $data = json_decode($body, true);
                $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
            } else {
                $output->writeln($body);
            }
        } catch (\Exception $e) {
            $output->error('Request failed: ' . $e->getMessage());
        }
    }

    public function post($args, $output): void
    {
        [$url, $data] = explode(' ', $args, 2);

        try {
            $response = $this->client->post($url, [
                'json' => json_decode($data, true)
            ]);

            $output->success('Posted successfully: ' . $response->getStatusCode());
        } catch (\Exception $e) {
            $output->error('Post failed: ' . $e->getMessage());
        }
    }

    public function api($endpoint, $output): void
    {
        $baseUrl = getenv('API_BASE_URL') ?: 'https://api.example.com';
        $this->get($baseUrl . $endpoint, $output);
    }

    private function isJson($string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    // ... other required methods
}
```

### File System Extension

```php
<?php

namespace App\Extensions;

use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;

class FileSystemExtension implements ReplExtension
{
    public function register(ReplContext $context): void
    {
        // File commands
        $context->addCommand('ls', [$this, 'listFiles']);
        $context->addCommand('cd', [$this, 'changeDirectory']);
        $context->addCommand('pwd', [$this, 'printWorkingDirectory']);
        $context->addCommand('cat', [$this, 'readFile']);
        $context->addCommand('find', [$this, 'findFiles']);

        // Shortcuts for common operations
        $context->addShortcut('File', self::class);
    }

    public function listFiles($path = '.', $output): void
    {
        $files = scandir($path);
        $items = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $fullPath = $path . '/' . $file;
            $type = is_dir($fullPath) ? 'DIR' : 'FILE';
            $size = is_file($fullPath) ? filesize($fullPath) : '-';
            $modified = date('Y-m-d H:i', filemtime($fullPath));

            $items[] = [$file, $type, $size, $modified];
        }

        $output->table(['Name', 'Type', 'Size', 'Modified'], $items);
    }

    public function changeDirectory($path, $output): void
    {
        if (chdir($path)) {
            $output->success('Changed to: ' . getcwd());
        } else {
            $output->error('Cannot change to: ' . $path);
        }
    }

    public function printWorkingDirectory($args, $output): void
    {
        $output->info(getcwd());
    }

    public function readFile($file, $output): void
    {
        if (!file_exists($file)) {
            $output->error('File not found: ' . $file);
            return;
        }

        $content = file_get_contents($file);
        $lines = explode("\n", $content);

        foreach ($lines as $i => $line) {
            $output->writeln(sprintf("%4d | %s", $i + 1, $line));
        }
    }

    public function findFiles($pattern, $output): void
    {
        $files = glob($pattern, GLOB_BRACE);

        if (empty($files)) {
            $output->info('No files found matching: ' . $pattern);
            return;
        }

        foreach ($files as $file) {
            $output->writeln($file);
        }
    }

    // ... other required methods
}
```

## Loading Extensions

### Manual Registration

```php
use Yalla\Repl\ReplCommand;
use App\Extensions\DatabaseExtension;
use App\Extensions\HttpExtension;

$repl = new ReplCommand();
$repl->registerExtension(new DatabaseExtension());
$repl->registerExtension(new HttpExtension());
```

### Auto-loading Extensions

```php
class ExtensionLoader
{
    public static function loadAll(ReplCommand $repl, string $directory)
    {
        $files = glob($directory . '/*Extension.php');

        foreach ($files as $file) {
            $class = self::getClassFromFile($file);

            if ($class && is_subclass_of($class, ReplExtension::class)) {
                $repl->registerExtension(new $class());
            }
        }
    }

    private static function getClassFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);

        if (preg_match('/namespace\s+(.+?);/', $contents, $nsMatch) &&
            preg_match('/class\s+(\w+)/', $contents, $classMatch)) {
            return $nsMatch[1] . '\\' . $classMatch[1];
        }

        return null;
    }
}

// Usage
$repl = new ReplCommand();
ExtensionLoader::loadAll($repl, __DIR__ . '/src/Extensions');
```

### Configuration-based Loading

In your `repl.config.php`:

```php
return [
    'extensions' => [
        \App\Extensions\DatabaseExtension::class,
        \App\Extensions\HttpExtension::class,
        \App\Extensions\FileSystemExtension::class,
    ],

    // ... other config
];
```

Then in your application:

```php
$config = require 'repl.config.php';
$repl = new ReplCommand();

foreach ($config['extensions'] as $extensionClass) {
    $repl->registerExtension(new $extensionClass());
}
```

## Best Practices

### 1. Namespace Your Commands

Prefix extension commands to avoid conflicts:

```php
// Good
$context->addCommand('db:query', ...);
$context->addCommand('http:get', ...);

// Poor
$context->addCommand('query', ...);
$context->addCommand('get', ...);
```

### 2. Handle Errors Gracefully

```php
public function connect($dsn, $output): void
{
    try {
        $this->connection = new PDO($dsn);
        $output->success('Connected');
    } catch (\Exception $e) {
        $output->error('Failed: ' . $e->getMessage());
        // Don't throw - let REPL continue
    }
}
```

### 3. Provide Help

```php
$context->addCommand('myext:help', function($args, $output) {
    $output->section('MyExtension Commands');
    $output->writeln(':myext:connect <dsn> - Connect to service');
    $output->writeln(':myext:status       - Show status');
    $output->writeln(':myext:clear        - Clear data');
});
```

### 4. Use Lazy Loading

```php
public function boot(): void
{
    // Don't initialize heavy resources here
}

private function getConnection()
{
    if (!$this->connection) {
        $this->connection = new HeavyResource();
    }
    return $this->connection;
}
```

### 5. Make Extensions Configurable

```php
class MyExtension implements ReplExtension
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'timeout' => 30,
            'retries' => 3,
        ], $config);
    }
}
```

## Distributing Extensions

### As Composer Packages

Create `composer.json`:

```json
{
    "name": "vendor/yalla-extension-database",
    "description": "Database extension for Yalla REPL",
    "type": "library",
    "require": {
        "marwen-brini/yalla": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\YallaExtensions\\": "src/"
        }
    }
}
```

### Installation Instructions

```bash
# Install extension
composer require vendor/yalla-extension-database

# Register in your REPL
```

```php
use Vendor\YallaExtensions\DatabaseExtension;

$repl->registerExtension(new DatabaseExtension());
```