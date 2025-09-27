# Output Formatting

Yalla provides a rich set of output formatting methods to create beautiful and informative CLI interfaces.

## Basic Output

### Simple Text Output

```php
// Write text without newline
$output->write('Processing...');

// Write text with newline
$output->writeln('Task completed!');

// Write multiple lines
$output->writeln('Line 1');
$output->writeln('Line 2');
$output->writeln('Line 3');
```

### Colored Messages

Yalla provides semantic methods for different message types:

```php
// Success message (green)
$output->success('✓ Operation completed successfully!');

// Error message (red)
$output->error('✗ Failed to connect to database');

// Warning message (yellow)
$output->warning('⚠ Cache directory is almost full');

// Info message (cyan)
$output->info('ℹ Loading configuration...');
```

## Color Support

### Using Predefined Colors

```php
// Text colors
$output->writeln($output->color('Red text', Output::RED));
$output->writeln($output->color('Green text', Output::GREEN));
$output->writeln($output->color('Yellow text', Output::YELLOW));
$output->writeln($output->color('Blue text', Output::BLUE));
$output->writeln($output->color('Magenta text', Output::MAGENTA));
$output->writeln($output->color('Cyan text', Output::CYAN));
$output->writeln($output->color('White text', Output::WHITE));

// Background colors
$output->writeln($output->color('Red background', Output::BG_RED));
$output->writeln($output->color('Green background', Output::BG_GREEN));
```

### Text Styles

```php
// Bold text
$output->bold('Important message');

// Dim text
$output->dim('Less important note');

// Underlined text
$output->underline('Click here');

// Combining styles
$text = $output->color('Bold and red', Output::RED . Output::BOLD);
$output->writeln($text);
```

## Tables

Tables are perfect for displaying structured data:

```php
// Basic table
$output->table(
    ['ID', 'Name', 'Status'],
    [
        ['1', 'Server A', 'Running'],
        ['2', 'Server B', 'Stopped'],
        ['3', 'Server C', 'Running'],
    ]
);
```

Output:
```
│ ID │ Name     │ Status  │
├────┼──────────┼─────────┤
│ 1  │ Server A │ Running │
│ 2  │ Server B │ Stopped │
│ 3  │ Server C │ Running │
```

### Dynamic Tables

```php
// Building tables from data
$users = [
    ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
    ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
];

$headers = ['ID', 'Name', 'Email'];
$rows = array_map(fn($user) => [
    $user['id'],
    $user['name'],
    $user['email']
], $users);

$output->table($headers, $rows);
```

### Advanced Table Formatting (New in v1.4)

For more sophisticated table formatting, use the `createTable()` method:

```php
use Yalla\Output\Table;

$table = $output->createTable([
    'borders' => Table::BORDER_UNICODE,
    'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT],
    'colors' => true,
    'max_width' => 120
]);

$table->setHeaders(['Service', 'Status', 'Uptime'])
      ->addRow(['Database', '✅ Online', '99.9%'])
      ->addRow(['Cache', '⏳ Starting', '0.0%'])
      ->addRow(['API', '❌ Offline', '89.2%'])
      ->render();
```

See the complete [Table Formatting Guide](/guide/tables) for all available features.

## Progress Indicators

### Progress Bar

For long-running operations:

```php
$total = 100;
for ($i = 0; $i <= $total; $i++) {
    $output->progressBar($i, $total);
    usleep(50000); // Simulate work
}
$output->writeln(''); // New line after progress bar
```

Output:
```
[██████████████████████████████████████████████████] 100%
```

### Custom Width Progress Bar

```php
// Narrow progress bar (30 characters)
$output->progressBar($current, $total, 30);

// Wide progress bar (80 characters)
$output->progressBar($current, $total, 80);
```

### Spinner

For indeterminate progress:

```php
for ($i = 0; $i < 50; $i++) {
    $output->spinner($i);
    usleep(100000); // Simulate work
}
$output->write("\r"); // Clear spinner
$output->writeln('Done!');
```

## Boxes and Sections

### Box Drawing

Highlight important messages:

```php
$output->box('Welcome to Yalla CLI!', Output::CYAN);
```

Output:
```
╔═══════════════════════╗
║ Welcome to Yalla CLI! ║
╚═══════════════════════╝
```

### Multi-line Boxes

```php
$content = "Line 1\nLine 2\nLine 3";
$output->box($content, Output::GREEN);
```

### Section Headers

Organize output into sections:

```php
$output->section('Configuration');
$output->writeln('Database: MySQL');
$output->writeln('Cache: Redis');

$output->section('Status');
$output->writeln('All systems operational');
```

Output:
```
━━━ Configuration ━━━

Database: MySQL
Cache: Redis

━━━ Status ━━━

All systems operational
```

## Tree Structure

Display hierarchical data:

```php
$structure = [
    'src' => [
        'Commands' => [
            'Command.php' => 'Base command class',
            'HelpCommand.php' => 'Help command'
        ],
        'Output' => [
            'Output.php' => 'Output handler'
        ]
    ],
    'tests' => 'Test files',
    'composer.json' => 'Dependencies'
];

$output->tree($structure);
```

Output:
```
src:
├── Commands:
│   ├── Command.php: Base command class
│   └── HelpCommand.php: Help command
└── Output:
    └── Output.php: Output handler
tests: Test files
composer.json: Dependencies
```

## Advanced Formatting

### Combining Formatters

