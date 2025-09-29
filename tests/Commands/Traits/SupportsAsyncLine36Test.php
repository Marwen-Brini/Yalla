<?php

declare(strict_types=1);

use Yalla\Commands\AsyncCommandInterface;
use Yalla\Commands\Command;
use Yalla\Commands\Traits\SupportsAsync;
use Yalla\Output\Output;
use Yalla\Process\Promise;

test('executeAsync progress callback execution covers line 36', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $output = \Mockery::mock(Output::class);
    $output->shouldReceive('isVerbose')->andReturn(true);

    // Expect the progress indicator (line 36) to be called
    $output->shouldReceive('write')->with('.')->once();

    // Create the promise which sets up the progress callback
    $promise = $command->executeAsync([], $output);

    // Now manually trigger the progress callback using the Promise's progress method
    $promise->progress(50);

    expect($promise)->toBeInstanceOf(Promise::class);
});

test('executeAsync verbose output with multiple progress updates', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $output = \Mockery::mock(Output::class);
    $output->shouldReceive('isVerbose')->andReturn(true);

    // Expect multiple progress indicators (line 36) to be called
    $output->shouldReceive('write')->with('.')->times(3);

    // Create the promise
    $promise = $command->executeAsync([], $output);

    // Trigger progress multiple times to ensure line 36 is covered
    $promise->progress(25);
    $promise->progress(50);
    $promise->progress(75);

    expect($promise)->toBeInstanceOf(Promise::class);
});

test('executeAsync non-verbose output should not trigger progress callback', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $output = \Mockery::mock(Output::class);
    $output->shouldReceive('isVerbose')->andReturn(false);

    // Should NOT expect any progress indicators when not verbose
    $output->shouldNotReceive('write');

    // Create the promise
    $promise = $command->executeAsync([], $output);

    // Even if we trigger progress, nothing should happen since isVerbose is false
    $promise->progress(50);

    expect($promise)->toBeInstanceOf(Promise::class);
});
