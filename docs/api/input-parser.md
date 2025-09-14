# InputParser

The `InputParser` class handles parsing command-line arguments into a structured format that commands can easily work with.

## Class Definition

```php
namespace Yalla\Input;

class InputParser
{
    public function parse(array $argv): array
}
```

## Methods

### parse()

```php
public function parse(array $argv): array
```

Parses command-line arguments into a structured array.

#### Parameters

- `$argv` (array): Array of command-line arguments (typically from `$_SERVER['argv']`)

#### Returns

- `array`: Structured array containing command, arguments, and options

#### Return Structure

```php
[
    'command' => string|null,    // The command name
    'arguments' => array,         // Positional arguments
    'options' => array           // Named options/flags
]
```

## Parsing Rules

### Command Detection

The first non-option argument becomes the command:

```php
$parser = new InputParser();

// "deploy" is the command
$result = $parser->parse(['deploy', 'production']);
// Result: ['command' => 'deploy', 'arguments' => ['production'], 'options' => []]

// "db:migrate" is the command
$result = $parser->parse(['db:migrate', '--force']);
// Result: ['command' => 'db:migrate', 'arguments' => [], 'options' => ['force' => true]]
```

### Arguments

Positional parameters after the command:

```php
// Multiple arguments
$result = $parser->parse(['copy', 'source.txt', 'dest.txt']);
// Result: [
//     'command' => 'copy',
//     'arguments' => ['source.txt', 'dest.txt'],
//     'options' => []
// ]
```

### Long Options (--option)

Options starting with `--`:

```php
// Boolean flag
$result = $parser->parse(['deploy', '--force']);
// Result: options => ['force' => true]

// With value using =
$result = $parser->parse(['deploy', '--env=production']);
// Result: options => ['env' => 'production']

// With value using space
$result = $parser->parse(['deploy', '--env', 'production']);
// Result: options => ['env' => 'production']

// Multiple options
$result = $parser->parse(['deploy', '--force', '--env=prod', '--dry-run']);
// Result: options => [
//     'force' => true,
//     'env' => 'prod',
//     'dry-run' => true
// ]
```

### Short Options (-o)

Single-letter options starting with `-`:

```php
// Single flag
$result = $parser->parse(['deploy', '-f']);
// Result: options => ['f' => true]

// Multiple flags combined
$result = $parser->parse(['deploy', '-fvd']);
// Result: options => ['f' => true, 'v' => true, 'd' => true]

// With value
$result = $parser->parse(['deploy', '-e', 'production']);
// Result: options => ['e' => 'production']
```

## Complete Examples

### Basic Command with Arguments

```php
$parser = new InputParser();

$input = ['greet', 'World'];
$result = $parser->parse($input);

// Result:
[
    'command' => 'greet',
    'arguments' => ['World'],
    'options' => []
]
```

### Command with Options

```php
$input = ['deploy', 'production', '--force', '-v'];
$result = $parser->parse($input);

// Result:
[
    'command' => 'deploy',
    'arguments' => ['production'],
    'options' => [
        'force' => true,
        'v' => true
    ]
]
```

### Complex Example

```php
$input = [
    'backup',
    'database',
    '/path/to/backup',
    '--compress=gzip',
    '--exclude=logs',
    '--exclude=temp',
    '-fvq',
    '--timeout',
    '3600'
];

$result = $parser->parse($input);

// Result:
[
    'command' => 'backup',
    'arguments' => ['database', '/path/to/backup'],
    'options' => [
        'compress' => 'gzip',
        'exclude' => 'temp',  // Last value overwrites
        'f' => true,
        'v' => true,
        'q' => true,
        'timeout' => '3600'
    ]
]
```

## Edge Cases

### No Command

```php
$result = $parser->parse(['--help']);
// Result:
[
    'command' => null,
    'arguments' => [],
    'options' => ['help' => true]
]
```

### Options Before Command

```php
$result = $parser->parse(['--verbose', 'deploy']);
// Result:
[
    'command' => 'deploy',
    'arguments' => [],
    'options' => ['verbose' => true]
]
```

### Mixed Format

```php
$result = $parser->parse([
    'deploy',
    'prod',
    '--force',
    '-v',
    '--env=staging',
    'extra-arg',
    '--dry-run'
]);

// Result:
[
    'command' => 'deploy',
    'arguments' => ['prod', 'extra-arg'],
    'options' => [
        'force' => true,
        'v' => true,
        'env' => 'staging',
        'dry-run' => true
    ]
]
```

