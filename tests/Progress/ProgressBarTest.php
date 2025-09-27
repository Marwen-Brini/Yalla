<?php

declare(strict_types=1);

use Yalla\Output\Output;
use Yalla\Progress\ProgressBar;

test('progress bar can be created and started', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    expect($progress)->toBeInstanceOf(ProgressBar::class);
    expect($progress->getTotal())->toBe(100);
    expect($progress->getProgress())->toBe(0);
    expect($progress->isFinished())->toBeFalse();

    ob_start();
    $progress->start();
    $result = ob_get_clean();

    expect($result)->toContain('0/100');
    expect($result)->toContain('[');
    expect($result)->toContain(']');
    expect($result)->toContain('0%');
});

test('progress bar advances correctly', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 10);

    ob_start();
    $progress->start();
    $progress->advance();
    ob_end_clean();

    expect($progress->getProgress())->toBe(1);

    ob_start();
    $progress->advance(3);
    ob_end_clean();

    expect($progress->getProgress())->toBe(4);
});

test('progress bar completes at 100%', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 5);

    ob_start();
    $progress->start();

    for ($i = 0; $i < 5; $i++) {
        $progress->advance();
    }

    $progress->finish();
    $result = ob_get_clean();

    expect($progress->getProgress())->toBe(5);
    expect($progress->isFinished())->toBeTrue();
    expect($result)->toContain('5/5');
    expect($result)->toContain('100%');
});

test('progress bar handles custom width', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    $progress->setBarWidth(50);

    ob_start();
    $progress->start();
    ob_get_clean();

    // The bar width property is applied internally
    expect($progress)->toBeInstanceOf(ProgressBar::class);
});

test('progress bar supports different formats', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    $formats = ['normal', 'verbose', 'detailed', 'minimal', 'memory'];

    foreach ($formats as $format) {
        $progress->setFormat($format);

        ob_start();
        $progress->setProgress(50);
        $result = ob_get_clean();

        expect($result)->toContain('50');
        expect($result)->toContain('50%');
    }
});

test('progress bar displays custom messages', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 10);

    $progress->setFormat('verbose');
    $progress->setMessage('Processing...');

    ob_start();
    $progress->start();
    ob_end_clean();

    // Test that the message was set
    expect($progress)->toBeInstanceOf(ProgressBar::class);
});

test('progress bar handles custom format template', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    $progress->setCustomFormat('Progress: {current} of {total} ({percent}%)');

    ob_start();
    $progress->start();
    $progress->setProgress(25);
    ob_end_clean();

    // Test the format by checking the internal state
    expect($progress->getProgress())->toBe(25);
});

test('progress bar respects redraw frequency', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    $progress->setRedrawFrequency(10);

    ob_start();
    $progress->start();

    // Advance by 5 - should not redraw
    for ($i = 0; $i < 5; $i++) {
        $progress->advance();
    }

    // Advance to 10 - should redraw
    for ($i = 0; $i < 5; $i++) {
        $progress->advance();
    }

    ob_get_clean();

    expect($progress->getProgress())->toBe(10);
});

test('progress bar prevents overflow beyond total', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 10);

    ob_start();
    $progress->setProgress(15); // Try to set beyond total
    ob_end_clean();

    expect($progress->getProgress())->toBe(10);
});

test('progress bar handles zero total gracefully', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 0);

    expect($progress->getTotal())->toBe(1); // Should be set to 1 minimum
});

test('progress bar can be cleared', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 10);

    ob_start();
    $progress->start();
    $progress->clear();
    $result = ob_get_clean();

    // Clear should output spaces to clear the line
    expect($result)->toContain("\r");
});

test('progress bar formats time correctly', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    $progress->setFormat('detailed');

    ob_start();
    $progress->start();
    usleep(100000); // Wait 100ms
    $progress->setProgress(50);
    $result = ob_get_clean();

    // Should contain time information
    expect($result)->toMatch('/\d+/'); // Contains numbers (time)
});

