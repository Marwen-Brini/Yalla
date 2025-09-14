# Input Parsing

The InputParser class handles parsing command-line arguments into a structured format that commands can easily work with.

## How Parsing Works

When a user runs a command like:

```bash
./cli deploy production --force --env=staging -vd
```

The InputParser converts this into:

```php
[
    'command' => 'deploy',
    'arguments' => ['production'],
    'options' => [
        'force' => true,
        'env' => 'staging',
        'v' => true,
        'd' => true
    ]
]
```

## Parsing Rules

### Commands

The first non-option argument becomes the command:

```bash
./cli deploy          # command: 'deploy'
./cli db:migrate      # command: 'db:migrate'
./cli help deploy     # command: 'help', argument: 'deploy'
```

### Arguments

Positional parameters after the command:

```bash
./cli copy source.txt dest.txt
# command: 'copy'
# arguments: ['source.txt', 'dest.txt']
```

### Long Options

Options starting with `--`:

```bash
# Boolean flags
./cli deploy --force
# options: ['force' => true]

# With values using =
./cli deploy --env=production
# options: ['env' => 'production']

# With values using space
./cli deploy --env production
# options: ['env' => 'production']
```

### Short Options

Single-letter options starting with `-`:

```bash
# Single flag
./cli deploy -f
# options: ['f' => true]

# Multiple flags
./cli deploy -fvd
# options: ['f' => true, 'v' => true, 'd' => true]

# With value
./cli deploy -e production
# options: ['e' => 'production']
```

## Advanced Parsing

### Mixed Formats

```bash
./cli deploy production --force -v --env=staging --dry-run
```

Parsed as:
```php
[
    'command' => 'deploy',
    'arguments' => ['production'],
    'options' => [
        'force' => true,
        'v' => true,
        'env' => 'staging',
        'dry-run' => true
    ]
]
```

### Option Terminator

Use `--` to stop parsing options:

```bash
./cli run --verbose -- --not-an-option
# arguments: ['--not-an-option']
```

### Special Characters

Handle special characters in arguments:

```bash
# Quotes for spaces
./cli process "file with spaces.txt"

# Escape sequences
./cli echo "Line 1\nLine 2"

# Shell variables (handled by shell)
./cli process $HOME/file.txt
```

## Using InputParser Directly

While normally handled by the Application class, you can use InputParser directly:

```php
use Yalla\Input\InputParser;

$parser = new InputParser();

// Parse custom input
$input = $parser->parse(['deploy', 'production', '--force', '-v']);

// Result:
// [
//     'command' => 'deploy',
//     'arguments' => ['production'],
//     'options' => ['force' => true, 'v' => true]
// ]
```

## Custom Input Handling

### Reading from STDIN

```php
public function execute(array $input, Output $output): int
{
    $output->write('Enter your name: ');
    $name = trim(fgets(STDIN));

    $output->writeln("Hello, $name!");

    return 0;
}
```

### Interactive Prompts

```php
public function execute(array $input, Output $output): int
{
    // Yes/No prompt
    $output->write('Continue? (y/n): ');
    $answer = strtolower(trim(fgets(STDIN)));

    if ($answer !== 'y' && $answer !== 'yes') {
        $output->info('Operation cancelled');
        return 1;
    }

    // Password input (no echo)
    $output->write('Password: ');
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    $output->writeln(''); // New line after password

    return 0;
}
```

### Multi-line Input

```php
public function execute(array $input, Output $output): int
{
    $output->writeln('Enter text (type "END" on a new line to finish):');

    $lines = [];
    while (true) {
        $line = trim(fgets(STDIN));
        if ($line === 'END') {
            break;
        }
        $lines[] = $line;
    }

    $text = implode("\n", $lines);
    $output->info("You entered:\n$text");

    return 0;
}
```

## Input Validation

### Validating Arguments

```php
public function execute(array $input, Output $output): int
{
    $file = $this->getArgument($input, 'file');

    // Check if provided
    if (empty($file)) {
        $output->error('Error: File argument is required');
        $output->writeln('Usage: cli process <file>');
        return 1;
    }

    // Validate file path
    if (!file_exists($file)) {
        $output->error("Error: File '$file' not found");
        return 1;
    }

    if (!is_readable($file)) {
        $output->error("Error: Cannot read file '$file'");
        return 1;
    }

    // Validate file type
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if (!in_array($ext, ['txt', 'csv', 'json'])) {
        $output->error("Error: Unsupported file type '.$ext'");
        return 1;
    }

    return 0;
}
```

### Validating Options

```php
public function execute(array $input, Output $output): int
{
    $port = $this->getOption($input, 'port', 8080);

    // Validate port number
    if (!is_numeric($port) || $port < 1 || $port > 65535) {
        $output->error('Error: Port must be between 1 and 65535');
        return 1;
    }

    $format = $this->getOption($input, 'format', 'json');
    $validFormats = ['json', 'xml', 'csv'];

    if (!in_array($format, $validFormats)) {
        $output->error("Error: Invalid format '$format'");
        $output->info('Valid formats: ' . implode(', ', $validFormats));
        return 1;
    }

    return 0;
}
```

## Helper Methods

### Creating an Input Helper