```php
public function execute(array $input, Output $output): int
{
    $output->section('Deployment Status');

    // Show progress
    for ($i = 1; $i <= 5; $i++) {
        $output->write("Step $i/5: ");

        // Simulate work
        for ($j = 0; $j <= 100; $j += 20) {
            $output->progressBar($j, 100, 30);
            usleep(100000);
        }

        $output->writeln(' ' . $output->color('✓', Output::GREEN));
    }

    // Summary table
    $output->section('Summary');
    $output->table(
        ['Metric', 'Value', 'Status'],
        [
            ['Files deployed', '156', $output->color('✓', Output::GREEN)],
            ['Time taken', '2.3s', $output->color('✓', Output::GREEN)],
            ['Errors', '0', $output->color('✓', Output::GREEN)],
        ]
    );

    $output->box('Deployment successful!', Output::GREEN);

    return 0;
}
```

### Custom Formatting Helper

```php
class FormatHelper
{
    private Output $output;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    public function printStatus(string $service, bool $running): void
    {
        $status = $running
            ? $this->output->color('● Running', Output::GREEN)
            : $this->output->color('● Stopped', Output::RED);

        $this->output->writeln("$service: $status");
    }

    public function printMetric(string $label, $value, string $unit = ''): void
    {
        $formatted = number_format((float) $value, 2);
        $this->output->writeln(
            $this->output->color($label . ':', Output::CYAN) .
            ' ' .
            $this->output->color($formatted . $unit, Output::WHITE)
        );
    }
}
```

## Platform Compatibility

Yalla automatically detects platform capabilities:

```php
// Color support is automatically detected
// On Windows: Checks for ANSICON or ConEmu
// On Unix: Checks for TTY

// Colors are automatically disabled when:
// - Output is piped
// - Terminal doesn't support colors
// - Running in CI environment
```

### Forcing Colors

```php
// You can check if colors are supported
if ($output->hasColorSupport()) {
    $output->writeln($output->color('Colored text', Output::GREEN));
} else {
    $output->writeln('Plain text');
}
```

## Best Practices

### 1. Use Semantic Methods

```php
// Good - semantic and clear
$output->success('Database migrated successfully');
$output->error('Failed to connect to API');

// Poor - manual coloring
$output->writeln($output->color('Database migrated', Output::GREEN));
$output->writeln($output->color('Failed to connect', Output::RED));
```

### 2. Provide Context

```php
// Good - informative output
$output->info('Connecting to database...');
$output->writeln('Host: localhost');
$output->writeln('Database: myapp');
$output->success('Connected successfully');

// Poor - minimal output
$output->writeln('Connecting...');
$output->writeln('Done');
```

### 3. Use Progress Indicators

```php
// For determinate progress
$files = glob('*.txt');
$total = count($files);

foreach ($files as $i => $file) {
    $output->progressBar($i + 1, $total);
    // Process file...
}

// For indeterminate progress
$output->write('Downloading... ');
$i = 0;
while ($downloading) {
    $output->spinner($i++);
    // Check download status...
}
```

### 4. Group Related Output

```php
$output->section('Database Configuration');
// Database-related output...

$output->section('Cache Configuration');
// Cache-related output...

$output->section('Queue Configuration');
// Queue-related output...
```

### 5. Handle Verbosity Levels

```php
public function execute(array $input, Output $output): int
{
    $verbose = $this->getOption($input, 'verbose', false);
    $quiet = $this->getOption($input, 'quiet', false);

    if (!$quiet) {
        $output->info('Starting process...');
    }

    // Always show errors
    if ($error) {
        $output->error('An error occurred: ' . $error);
    }

    if ($verbose) {
        $output->dim('Debug: Processing item #' . $id);
        $output->dim('Debug: Memory usage: ' . memory_get_usage());
    }

    if (!$quiet) {
        $output->success('Process completed');
    }

    return 0;
}
```

## Complete Example

Here's a command that uses various output features:

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

class StatusCommand extends Command
{
    public function __construct()
    {
        $this->name = 'status';
        $this->description = 'Show system status';
    }

    public function execute(array $input, Output $output): int
    {
        // Header
        $output->box('System Status Report', Output::CYAN);
        $output->writeln('');

        // Services section
        $output->section('Services');
        $services = [
            ['Web Server', 'nginx', 'Running', '0.2%', '45 MB'],
            ['Database', 'mysql', 'Running', '1.5%', '512 MB'],
            ['Cache', 'redis', 'Running', '0.1%', '32 MB'],
            ['Queue', 'rabbitmq', 'Stopped', '0.0%', '0 MB'],
        ];

        $output->table(
            ['Service', 'Process', 'Status', 'CPU', 'Memory'],
            array_map(function($service) use ($output) {
                $service[2] = $service[2] === 'Running'
                    ? $output->color('● Running', Output::GREEN)
                    : $output->color('● Stopped', Output::RED);
                return $service;
            }, $services)
        );

        // Metrics section
        $output->section('System Metrics');
        $output->write('CPU Usage: ');
        $output->progressBar(35, 100, 40);
        $output->writeln(' 35%');

        $output->write('Memory:    ');
        $output->progressBar(67, 100, 40);
        $output->writeln(' 67%');

        $output->write('Disk:      ');
        $output->progressBar(45, 100, 40);
        $output->writeln(' 45%');

        // File structure
        $output->section('Application Structure');
        $output->tree([
            'app' => [
                'Commands' => '15 files',
                'Models' => '8 files',
                'Services' => '12 files'
            ],
            'config' => '5 files',
            'tests' => '42 files'
        ]);

        // Summary
        $output->writeln('');
        $issues = 1;
        if ($issues > 0) {
            $output->warning("⚠ Found $issues issue(s) that need attention");
        } else {
            $output->success('✓ All systems operational');
        }

        return 0;
    }
}
```