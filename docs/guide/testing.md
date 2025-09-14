# Testing Commands

Yalla is fully tested with 100% code coverage using Pest PHP. This guide will help you write tests for your custom commands.

## Setting Up Tests

### Install Pest

```bash
composer require --dev pestphp/pest
```

### Initialize Pest

```bash
./vendor/bin/pest --init
```

## Writing Command Tests

### Basic Command Test

```php
<?php

use Yalla\Application;
use Yalla\Output\Output;
use App\Commands\GreetCommand;

test('greet command outputs greeting', function () {
    // Create command
    $command = new GreetCommand();

    // Create mock output
    $output = Mockery::mock(Output::class);
    $output->shouldReceive('success')
        ->once()
        ->with('Hello, World!');

    // Create input
    $input = [
        'command' => 'greet',
        'arguments' => ['World'],
        'options' => []
    ];

    // Execute command
    $result = $command->execute($input, $output);

    // Assert success
    expect($result)->toBe(0);
});
```

### Testing with Options

```php
test('greet command with yell option', function () {
    $command = new GreetCommand();

    $output = Mockery::mock(Output::class);
    $output->shouldReceive('success')
        ->once()
        ->with('HELLO, WORLD!');

    $input = [
        'command' => 'greet',
        'arguments' => ['World'],
        'options' => ['yell' => true]
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(0);
});
```

## Testing Output

### Capturing Output

```php
test('command outputs table', function () {
    $command = new ListCommand();

    $output = Mockery::mock(Output::class);

    // Expect table method to be called
    $output->shouldReceive('table')
        ->once()
        ->with(
            ['ID', 'Name', 'Status'],
            Mockery::type('array')
        );

    $input = [
        'command' => 'list',
        'arguments' => [],
        'options' => []
    ];

    $command->execute($input, $output);
});
```

### Testing Multiple Output Calls

```php
test('command shows progress', function () {
    $command = new ProcessCommand();

    $output = Mockery::mock(Output::class);

    // Expect multiple calls
    $output->shouldReceive('info')
        ->once()
        ->with('Starting process...');

    $output->shouldReceive('progressBar')
        ->times(100)
        ->with(Mockery::type('int'), 100);

    $output->shouldReceive('success')
        ->once()
        ->with('Process completed!');

    $input = [
        'command' => 'process',
        'arguments' => ['data.csv'],
        'options' => []
    ];

    $command->execute($input, $output);
});
```

## Testing Input Validation

### Testing Required Arguments

```php
test('command fails without required argument', function () {
    $command = new DeployCommand();

    $output = Mockery::mock(Output::class);
    $output->shouldReceive('error')
        ->once()
        ->with('Environment argument is required');

    $input = [
        'command' => 'deploy',
        'arguments' => [],
        'options' => []
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(1);
});
```

### Testing Option Validation

```php
test('command validates option values', function () {
    $command = new BackupCommand();

    $output = Mockery::mock(Output::class);
    $output->shouldReceive('error')
        ->once()
        ->with('Invalid format: invalid');

    $input = [
        'command' => 'backup',
        'arguments' => ['database'],
        'options' => ['format' => 'invalid']
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(1);
});
```

## Testing File Operations

### Using Virtual File System

```php
use org\bovigo\vfs\vfsStream;

test('command creates output file', function () {
    // Set up virtual file system
    $root = vfsStream::setup('test');

    $command = new ExportCommand();
    $output = Mockery::mock(Output::class);

    $input = [
        'command' => 'export',
        'arguments' => [vfsStream::url('test/output.json')],
        'options' => []
    ];

    $command->execute($input, $output);

    // Assert file was created
    expect($root->hasChild('output.json'))->toBeTrue();

    // Check file contents
    $content = $root->getChild('output.json')->getContent();
    expect($content)->toContain('exported data');
});
```

### Testing File Reading

```php
test('command reads input file', function () {
    $root = vfsStream::setup('test');

    // Create input file
    $inputFile = vfsStream::newFile('input.txt')
        ->withContent('test data')
        ->at($root);

    $command = new ImportCommand();
    $output = Mockery::mock(Output::class);

    $output->shouldReceive('success')
        ->once();

    $input = [
        'command' => 'import',
        'arguments' => [vfsStream::url('test/input.txt')],
        'options' => []
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(0);
});
```

## Testing Application Integration

### Testing Command Registration

```php
test('application registers command', function () {
    $app = new Application('Test CLI', '1.0.0');
    $command = new CustomCommand();

    $app->register($command);

    // Create test input
    $_SERVER['argv'] = ['cli', 'custom'];

    // Capture output
    ob_start();
    $result = $app->run();
    $output = ob_get_clean();

    expect($result)->toBe(0);
    expect($output)->toContain('Custom command executed');
});
```

### Testing Command Discovery

```php
test('application lists all commands', function () {
    $app = new Application('Test CLI', '1.0.0');

    // Register multiple commands
    $app->register(new Command1());
    $app->register(new Command2());
    $app->register(new Command3());

    $_SERVER['argv'] = ['cli', 'list'];

    ob_start();
    $app->run();
    $output = ob_get_clean();

    expect($output)->toContain('command1');
    expect($output)->toContain('command2');
    expect($output)->toContain('command3');
});
```

## Testing REPL Commands

### Testing REPL Context