## Integration with Application

```php
class Application
{
    private InputParser $input;

    public function __construct()
    {
        $this->input = new InputParser();
    }

    public function run(): int
    {
        // Get raw arguments
        $argv = $_SERVER['argv'] ?? [];
        array_shift($argv);  // Remove script name

        // Parse input
        $parsed = $this->input->parse($argv);

        // Use default command if none provided
        if (empty($parsed['command'])) {
            $parsed['command'] = 'list';
        }

        // Get and execute command
        $command = $this->registry->get($parsed['command']);

        if (!$command) {
            $this->output->error("Command not found: {$parsed['command']}");
            return 1;
        }

        return $command->execute($parsed, $this->output);
    }
}
```

## Using with Commands

```php
class DeployCommand extends Command
{
    public function execute(array $input, Output $output): int
    {
        // Input structure from parser
        // $input = [
        //     'command' => 'deploy',
        //     'arguments' => ['production'],
        //     'options' => ['force' => true, 'v' => true]
        // ]

        $environment = $input['arguments'][0] ?? null;
        $force = $input['options']['force'] ?? false;
        $verbose = $input['options']['v'] ?? false;

        // Or using helper methods
        $environment = $this->getArgument($input, 0);
        $force = $this->getOption($input, 'force', false);

        return 0;
    }
}
```

## Special Characters and Quoting

The parser handles shell quoting:

```php
// Spaces in arguments (handled by shell)
// Command line: ./cli process "file with spaces.txt"
$result = $parser->parse(['process', 'file with spaces.txt']);
// Result: arguments => ['file with spaces.txt']

// Equals sign in values
$result = $parser->parse(['set', '--key=name=value']);
// Result: options => ['key' => 'name=value']
```

## Custom Parser Extension

```php
class ExtendedInputParser extends InputParser
{
    public function parse(array $argv): array
    {
        $result = parent::parse($argv);

        // Add custom parsing logic
        $result = $this->parseEnvironmentVariables($result);
        $result = $this->expandAliases($result);

        return $result;
    }

    private function parseEnvironmentVariables(array $result): array
    {
        // Replace $VAR with environment variable values
        foreach ($result['arguments'] as &$arg) {
            if (str_starts_with($arg, '$')) {
                $var = substr($arg, 1);
                $arg = getenv($var) ?: $arg;
            }
        }

        return $result;
    }

    private function expandAliases(array $result): array
    {
        $aliases = [
            'd' => 'deploy',
            'b' => 'build',
            'm' => 'migrate'
        ];

        if (isset($aliases[$result['command']])) {
            $result['command'] = $aliases[$result['command']];
        }

        return $result;
    }
}
```

## Testing

```php
test('parser handles command with arguments', function () {
    $parser = new InputParser();

    $result = $parser->parse(['deploy', 'production', 'v2.0']);

    expect($result['command'])->toBe('deploy');
    expect($result['arguments'])->toBe(['production', 'v2.0']);
    expect($result['options'])->toBe([]);
});

test('parser handles long options', function () {
    $parser = new InputParser();

    $result = $parser->parse(['deploy', '--force', '--env=prod']);

    expect($result['options']['force'])->toBeTrue();
    expect($result['options']['env'])->toBe('prod');
});

test('parser handles short options', function () {
    $parser = new InputParser();

    $result = $parser->parse(['deploy', '-fvd']);

    expect($result['options']['f'])->toBeTrue();
    expect($result['options']['v'])->toBeTrue();
    expect($result['options']['d'])->toBeTrue();
});

test('parser handles mixed input', function () {
    $parser = new InputParser();

    $result = $parser->parse([
        'backup',
        'db',
        '--compress',
        '-v',
        '--output=backup.sql'
    ]);

    expect($result['command'])->toBe('backup');
    expect($result['arguments'])->toBe(['db']);
    expect($result['options'])->toMatchArray([
        'compress' => true,
        'v' => true,
        'output' => 'backup.sql'
    ]);
});
```

## Performance

The InputParser is optimized for performance:

- Single pass parsing: O(n) where n is the number of arguments
- No regular expressions for basic parsing
- Minimal memory allocation

## See Also

- [Application](./application.md) - How Application uses InputParser
- [Command](./command.md) - Using parsed input in commands