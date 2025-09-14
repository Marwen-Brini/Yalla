# Output

The `Output` class provides methods for formatting and displaying text in the terminal with colors, styles, and various formatting options.

## Class Definition

```php
namespace Yalla\Output;

class Output
{
    private bool $supportsColors;

    // Color constants
    const RESET = "\033[0m";
    const BLACK = "\033[30m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";

    // Background color constants
    const BG_BLACK = "\033[40m";
    const BG_RED = "\033[41m";
    const BG_GREEN = "\033[42m";
    const BG_YELLOW = "\033[43m";
    const BG_BLUE = "\033[44m";
    const BG_MAGENTA = "\033[45m";
    const BG_CYAN = "\033[46m";
    const BG_WHITE = "\033[47m";

    // Style constants
    const BOLD = "\033[1m";
    const DIM = "\033[2m";
    const UNDERLINE = "\033[4m";
}
```

## Constructor

```php
public function __construct()
```

Creates a new Output instance and automatically detects color support.

## Basic Output Methods

### write()

```php
public function write(string $message, bool $newline = false): void
```

Writes text to the output.

#### Parameters

- `$message` (string): The message to write
- `$newline` (bool): Whether to add a newline after the message

#### Example

```php
$output->write('Processing...');
$output->write(' Done!', true);  // Adds newline
```

### writeln()

```php
public function writeln(string $message): void
```

Writes text with a newline.

#### Parameters

- `$message` (string): The message to write

#### Example

```php
$output->writeln('Hello World');
$output->writeln('Next line');
```

## Semantic Output Methods

### success()

```php
public function success(string $message): void
```

Displays a success message in green.

```php
$output->success('✓ Operation completed successfully!');
```

### error()

```php
public function error(string $message): void
```

Displays an error message in red.

```php
$output->error('✗ Operation failed');
```

### warning()

```php
public function warning(string $message): void
```

Displays a warning message in yellow.

```php
$output->warning('⚠ This action cannot be undone');
```

### info()

```php
public function info(string $message): void
```

Displays an info message in cyan.

```php
$output->info('ℹ Loading configuration...');
```

## Color and Style Methods

### color()

```php
public function color(string $text, string $color): string
```

Applies color to text.

#### Parameters

- `$text` (string): The text to color
- `$color` (string): Color constant (e.g., Output::RED)

#### Returns

- `string`: Colored text (or plain text if colors not supported)

#### Example

```php
$red = $output->color('Error', Output::RED);
$output->writeln($red);

// With background
$highlighted = $output->color('Important', Output::BG_YELLOW);
```

### bold()

```php
public function bold(string $message): void
```

Displays text in bold.

```php
$output->bold('Important Notice');
```

### dim()

```php
public function dim(string $message): void
```

Displays dimmed/faded text.

```php
$output->dim('Less important note');
```

### underline()

```php
public function underline(string $message): void
```

Displays underlined text.

```php
$output->underline('Click here');
```

## Formatting Methods

### table()

```php
public function table(array $headers, array $rows): void
```

Displays data in a formatted table.

#### Parameters

- `$headers` (array): Column headers
- `$rows` (array): Array of row data

#### Example

```php
$output->table(
    ['ID', 'Name', 'Status'],
    [
        ['1', 'Server A', 'Running'],
        ['2', 'Server B', 'Stopped'],
    ]
);
```

Output:
```
│ ID │ Name     │ Status  │
├────┼──────────┼─────────┤
│ 1  │ Server A │ Running │
│ 2  │ Server B │ Stopped │
```

### box()

```php
public function box(string $content, string $color = self::WHITE): void
```

Draws a box around content.

#### Parameters

- `$content` (string): Content to display in the box
- `$color` (string): Box color (default: white)

#### Example

```php
$output->box('Welcome!', Output::CYAN);
```

Output:
```
╔═══════════╗
║ Welcome!  ║
╚═══════════╝
```

### section()

```php
public function section(string $title): void
```

Creates a section header.

#### Parameters

- `$title` (string): Section title

#### Example

```php
$output->section('Configuration');
```

Output:
```

━━━ Configuration ━━━

```

### tree()

```php
public function tree(array $items, int $level = 0): void
```

Displays hierarchical data as a tree.

#### Parameters

- `$items` (array): Hierarchical array data
- `$level` (int): Starting indentation level

#### Example

```php
$output->tree([
    'src' => [
        'Commands' => ['Command.php', 'HelpCommand.php'],
        'Output' => ['Output.php']
    ],
    'tests' => 'Test files'
]);
```

Output:
```
src:
├── Commands:
│   ├── Command.php
│   └── HelpCommand.php
└── Output:
    └── Output.php
tests: Test files
```

## Progress Indicators

### progressBar()

```php
public function progressBar(int $current, int $total, int $width = 50): void
```

Displays a progress bar.

#### Parameters

- `$current` (int): Current progress value
- `$total` (int): Total value
- `$width` (int): Bar width in characters (default: 50)

#### Example

```php
for ($i = 0; $i <= 100; $i++) {
    $output->progressBar($i, 100);
    usleep(50000);
}
```

Output:
```
[██████████████████████████████████████████████████] 100%
```

### spinner()

```php
public function spinner(int $step = 0): void
```

Displays a spinner animation.

#### Parameters

- `$step` (int): Animation step

#### Example

```php
for ($i = 0; $i < 50; $i++) {
    $output->spinner($i);
    usleep(100000);
}
$output->write("\r");  // Clear spinner
```

## Platform Detection

The Output class automatically detects platform capabilities:

```php
// Check if colors are supported
if ($output->hasColorSupport()) {
    // Use colors
}
```

### Windows Support

On Windows, checks for:
- ANSICON environment variable
- ConEmu terminal

### Unix Support

On Unix systems, checks for:
- TTY availability
- Terminal type

## Complete Example

```php
<?php

use Yalla\Output\Output;

$output = new Output();

// Section header
$output->section('System Status');

// Status messages
$output->success('✓ Database connected');
$output->error('✗ Cache server unreachable');
$output->warning('⚠ High memory usage');
$output->info('ℹ 3 updates available');

// Table
$output->section('Services');
$output->table(
    ['Service', 'Status', 'CPU', 'Memory'],
    [
        ['nginx', 'Running', '0.2%', '45MB'],
        ['mysql', 'Running', '1.5%', '512MB'],
        ['redis', 'Stopped', '0.0%', '0MB'],
    ]
);

// Progress
$output->section('Processing');
for ($i = 0; $i <= 100; $i += 10) {
    $output->progressBar($i, 100);
    usleep(100000);
}
$output->writeln('');

// Tree
$output->section('Project Structure');
$output->tree([
    'app' => [
        'Commands' => '15 files',
        'Models' => '8 files',
    ],
    'tests' => '42 files'
]);

// Box
$output->box('Task completed!', Output::GREEN);
```

## Testing Output

```php
test('output displays colored text', function () {
    $output = Mockery::mock(Output::class);

    $output->shouldReceive('success')
        ->once()
        ->with('Operation successful');

    $output->shouldReceive('error')
        ->once()
        ->with('Operation failed');

    // Test your command that uses output
});
```

## Performance Tips

1. **Buffer Output**: For many lines, consider buffering
2. **Minimize Redraws**: Use `\r` for updating single lines
3. **Check Color Support**: Disable colors when piping output

```php
// Efficient progress updates
$output->write("\r" . $output->color("Progress: $percent%", Output::GREEN));
```

## See Also

- [Command](./command.md) - Using Output in commands
- [Application](./application.md) - Application output handling