```php
use Yalla\Repl\ReplContext;
use Yalla\Repl\ReplConfig;

test('repl context stores variables', function () {
    $config = new ReplConfig();
    $context = new ReplContext($config);

    $context->setVariable('test', 'value');

    expect($context->getVariable('test'))->toBe('value');
    expect($context->hasVariable('test'))->toBeTrue();
});
```

### Testing REPL Extensions

```php
test('repl extension registers commands', function () {
    $config = new ReplConfig();
    $context = new ReplContext($config);

    $extension = new CustomExtension();
    $extension->register($context);

    expect($context->hasCommand('custom'))->toBeTrue();
});
```

## Advanced Testing Patterns

### Data Providers

```php
dataset('environments', [
    'production',
    'staging',
    'development'
]);

test('deploy command works with different environments', function ($env) {
    $command = new DeployCommand();
    $output = Mockery::mock(Output::class);

    $output->shouldReceive('success')
        ->once();

    $input = [
        'command' => 'deploy',
        'arguments' => [$env],
        'options' => []
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(0);
})->with('environments');
```

### Testing Exceptions

```php
test('command handles exceptions gracefully', function () {
    $command = new DatabaseCommand();
    $output = Mockery::mock(Output::class);

    $output->shouldReceive('error')
        ->once()
        ->with(Mockery::on(function ($message) {
            return str_contains($message, 'Database connection failed');
        }));

    // Mock database to throw exception
    $command->setDatabase(new class {
        public function connect() {
            throw new Exception('Connection refused');
        }
    });

    $input = [
        'command' => 'db:connect',
        'arguments' => [],
        'options' => []
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(1);
});
```

### Testing Interactive Input

```php
test('command prompts for confirmation', function () {
    $command = new DeleteCommand();
    $output = Mockery::mock(Output::class);

    // Mock user input
    stream_wrapper_unregister("php");
    stream_wrapper_register("php", "MockPhpStream");

    file_put_contents('php://stdin', "y\n");

    $output->shouldReceive('write')
        ->with('Are you sure? (y/n): ');

    $output->shouldReceive('success')
        ->once()
        ->with('Deleted successfully');

    $input = [
        'command' => 'delete',
        'arguments' => ['item'],
        'options' => []
    ];

    $command->execute($input, $output);
});
```

## Test Helpers

### Creating Test Helpers

```php
// tests/Helpers/CommandTestHelper.php

class CommandTestHelper
{
    public static function createInput(
        string $command,
        array $arguments = [],
        array $options = []
    ): array {
        return [
            'command' => $command,
            'arguments' => $arguments,
            'options' => $options
        ];
    }

    public static function createMockOutput(array $expectations = []): Output
    {
        $output = Mockery::mock(Output::class);

        foreach ($expectations as $method => $calls) {
            foreach ($calls as $call) {
                $expectation = $output->shouldReceive($method);

                if (isset($call['with'])) {
                    $expectation->with(...$call['with']);
                }

                if (isset($call['times'])) {
                    $expectation->times($call['times']);
                } else {
                    $expectation->once();
                }

                if (isset($call['return'])) {
                    $expectation->andReturn($call['return']);
                }
            }
        }

        return $output;
    }
}
```

Usage:

```php
test('command with helper', function () {
    $command = new TestCommand();

    $output = CommandTestHelper::createMockOutput([
        'info' => [
            ['with' => ['Processing...']],
        ],
        'success' => [
            ['with' => ['Done!']],
        ]
    ]);

    $input = CommandTestHelper::createInput('test', ['arg1'], ['opt' => true]);

    $result = $command->execute($input, $output);

    expect($result)->toBe(0);
});
```

## Running Tests

### Run All Tests

```bash
composer test
```

### Run Specific Test File

```bash
./vendor/bin/pest tests/Commands/DeployCommandTest.php
```

### Run with Coverage

```bash
composer test-coverage
```

### Run with Coverage Report

```bash
composer test-coverage-html
# Open build/coverage/index.html in browser
```

## Best Practices

### 1. Test One Thing at a Time

```php
// Good - focused test
test('command validates email format', function () {
    // Test only email validation
});

// Poor - testing multiple things
test('command works', function () {
    // Tests validation, execution, and output
});
```

### 2. Use Descriptive Test Names

```php
// Good
test('deploy command fails when environment is not specified')
test('backup command creates compressed archive when compress option is true')

// Poor
test('it works')
test('test command')
```

### 3. Mock External Dependencies

```php
test('api command handles network timeout', function () {
    $httpClient = Mockery::mock(HttpClient::class);
    $httpClient->shouldReceive('get')
        ->andThrow(new TimeoutException());

    $command = new ApiCommand($httpClient);
    // Test error handling
});
```

### 4. Test Edge Cases

```php
test('command handles empty input file', function () {
    // Test with 0-byte file
});

test('command handles very large input', function () {
    // Test with large dataset
});

test('command handles special characters', function () {
    // Test with unicode, quotes, etc.
});
```

### 5. Keep Tests Fast

```php
// Use mocks instead of real file I/O
// Use in-memory databases
// Avoid network calls
// Use data providers for similar tests
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v2

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        coverage: xdebug

    - name: Install dependencies
      run: composer install

    - name: Run tests
      run: composer test

    - name: Run tests with coverage
      run: composer test-coverage-ci

    - name: Upload coverage
      uses: codecov/codecov-action@v2
      with:
        file: ./build/logs/clover.xml
```