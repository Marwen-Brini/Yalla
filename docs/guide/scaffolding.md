# Command Scaffolding

Yalla includes a powerful command generator that helps you quickly create new command classes with the proper structure.

## Using the Generator

### Basic Usage

```bash
# Create a new command
./cli create:command deploy
```

This creates a new file `src/Commands/DeployCommand.php` with a complete command template.

### Command Options

```bash
# Specify custom class name
./cli create:command deploy --class=DeployApplicationCommand

# Generate in a custom directory
./cli create:command deploy --dir=src/Commands/Deployment

# Force overwrite existing file
./cli create:command deploy --force

# Combine options
./cli create:command api:sync --class=ApiSyncCommand --dir=src/Api/Commands
```

## Generated Command Structure

The generator creates a fully functional command template:

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

## Naming Conventions

### Command Names

The generator intelligently handles various naming formats:

```bash
# Simple name
./cli create:command deploy
# Creates: DeployCommand

# Namespaced command
./cli create:command db:migrate
# Creates: DbMigrateCommand

# Hyphenated name
./cli create:command clear-cache
# Creates: ClearCacheCommand

# Already has Command suffix
./cli create:command TestCommand
# Creates: TestCommand (doesn't duplicate suffix)
```

### Directory Structure

The generator automatically determines the namespace based on your project structure:

```bash
# Default location
./cli create:command deploy
# Creates: src/Commands/DeployCommand.php
# Namespace: App\Commands

# Custom directory
./cli create:command deploy --dir=src/Console/Commands
# Creates: src/Console/Commands/DeployCommand.php
# Namespace: App\Console\Commands

# Nested directories
./cli create:command deploy --dir=src/Commands/Deployment
# Creates: src/Commands/Deployment/DeployCommand.php
# Namespace: App\Commands\Deployment
```

## Customizing Generated Commands

### Step 1: Generate the Base Command

```bash
./cli create:command process:data
```

### Step 2: Add Arguments

Edit the generated file to add arguments:

```php
public function __construct()
{
    $this->name = 'process:data';
    $this->description = 'Process data from various sources';

    // Add arguments
    $this->addArgument('source', 'Data source file or URL', true);
    $this->addArgument('output', 'Output file path', false);
}
```

### Step 3: Add Options

```php
public function __construct()
{
    // ... existing code ...

    // Add options
    $this->addOption('format', 'f', 'Output format (json|csv|xml)', 'json');
    $this->addOption('validate', 'v', 'Validate data before processing', false);
    $this->addOption('chunk-size', 'c', 'Process in chunks of N records', 1000);
    $this->addOption('dry-run', null, 'Simulate without saving', false);
}
```

### Step 4: Implement Logic

```php
public function execute(array $input, Output $output): int
{
    // Get arguments
    $source = $this->getArgument($input, 'source');
    $outputFile = $this->getArgument($input, 'output', 'output.json');

    // Get options
    $format = $this->getOption($input, 'format', 'json');
    $validate = $this->getOption($input, 'validate', false);
    $chunkSize = (int) $this->getOption($input, 'chunk-size', 1000);
    $dryRun = $this->getOption($input, 'dry-run', false);

    // Validation
    if (!file_exists($source)) {
        $output->error("Source file not found: $source");
        return 1;
    }

    // Process data
    $output->info("Processing data from: $source");

    if ($validate) {
        $output->writeln('Validating data...');
        // Validation logic
    }

    // Show progress
    $totalRecords = 1000; // Get actual count
    $processed = 0;

    while ($processed < $totalRecords) {
        $output->progressBar($processed, $totalRecords);
        // Process chunk
        $processed += $chunkSize;
    }

    if (!$dryRun) {
        $output->success("Data saved to: $outputFile");
    } else {
        $output->warning('DRY RUN - No data was saved');
    }

    return 0;
}
```

## Advanced Scaffolding

### Custom Command Templates

Create your own command template by extending the generator:

```php
<?php

use Yalla\Commands\CreateCommandCommand;

class CreateApiCommandCommand extends CreateCommandCommand
{
    protected function getCommandTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace $namespace;

use Yalla\Commands\Command;
use Yalla\Output\Output;
use GuzzleHttp\Client;

class $className extends Command
{
    private Client \$httpClient;

    public function __construct()
    {
        \$this->name = strtolower(str_replace('Command', '', '$className'));
        \$this->description = 'API command for ' . \$this->name;

        \$this->addOption('api-key', 'k', 'API key for authentication', null);
        \$this->addOption('endpoint', 'e', 'API endpoint URL', 'https://api.example.com');
        \$this->addOption('timeout', 't', 'Request timeout in seconds', 30);

        \$this->httpClient = new Client();
    }

    public function execute(array \$input, Output \$output): int
    {
        \$apiKey = \$this->getOption(\$input, 'api-key');
        \$endpoint = \$this->getOption(\$input, 'endpoint');

        if (!\$apiKey) {
            \$output->error('API key is required. Use --api-key option.');
            return 1;
        }

        try {
            \$response = \$this->httpClient->get(\$endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . \$apiKey,
                ],
                'timeout' => \$this->getOption(\$input, 'timeout', 30),
            ]);

            \$data = json_decode(\$response->getBody()->getContents(), true);
            \$output->writeln(json_encode(\$data, JSON_PRETTY_PRINT));

            return 0;
        } catch (\Exception \$e) {
            \$output->error('API request failed: ' . \$e->getMessage());
            return 1;
        }
    }
}
PHP;
    }
}
```

