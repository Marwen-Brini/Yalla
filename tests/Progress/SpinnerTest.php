<?php

declare(strict_types=1);

use Yalla\Output\Output;
use Yalla\Progress\Spinner;

test('spinner can be created and started', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Loading...');

    expect($spinner)->toBeInstanceOf(Spinner::class);
    expect($spinner->isRunning())->toBeFalse();

    ob_start();
    $spinner->start();
    $result = ob_get_clean();

    expect($spinner->isRunning())->toBeTrue();
    expect($result)->toContain('Loading...');
});

test('spinner advances through frames', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    $spinner->setFrames('simple');
    $spinner->setInterval(0.01); // Fast interval for testing

    ob_start();
    $spinner->start();

    $firstFrame = $spinner->getCurrentFrame();

    usleep(20000); // Wait for interval
    $spinner->advance();

    $secondFrame = $spinner->getCurrentFrame();
    ob_end_clean();

    expect($firstFrame)->not->toBe($secondFrame);
});

test('spinner supports different frame sets', function () {
    $output = new Output();

    $frameSets = ['dots', 'dots2', 'line', 'pipe', 'simple', 'arrow', 'bounce', 'box'];

    foreach ($frameSets as $frameSet) {
        $spinner = new Spinner($output, 'Test', $frameSet);

        ob_start();
        $spinner->start();
        ob_end_clean();

        expect($spinner->getCurrentFrame())->toBeString();
        expect($spinner->getCurrentFrame())->not->toBeEmpty();
    }
});

test('spinner can set custom frames', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    $customFrames = ['A', 'B', 'C', 'D'];
    $spinner->setCustomFrames($customFrames);

    ob_start();
    $spinner->start();
    ob_end_clean();

    expect($spinner->getCurrentFrame())->toBeIn($customFrames);
});

test('spinner message can be updated', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Initial');

    ob_start();
    $spinner->start();
    ob_get_clean();

    ob_start();
    $spinner->setMessage('Updated message');
    $result = ob_get_clean();

    expect($result)->toContain('Updated message');
});

test('spinner stops with success', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Processing');

    ob_start();
    $spinner->start();
    $spinner->success('Complete!');
    $result = ob_get_clean();

    expect($spinner->isRunning())->toBeFalse();
    expect($result)->toContain('✅');
    expect($result)->toContain('Complete!');
});

test('spinner stops with error', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Processing');

    ob_start();
    $spinner->start();
    $spinner->error('Failed!');
    $result = ob_get_clean();

    expect($spinner->isRunning())->toBeFalse();
    expect($result)->toContain('❌');
    expect($result)->toContain('Failed!');
});

test('spinner stops with warning', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Processing');

    ob_start();
    $spinner->start();
    $spinner->warning('Warning!');
    $result = ob_get_clean();

    expect($spinner->isRunning())->toBeFalse();
    expect($result)->toContain('⚠️');
    expect($result)->toContain('Warning!');
});

test('spinner stops with info', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Processing');

    ob_start();
    $spinner->start();
    $spinner->info('Information');
    $result = ob_get_clean();

    expect($spinner->isRunning())->toBeFalse();
    expect($result)->toContain('ℹ️');
    expect($result)->toContain('Information');
});

test('spinner can be cleared', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    ob_start();
    $spinner->start();
    $spinner->clear();
    $result = ob_get_clean();

    expect($result)->toContain("\r");
});

test('spinner tracks elapsed time', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    ob_start();
    $spinner->start();
    usleep(100000); // Wait 100ms

    $elapsed = $spinner->getElapsedTime();
    ob_end_clean();

    expect($elapsed)->toBeGreaterThan(0.09);
    expect($elapsed)->toBeLessThan(0.2);
});

test('spinner interval can be configured', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    $spinner->setInterval(0.5);

    ob_start();
    $spinner->start();

    $firstFrame = $spinner->getCurrentFrame();

    usleep(100000); // Wait less than interval
    $spinner->advance();

    $secondFrame = $spinner->getCurrentFrame();
    ob_end_clean();

    // Should not advance because interval not reached
    expect($firstFrame)->toBe($secondFrame);
});

test('spinner stops gracefully when not running', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    ob_start();
    $spinner->success('Done'); // Stop without starting
    $result = ob_get_clean();

    expect($result)->toBe('');
    expect($spinner->isRunning())->toBeFalse();
});

test('spinner Output integration', function () {
    $output = new Output();
    $spinner = $output->createSpinner('Loading...', 'dots');

    expect($spinner)->toBeInstanceOf(Spinner::class);
    expect($spinner->isRunning())->toBeFalse();
});

test('spinner handles empty message', function () {
    $output = new Output();
    $spinner = new Spinner($output);

    ob_start();
    $spinner->start();
    ob_end_clean();

    // Should show just the spinner frame
    expect($spinner->isRunning())->toBeTrue();
});

test('spinner restart when already running returns early', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    ob_start();
    $spinner->start();

    // Try to start again while already running
    $spinner->start('New message');
    ob_end_clean();

    expect($spinner->isRunning())->toBeTrue();
});

test('spinner start with empty message parameter', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Original message');

    ob_start();
    $spinner->start(''); // Empty message should not change original
    ob_end_clean();

    expect($spinner->isRunning())->toBeTrue();
});

test('spinner start with non-empty message parameter', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Original message');

    ob_start();
    $spinner->start('New message'); // Should change message
    ob_end_clean();

    expect($spinner->isRunning())->toBeTrue();
});

test('spinner setMessage when not running', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    // Try to set message when not running
    ob_start();
    $spinner->setMessage('New message');
    ob_end_clean();

    expect($spinner->isRunning())->toBeFalse();
});

test('spinner advance when not running returns early', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    // Try to advance when not running
    ob_start();
    $spinner->advance();
    ob_end_clean();

    expect($spinner->isRunning())->toBeFalse();
});

test('spinner clear when not running', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    // Try to clear when not running
    ob_start();
    $spinner->clear();
    ob_end_clean();

    expect($spinner->isRunning())->toBeFalse();
});

test('spinner stop with empty symbol shows only message', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    ob_start();
    $spinner->start();
    $spinner->stop('', 'Final message'); // Empty symbol, only message
    $result = ob_get_clean();

    expect($spinner->isRunning())->toBeFalse();
    expect($result)->toContain('Final message');
});

test('spinner getElapsedTime when not started', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    // Get elapsed time without starting
    $elapsed = $spinner->getElapsedTime();

    expect($elapsed)->toBe(0.0);
});

test('spinner advances when interval not reached', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test');

    // Set very long interval
    $spinner->setInterval(10.0); // 10 seconds

    ob_start();
    $spinner->start();

    $firstFrame = $spinner->getCurrentFrame();
    $spinner->advance(); // Should not advance due to interval
    $secondFrame = $spinner->getCurrentFrame();

    ob_end_clean();

    expect($firstFrame)->toBe($secondFrame);
});

test('spinner with undefined frame set falls back to dots', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test', 'nonexistent_frameset');

    ob_start();
    $spinner->start();
    ob_end_clean();

    // Should fallback and work
    expect($spinner->getCurrentFrame())->toBeString();
    expect($spinner->getCurrentFrame())->not->toBeEmpty();
});

test('spinner clear output', function () {
    $output = new Output();
    $spinner = new Spinner($output, 'Test message');

    ob_start();
    $spinner->start();
    $spinner->clear();
    $result = ob_get_clean();

    expect($result)->toContain("\r");
});