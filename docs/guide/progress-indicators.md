# Progress Indicators

Yalla provides comprehensive progress tracking components for long-running tasks. These indicators give users visual feedback about the status and progress of operations.

## Available Indicators

Yalla offers three types of progress indicators:

- **Progress Bars** - Show percentage completion with time estimates
- **Spinners** - Animated indicators for indeterminate progress
- **Step Indicators** - Track multi-step processes

## Progress Bars

Progress bars are ideal when you know the total amount of work to be done.

### Basic Usage

```php
use Yalla\Output\Output;

$output = new Output();
$progress = $output->createProgressBar(100);
$progress->start();

for ($i = 0; $i < 100; $i++) {
    // Do work...
    usleep(50000);
    $progress->advance();
}

$progress->finish();
```

Output:
```
 50/100 [===============>--------------] 50%
```

### Formats

Progress bars support multiple display formats:

```php
// Normal format (default)
$progress->setFormat('normal');
// Output: 50/100 [===============>--------------] 50%

// Verbose format with custom messages
$progress->setFormat('verbose');
$progress->setMessage('Processing files...');
// Output: 50/100 [===============>--------------] 50% - Processing files...

// Detailed format with time estimates
$progress->setFormat('detailed');
// Output: 50/100 [===============>--------------] 50% - 00:05 / 00:10

// Minimal format
$progress->setFormat('minimal');
// Output: [===============>--------------] 50%

// Memory format
$progress->setFormat('memory');
// Output: 50/100 [===============>--------------] 50% - Memory: 4.25 MB
```

### Custom Format

You can create custom format templates:

```php
$progress->setCustomFormat('Progress: {current}/{total} ({percent}%) - {message}');
$progress->setMessage('Downloading...');
```

### Advanced Options

```php
// Set custom bar width
$progress->setBarWidth(50);

// Set redraw frequency for performance
$progress->setRedrawFrequency(10); // Only redraw every 10 items

// Set progress directly
$progress->setProgress(75);

// Clear the progress bar
$progress->clear();
```

### Time Estimates

Progress bars automatically calculate:
- Elapsed time since start
- Estimated time remaining based on current rate
- Formatted as HH:MM:SS for long operations

## Spinners

Spinners are perfect for operations where the duration is unknown.

### Basic Usage

```php
$spinner = $output->createSpinner('Loading data...');
$spinner->start();

// Do work...
for ($i = 0; $i < 50; $i++) {
    usleep(100000);
    $spinner->advance();
}

$spinner->success('Data loaded successfully!');
```

### Frame Styles

Spinners support various animation styles:

```php
// Dots (default)
$spinner = $output->createSpinner('Processing...', 'dots');
// Animation: ⠋ ⠙ ⠹ ⠸ ⠼ ⠴ ⠦ ⠧ ⠇ ⠏

// Line
$spinner = $output->createSpinner('Processing...', 'line');
// Animation: - \ | /

// Pipe
$spinner = $output->createSpinner('Processing...', 'pipe');
// Animation: ┤ ┘ ┴ └ ├ ┌ ┬ ┐

// Arrow
$spinner = $output->createSpinner('Processing...', 'arrow');
// Animation: ← ↖ ↑ ↗ → ↘ ↓ ↙

// Bounce
$spinner = $output->createSpinner('Processing...', 'bounce');
// Animation: ⠁ ⠂ ⠄ ⠂

// Box
$spinner = $output->createSpinner('Processing...', 'box');
// Animation: ▖ ▘ ▝ ▗
```

### Custom Frames

You can define custom animation frames:

```php
$spinner->setFrames(['🌍', '🌎', '🌏']);
$spinner->setInterval(200); // Milliseconds between frames
```

### Dynamic Messages

Update the message while the spinner is running:

```php
$spinner->start();

$steps = ['Connecting...', 'Authenticating...', 'Downloading...', 'Processing...'];
foreach ($steps as $step) {
    $spinner->setMessage($step);
    sleep(1);
}
```

### Completion States

Spinners can finish with different states:

```php
// Success (green checkmark)
$spinner->success('Process completed!');

// Error (red cross)
$spinner->error('Process failed!');

// Warning (yellow exclamation)
$spinner->warning('Process completed with warnings');

// Info (blue info symbol)
$spinner->info('Process terminated by user');
```

### Performance

```php
// Get elapsed time
$elapsed = $spinner->getElapsedTime();

// Clear the spinner output
$spinner->clear();
```

## Step Indicators

Step indicators are ideal for workflows with discrete stages.

### Basic Usage

```php
$steps = $output->steps([
    'Download package',
    'Extract files',
    'Install dependencies',
    'Run migrations',
    'Clear cache'
]);

$steps->start();

// Complete steps as you go
$steps->complete(0, 'Package downloaded (2.3MB)');
sleep(1);

$steps->complete(1, 'Files extracted to /tmp');
sleep(1);

$steps->complete(2, '42 dependencies installed');
sleep(1);

$steps->skip(3, 'No migrations to run');

$steps->fail(4, 'Failed to clear cache');

$steps->finish();
```