### Batch Command Generation

Create a script to generate multiple commands:

```php
#!/usr/bin/env php
<?php

$commands = [
    'user:create' => 'Create a new user',
    'user:update' => 'Update user information',
    'user:delete' => 'Delete a user',
    'user:list' => 'List all users',
    'cache:clear' => 'Clear application cache',
    'cache:warm' => 'Warm up cache',
    'db:backup' => 'Backup database',
    'db:restore' => 'Restore database from backup',
];

foreach ($commands as $name => $description) {
    $className = str_replace([':', '-'], '', ucwords($name, ':-')) . 'Command';

    echo "Creating $className...\n";

    exec("./cli create:command $name --class=$className");

    // Optionally update the description
    $file = "src/Commands/$className.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace(
            "'Description of your command'",
            "'$description'",
            $content
        );
        file_put_contents($file, $content);
    }
}

echo "Generated " . count($commands) . " commands successfully!\n";
```

## Project Organization

### Recommended Structure

```
src/
└── Commands/
    ├── User/
    │   ├── CreateUserCommand.php
    │   ├── UpdateUserCommand.php
    │   ├── DeleteUserCommand.php
    │   └── ListUsersCommand.php
    ├── Cache/
    │   ├── ClearCacheCommand.php
    │   └── WarmCacheCommand.php
    ├── Database/
    │   ├── BackupCommand.php
    │   ├── RestoreCommand.php
    │   └── MigrateCommand.php
    └── System/
        ├── InfoCommand.php
        └── HealthCheckCommand.php
```

### Generating Organized Commands

```bash
# User commands
./cli create:command user:create --dir=src/Commands/User
./cli create:command user:update --dir=src/Commands/User
./cli create:command user:delete --dir=src/Commands/User

# Cache commands
./cli create:command cache:clear --dir=src/Commands/Cache
./cli create:command cache:warm --dir=src/Commands/Cache

# Database commands
./cli create:command db:backup --dir=src/Commands/Database
./cli create:command db:migrate --dir=src/Commands/Database
```

## Auto-Registration

After generating commands, register them with your application:

### Manual Registration

```php
use Yalla\Application;

$app = new Application('My CLI', '1.0.0');

// Register individual commands
$app->register(new Commands\User\CreateUserCommand());
$app->register(new Commands\User\UpdateUserCommand());
$app->register(new Commands\Cache\ClearCacheCommand());
```

### Automatic Registration

Create a command loader:

```php
class CommandLoader
{
    public static function loadAll(Application $app, string $dir)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $class = self::getClassFromFile($file->getPathname());

                if ($class && is_subclass_of($class, Command::class)) {
                    $app->register(new $class());
                }
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
CommandLoader::loadAll($app, __DIR__ . '/src/Commands');
```

## Best Practices

### 1. Use Descriptive Names

```bash
# Good
./cli create:command database:backup
./cli create:command user:import
./cli create:command cache:clear

# Poor
./cli create:command backup
./cli create:command import
./cli create:command clear
```

### 2. Organize by Domain

```bash
# Group related commands
./cli create:command api:fetch --dir=src/Commands/Api
./cli create:command api:sync --dir=src/Commands/Api
./cli create:command api:validate --dir=src/Commands/Api
```

### 3. Follow Naming Conventions

- Use lowercase with colons for command names: `db:migrate`
- Use PascalCase for class names: `DbMigrateCommand`
- Use descriptive names that indicate action: `create`, `update`, `delete`, `list`

### 4. Add Meaningful Descriptions

Always update the generated description to be helpful:

```php
// Instead of
$this->description = 'Description of your command';

// Use
$this->description = 'Backup database to specified location with optional compression';
```

### 5. Document Arguments and Options

```php
$this->addArgument(
    'database',
    'Name of the database to backup (leave empty for default)',
    false
);

$this->addOption(
    'compress',
    'c',
    'Compression type: gzip, bzip2, or none (default: gzip)',
    'gzip'
);
```

## Extending the Generator

You can extend the create:command functionality:

```php
class MyCreateCommand extends CreateCommandCommand
{
    public function __construct()
    {
        parent::__construct();

        // Add new options
        $this->addOption(
            'with-tests',
            't',
            'Generate test file for the command',
            false
        );
    }

    public function execute(array $input, Output $output): int
    {
        // Call parent execution
        $result = parent::execute($input, $output);

        if ($result === 0 && $this->getOption($input, 'with-tests')) {
            $this->generateTestFile($input, $output);
        }

        return $result;
    }

    private function generateTestFile(array $input, Output $output): void
    {
        // Generate corresponding test file
        $className = $this->getOption($input, 'class')
            ?? $this->generateClassName($this->getArgument($input, 'name'));

        $testContent = $this->getTestTemplate($className);
        $testFile = "tests/Commands/{$className}Test.php";

        file_put_contents($testFile, $testContent);
        $output->success("Test file created: $testFile");
    }
}
```