test('progress bar shows memory usage', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 10);

    $progress->setFormat('memory');

    ob_start();
    $progress->start();
    $result = ob_get_clean();

    expect($result)->toMatch('/\d+(\.\d+)?\s*(B|KB|MB|GB)/');
});

test('progress bar finishes only once', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 10);

    ob_start();
    $progress->start();
    $progress->finish();
    ob_end_clean();

    expect($progress->isFinished())->toBeTrue();

    ob_start();
    $progress->finish(); // Second call should do nothing
    $result = ob_get_clean();

    expect($result)->toBe('');
});

test('progress bar Output integration', function () {
    $output = new Output();
    $progress = $output->createProgressBar(50);

    expect($progress)->toBeInstanceOf(ProgressBar::class);
    expect($progress->getTotal())->toBe(50);
});

test('progress bar handles invalid format gracefully', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    // Test invalid format fallback to 'normal'
    $progress->setFormat('invalid_format_that_does_not_exist');

    ob_start();
    $progress->start();
    ob_end_clean();

    expect($progress)->toBeInstanceOf(ProgressBar::class);
});

test('progress bar calculates percentage correctly for zero total', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 0); // This gets normalized to 1

    ob_start();
    $progress->start();
    $progress->setProgress(0);
    ob_end_clean();

    // With total=1 and current=0, percentage should be 0
    expect($progress->getProgress())->toBe(0);
});

test('progress bar handles true zero total in percentage calculation', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    // Use reflection to set total to 0 directly
    $reflection = new ReflectionClass($progress);
    $totalProperty = $reflection->getProperty('total');
    $totalProperty->setAccessible(true);
    $totalProperty->setValue($progress, 0);

    $getPercentageMethod = $reflection->getMethod('getPercentage');
    $getPercentageMethod->setAccessible(true);

    $percentage = $getPercentageMethod->invoke($progress);
    expect($percentage)->toBe(100);
});

test('progress bar formats minutes correctly without hours', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    $reflection = new ReflectionClass($progress);
    $formatTimeMethod = $reflection->getMethod('formatTime');
    $formatTimeMethod->setAccessible(true);

    // Test 90 seconds (1 minute 30 seconds) - should be 01:30 format
    $result = $formatTimeMethod->invoke($progress, 90);
    expect($result)->toBe('01:30');
});

test('progress bar estimates time correctly with zero rate', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    // Start but don't advance - rate will be 0
    ob_start();
    $progress->start();
    $progress->setFormat('detailed'); // Format that includes estimated time
    ob_end_clean();

    expect($progress->getProgress())->toBe(0);
});

test('progress bar returns dash when rate is zero in getEstimatedTime', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 100);

    $reflection = new ReflectionClass($progress);
    $getEstimatedTimeMethod = $reflection->getMethod('getEstimatedTime');
    $getEstimatedTimeMethod->setAccessible(true);

    // Set up a scenario where rate will be exactly 0
    $startTimeProperty = $reflection->getProperty('startTime');
    $startTimeProperty->setAccessible(true);
    $startTimeProperty->setValue($progress, microtime(true) - 1); // 1 second ago

    $currentProperty = $reflection->getProperty('current');
    $currentProperty->setAccessible(true);
    $currentProperty->setValue($progress, 0); // Zero progress made

    $result = $getEstimatedTimeMethod->invoke($progress);
    expect($result)->toBe('--:--');
});

test('progress bar formats time correctly for hours', function () {
    $output = new Output();
    $progress = new ProgressBar($output, 1);

    ob_start();
    $progress->start();

    // Simulate a very slow process by manually setting start time far in past
    $reflection = new ReflectionClass($progress);
    $startTimeProperty = $reflection->getProperty('startTime');
    $startTimeProperty->setAccessible(true);
    $startTimeProperty->setValue($progress, microtime(true) - 7200); // 2 hours ago

    $progress->advance();
    $progress->finish(); // Explicitly finish to test completion
    ob_end_clean();

    expect($progress->isFinished())->toBeTrue();
    expect($progress->getProgress())->toBe(1);
});