Output:
```
[1/5] Download package
  ✅ Package downloaded (2.3MB) [0.5s]

[2/5] Extract files
  ✅ Files extracted to /tmp [0.3s]

[3/5] Install dependencies
  ✅ 42 dependencies installed [2.1s]

[4/5] Run migrations
  ⏭️  No migrations to run

[5/5] Clear cache
  ❌ Failed to clear cache

═══ Summary ═══
Completed: 3
Failed: 1
Skipped: 1
Total time: 3.2s
```

### Alternative Syntax

You can also use array format with step descriptions:

```php
$steps = $output->steps([
    ['download', 'Download package from repository'],
    ['extract', 'Extract compressed files'],
    ['install', 'Install required dependencies'],
    ['migrate', 'Run database migrations'],
    ['cache', 'Clear application cache']
]);
```

### Step States

Each step can be in one of several states:

```php
// Mark as running (shows spinner)
$steps->running(0, 'Downloading package...');

// Mark as completed
$steps->complete(0, 'Download complete');

// Mark as failed
$steps->fail(1, 'Extraction failed: Invalid archive');

// Mark as skipped
$steps->skip(2, 'Dependencies already installed');
```

### Advanced Features

```php
// Auto-advance to next step
$steps->next();

// Get timing for individual steps
$time = $steps->getStepTime(0);

// Custom symbols
$steps = $output->steps($tasks, [
    'pending' => '○',
    'running' => '◉',
    'complete' => '✓',
    'failed' => '✗',
    'skipped' => '→'
]);

// Custom colors
$steps = $output->steps($tasks, [], [
    'pending' => 'gray',
    'running' => 'yellow',
    'complete' => 'green',
    'failed' => 'red',
    'skipped' => 'cyan'
]);

// Show step numbers
$steps = $output->steps($tasks, [], [], true);
```

## Integration Examples

### File Processing

```php
$files = glob('data/*.csv');
$progress = $output->createProgressBar(count($files));
$progress->setFormat('verbose');
$progress->start();

foreach ($files as $file) {
    $progress->setMessage('Processing: ' . basename($file));

    // Process file...
    processFile($file);

    $progress->advance();
}

$progress->finish();
$output->success('All files processed!');
```

### Multi-Stage Deployment

```php
$deployment = $output->steps([
    'Run tests',
    'Build assets',
    'Upload files',
    'Run migrations',
    'Clear cache',
    'Restart services'
]);

$deployment->start();

// Run tests
$deployment->running(0);
$testResult = runTests();
if ($testResult['passed']) {
    $deployment->complete(0, "{$testResult['count']} tests passed");
} else {
    $deployment->fail(0, "{$testResult['failures']} tests failed");
    $deployment->finish();
    return;
}

// Build assets
$deployment->running(1);
$buildSize = buildAssets();
$deployment->complete(1, "Assets built ({$buildSize}MB)");

// Continue with deployment...
```

### API Data Sync

```php
$spinner = $output->createSpinner('Connecting to API...');
$spinner->start();

try {
    $api = connectToAPI();
    $spinner->setMessage('Fetching records...');

    $records = $api->getRecords();
    $spinner->success("Connected! Found {$records->count()} records");

    // Now process with progress bar
    $progress = $output->createProgressBar($records->count());
    $progress->setFormat('detailed');
    $progress->start();

    foreach ($records as $record) {
        processRecord($record);
        $progress->advance();
    }

    $progress->finish();

} catch (Exception $e) {
    $spinner->error('API connection failed: ' . $e->getMessage());
}
```

## Best Practices

### Choose the Right Indicator

- Use **progress bars** when you know the total amount of work
- Use **spinners** for indeterminate operations or quick tasks
- Use **step indicators** for multi-stage workflows

### Performance Optimization

```php
// For large datasets, reduce redraw frequency
$progress->setRedrawFrequency(100); // Update every 100 items

// For spinners, adjust animation speed
$spinner->setInterval(100); // Default is 80ms
```

### User Experience

```php
// Provide meaningful messages
$progress->setMessage('Processing order #' . $orderId);

// Show completion with context
$spinner->success("Import complete: {$imported} records imported, {$skipped} skipped");

// Include timing in step completions
$steps->complete(0, sprintf('Backup complete (%.1fs)', $elapsed));
```

### Error Handling

```php
try {
    $progress->start();
    // ... work ...
    $progress->finish();
} catch (Exception $e) {
    $progress->clear();
    $output->error('Operation failed: ' . $e->getMessage());
}
```

## Testing

When testing code with progress indicators, you can capture output:

```php
ob_start();
$progress = new ProgressBar($output, 100);
$progress->start();
$progress->setProgress(50);
$result = ob_get_clean();

expect($result)->toContain('50/100');
expect($result)->toContain('50%');
```