```php
class InputHelper
{
    private Output $output;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    public function ask(string $question, string $default = null): string
    {
        $prompt = $question;
        if ($default !== null) {
            $prompt .= " [$default]";
        }
        $prompt .= ': ';

        $this->output->write($prompt);
        $answer = trim(fgets(STDIN));

        return $answer ?: $default ?? '';
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $prompt = $question . ' (' . ($default ? 'Y/n' : 'y/N') . '): ';
        $this->output->write($prompt);

        $answer = strtolower(trim(fgets(STDIN)));

        if ($answer === '') {
            return $default;
        }

        return $answer === 'y' || $answer === 'yes';
    }

    public function choice(string $question, array $choices, $default = null): string
    {
        $this->output->writeln($question);

        foreach ($choices as $key => $label) {
            $this->output->writeln("  [$key] $label");
        }

        $prompt = 'Your choice';
        if ($default !== null) {
            $prompt .= " [$default]";
        }
        $prompt .= ': ';

        $this->output->write($prompt);
        $answer = trim(fgets(STDIN)) ?: $default;

        if (!isset($choices[$answer])) {
            $this->output->error('Invalid choice');
            return $this->choice($question, $choices, $default);
        }

        return $answer;
    }

    public function password(string $prompt = 'Password'): string
    {
        $this->output->write("$prompt: ");

        if (PHP_OS_FAMILY !== 'Windows') {
            system('stty -echo');
            $password = trim(fgets(STDIN));
            system('stty echo');
            $this->output->writeln('');
        } else {
            // Windows fallback
            $password = trim(fgets(STDIN));
        }

        return $password;
    }
}
```

Usage:

```php
public function execute(array $input, Output $output): int
{
    $helper = new InputHelper($output);

    // Ask for input
    $name = $helper->ask('What is your name?', 'Anonymous');

    // Confirmation
    if (!$helper->confirm('Are you sure you want to continue?')) {
        $output->info('Operation cancelled');
        return 1;
    }

    // Multiple choice
    $env = $helper->choice('Select environment', [
        'dev' => 'Development',
        'staging' => 'Staging',
        'prod' => 'Production'
    ], 'dev');

    // Password
    $password = $helper->password('Enter database password');

    return 0;
}
```

## Handling Complex Input

### Parsing Configuration Files

```php
public function execute(array $input, Output $output): int
{
    $configFile = $this->getArgument($input, 'config');

    if ($configFile && file_exists($configFile)) {
        $config = parse_ini_file($configFile, true);
        // Or for JSON:
        // $config = json_decode(file_get_contents($configFile), true);

        // Merge with command options
        foreach ($config as $key => $value) {
            if (!isset($input['options'][$key])) {
                $input['options'][$key] = $value;
            }
        }
    }

    return 0;
}
```

### Environment Variables

```php
public function execute(array $input, Output $output): int
{
    // Use environment variables as fallback
    $apiKey = $this->getOption($input, 'api-key')
        ?? $_ENV['API_KEY']
        ?? getenv('API_KEY');

    if (!$apiKey) {
        $output->error('API key required. Use --api-key or set API_KEY environment variable');
        return 1;
    }

    return 0;
}
```

## Best Practices

1. **Always validate input** - Never trust user input
2. **Provide clear error messages** - Help users understand what went wrong
3. **Use defaults wisely** - Provide sensible defaults for optional parameters
4. **Document expected formats** - Be clear about what input formats are accepted
5. **Handle edge cases** - Empty strings, special characters, very long input
6. **Consider interactive mode** - Prompt for missing required arguments
7. **Support common conventions** - Like `-h` for help, `-v` for version

## Complete Example

Here's a command that demonstrates comprehensive input handling:

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

class ImportCommand extends Command
{
    public function __construct()
    {
        $this->name = 'import';
        $this->description = 'Import data from various sources';

        $this->addArgument('source', 'Data source (file path or URL)', true);
        $this->addArgument('table', 'Target database table', false);

        $this->addOption('format', 'f', 'Input format (csv|json|xml)', 'csv');
        $this->addOption('delimiter', 'd', 'CSV delimiter', ',');
        $this->addOption('skip-header', null, 'Skip first row', false);
        $this->addOption('dry-run', null, 'Simulate import', false);
        $this->addOption('interactive', 'i', 'Interactive mode', false);
    }

    public function execute(array $input, Output $output): int
    {
        $source = $this->getArgument($input, 'source');
        $table = $this->getArgument($input, 'table');
        $format = $this->getOption($input, 'format', 'csv');
        $interactive = $this->getOption($input, 'interactive', false);

        // Interactive mode for missing arguments
        if ($interactive) {
            if (!$source) {
                $output->write('Enter source file or URL: ');
                $source = trim(fgets(STDIN));
            }

            if (!$table) {
                $output->write('Enter target table name: ');
                $table = trim(fgets(STDIN));
            }
        }

        // Validate source
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $output->info("Downloading from: $source");
            // Download file...
        } elseif (file_exists($source)) {
            $output->info("Reading from file: $source");
        } else {
            $output->error("Source not found: $source");
            return 1;
        }

        // Validate format
        if (!in_array($format, ['csv', 'json', 'xml'])) {
            $output->error("Invalid format: $format");
            return 1;
        }

        // Confirm in interactive mode
        if ($interactive) {
            $output->writeln("Ready to import to table: $table");
            $output->write('Continue? (y/n): ');
            if (strtolower(trim(fgets(STDIN))) !== 'y') {
                $output->info('Import cancelled');
                return 0;
            }
        }

        // Perform import...
        $output->success('Import completed successfully');

        return 0;
    }
}
```