<?php

declare(strict_types=1);

use Yalla\Output\Output;
use Yalla\Progress\StepIndicator;

test('step indicator can be created with steps', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2', 'Step 3'];
    $indicator = new StepIndicator($output, $steps);

    expect($indicator)->toBeInstanceOf(StepIndicator::class);
    expect($indicator->getCurrentStep())->toBe(-1);
    expect($indicator->isFinished())->toBeFalse();
});

test('step indicator starts and sets first step as running', function () {
    $output = new Output;
    $steps = ['Initialize', 'Configure', 'Deploy'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $result = ob_get_clean();

    expect($indicator->getCurrentStep())->toBe(0);
    expect($indicator->getStepStatus(0))->toBe(StepIndicator::STATUS_RUNNING);
    expect($indicator->getStepStatus(1))->toBe(StepIndicator::STATUS_PENDING);
    expect($result)->toContain('Initialize');
    expect($result)->toContain('🔄');
});

test('step indicator advances through steps', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2', 'Step 3'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->next();
    ob_end_clean();

    expect($indicator->getCurrentStep())->toBe(1);
    expect($indicator->getStepStatus(0))->toBe(StepIndicator::STATUS_COMPLETE);
    expect($indicator->getStepStatus(1))->toBe(StepIndicator::STATUS_RUNNING);
});

test('step indicator completes steps with messages', function () {
    $output = new Output;
    $steps = ['Download', 'Install', 'Configure'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->complete(0, 'Downloaded 100MB');
    ob_end_clean();

    expect($indicator->getStepStatus(0))->toBe(StepIndicator::STATUS_COMPLETE);
});

test('step indicator skips steps', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2', 'Step 3'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->skip(1, 'Not required');
    ob_end_clean();

    expect($indicator->getStepStatus(1))->toBe(StepIndicator::STATUS_SKIPPED);
});

test('step indicator fails steps', function () {
    $output = new Output;
    $steps = ['Build', 'Test', 'Deploy'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->fail(1, 'Tests failed');
    ob_end_clean();

    expect($indicator->getStepStatus(1))->toBe(StepIndicator::STATUS_FAILED);
});

test('step indicator can set step as running', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2', 'Step 3'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->running(2, 'Processing...');
    ob_end_clean();

    expect($indicator->getCurrentStep())->toBe(2);
    expect($indicator->getStepStatus(2))->toBe(StepIndicator::STATUS_RUNNING);
});

test('step indicator finishes and shows summary', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2', 'Step 3'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->complete(0);
    $indicator->skip(1);
    $indicator->fail(2);
    $indicator->finish();
    $result = ob_get_clean();

    expect($indicator->isFinished())->toBeTrue();
    expect($result)->toContain('Summary');
    expect($result)->toContain('1 completed');
    expect($result)->toContain('1 skipped');
    expect($result)->toContain('1 failed');
});

test('step indicator handles array format steps', function () {
    $output = new Output;
    $steps = [
        ['name' => 'Initialize', 'description' => 'Setting up environment'],
        ['name' => 'Deploy', 'description' => 'Deploying to server'],
    ];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $result = ob_get_clean();

    expect($result)->toContain('Initialize');
});

test('step indicator supports custom symbols', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    $indicator->setSymbols([
        'pending' => '○',
        'running' => '◉',
        'complete' => '●',
    ]);

    ob_start();
    $indicator->start();
    ob_end_clean();

    expect($indicator)->toBeInstanceOf(StepIndicator::class);
});

test('step indicator supports custom colors', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    $indicator->setColors([
        'pending' => Output::WHITE,
        'running' => Output::BLUE,
        'complete' => Output::GREEN,
    ]);

    ob_start();
    $indicator->start();
    ob_end_clean();

    expect($indicator)->toBeInstanceOf(StepIndicator::class);
});

test('step indicator shows step numbers', function () {
    $output = new Output;
    $steps = ['First', 'Second', 'Third'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $result = ob_get_clean();

    expect($result)->toContain('[1/3]');
    expect($result)->toContain('[2/3]');
    expect($result)->toContain('[3/3]');
});

test('step indicator tracks time for steps', function () {
    $output = new Output;
    $steps = ['Quick step', 'Another step'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    usleep(100000); // Wait 100ms
    $indicator->complete(0);
    $result = ob_get_clean();

    // Should show time information
    expect($result)->toMatch('/\d+ms/');
});

test('step indicator auto-completes running step on next', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2', 'Step 3'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->next('First complete');
    ob_end_clean();

    expect($indicator->getStepStatus(0))->toBe(StepIndicator::STATUS_COMPLETE);
    expect($indicator->getStepStatus(1))->toBe(StepIndicator::STATUS_RUNNING);
});

test('step indicator finishes when advancing past last step', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->next();
    $indicator->next();
    ob_end_clean();

    expect($indicator->isFinished())->toBeTrue();
});

test('step indicator marks pending steps as skipped on finish', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2', 'Step 3'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->complete(0);
    // Leave step 1 and 2 as pending
    $indicator->finish();
    ob_end_clean();

    expect($indicator->getStepStatus(1))->toBe(StepIndicator::STATUS_SKIPPED);
    expect($indicator->getStepStatus(2))->toBe(StepIndicator::STATUS_SKIPPED);
});

test('step indicator does nothing when already finished', function () {
    $output = new Output;
    $steps = ['Step 1'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->finish();
    ob_end_clean();

    ob_start();
    $indicator->finish(); // Second call
    $result = ob_get_clean();

    expect($result)->toBe('');
    expect($indicator->isFinished())->toBeTrue();
});

test('step indicator handles invalid step index gracefully', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->complete(99); // Invalid index
    ob_end_clean();

    // Should not crash
    expect($indicator->getStepStatus(99))->toBeNull();
});

test('step indicator Output integration', function () {
    $output = new Output;
    $steps = ['Initialize', 'Process', 'Complete'];
    $indicator = $output->steps($steps);

    expect($indicator)->toBeInstanceOf(StepIndicator::class);
    expect($indicator->getCurrentStep())->toBe(-1);
});

test('step indicator formats time correctly', function () {
    $output = new Output;
    $steps = ['Long running task'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    sleep(1); // Wait 1 second
    $indicator->complete(0);
    $result = ob_get_clean();

    // Should show seconds
    expect($result)->toMatch('/\d+\.\d+s/');
});

test('step indicator handles start when already started', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();

    // Try to start again
    $indicator->start();
    ob_end_clean();

    expect($indicator->getCurrentStep())->toBe(0);
});

test('step indicator skip with non-existent step', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->skip(999, 'Invalid step'); // Non-existent step
    ob_end_clean();

    // Should handle gracefully
    expect($indicator->getStepStatus(999))->toBeNull();
});

test('step indicator complete with non-existent step', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->complete(999, 'Invalid step'); // Non-existent step
    ob_end_clean();

    // Should handle gracefully
    expect($indicator->getStepStatus(999))->toBeNull();
});

test('step indicator fail with non-existent step', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->fail(999, 'Invalid step'); // Non-existent step
    ob_end_clean();

    // Should handle gracefully
    expect($indicator->getStepStatus(999))->toBeNull();
});

test('step indicator running with non-existent step', function () {
    $output = new Output;
    $steps = ['Step 1', 'Step 2'];
    $indicator = new StepIndicator($output, $steps);

    ob_start();
    $indicator->start();
    $indicator->running(999, 'Invalid step'); // Non-existent step
    ob_end_clean();

    // Should handle gracefully
    expect($indicator->getStepStatus(999))->toBeNull();
});

test('step indicator getStepTime with no start time', function () {
    $output = new Output;
    $steps = ['Step 1'];
    $indicator = new StepIndicator($output, $steps);

    // Create step but don't start it
    $reflection = new ReflectionClass($indicator);
    $getStepTimeMethod = $reflection->getMethod('getStepTime');
    $getStepTimeMethod->setAccessible(true);

    $result = $getStepTimeMethod->invoke($indicator, 0);

    expect($result)->toBeNull();
});

test('step indicator formatTime for very long durations', function () {
    $output = new Output;
    $steps = ['Long step'];
    $indicator = new StepIndicator($output, $steps);

    $reflection = new ReflectionClass($indicator);
    $formatTimeMethod = $reflection->getMethod('formatTime');
    $formatTimeMethod->setAccessible(true);

    // Test formatting of 3665 seconds (1 hour, 1 minute, 5 seconds)
    $result = $formatTimeMethod->invoke($indicator, 3665);

    expect($result)->toBe('61m 5s');
});
