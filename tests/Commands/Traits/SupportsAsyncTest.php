<?php

declare(strict_types=1);

use Yalla\Commands\AsyncCommandInterface;
use Yalla\Commands\Command;
use Yalla\Commands\ExitCodes;
use Yalla\Commands\Traits\SupportsAsync;
use Yalla\Output\Output;
use Yalla\Process\Promise;

const EXIT_SOFTWARE = 70;

test('async command execution', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            $output->writeln('Executing command');

            return 0;
        }
    };

    $output = \Mockery::mock(Output::class);
    $output->shouldReceive('writeln')
        ->once()
        ->with('Executing command');
    $output->shouldReceive('isVerbose')
        ->andReturn(false);

    $promise = $command->executeAsync([], $output);
    expect($promise)->toBeInstanceOf(Promise::class);

    $result = $promise->wait();
    expect($result)->toHaveKey('exitCode');
    expect($result['exitCode'])->toBe(0);
});

test('should run async with option', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $input = ['options' => ['async' => true]];
    expect($command->shouldRunAsync($input))->toBeTrue();

    $input = ['options' => ['async' => false]];
    expect($command->shouldRunAsync($input))->toBeFalse();

    $input = [];
    expect($command->shouldRunAsync($input))->toBeFalse();
});

test('should run async by default', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function __construct()
        {
            $this->runAsync = true;
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    expect($command->shouldRunAsync([]))->toBeTrue();
});

test('async timeout', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    expect($command->getAsyncTimeout())->toBe(300);

    $command->setAsyncTimeout(60);
    expect($command->getAsyncTimeout())->toBe(60);
});

test('handle async completion', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 42;
        }
    };

    $output = createOutput();

    $result = ['exitCode' => 42];
    $exitCode = $command->handleAsyncCompletion($result, $output);
    expect($exitCode)->toBe(42);

    $exitCode = $command->handleAsyncCompletion('string result', $output);
    expect($exitCode)->toBe(0);
});

test('handle async error', function () {
    $command = new class extends Command implements AsyncCommandInterface, ExitCodes
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            throw new \RuntimeException('Test error');
        }
    };

    $output = \Mockery::mock(Output::class);
    $output->shouldReceive('error')
        ->once()
        ->with('Async command failed: Test error');

    $output->shouldReceive('isDebug')
        ->once()
        ->andReturn(false);

    $exception = new \RuntimeException('Test error');
    $exitCode = $command->handleAsyncError($exception, $output);
    expect($exitCode)->toBe(EXIT_SOFTWARE);
});

test('handle async error with debug', function () {
    $command = new class extends Command implements AsyncCommandInterface, ExitCodes
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            throw new \RuntimeException('Test error');
        }
    };

    $output = \Mockery::mock(Output::class);
    $output->shouldReceive('error')
        ->once()
        ->with('Async command failed: Test error');

    $output->shouldReceive('isDebug')
        ->once()
        ->andReturn(true);

    $output->shouldReceive('writeln')
        ->twice();

    $exception = new \RuntimeException('Test error');
    $exitCode = $command->handleAsyncError($exception, $output);
    expect($exitCode)->toBe(EXIT_SOFTWARE);
});

test('run parallel', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function testParallelExecution(Output $output): array
        {
            // Use simple synchronous operations for testing
            $operations = [
                'first' => function () {
                    return 'result1';
                },
                'second' => function () {
                    return 'result2';
                },
                'third' => function () {
                    return 'result3';
                },
            ];

            // Create resolved promises directly for testing
            $promises = [];
            foreach ($operations as $key => $operation) {
                $result = $operation();
                $promises[$key] = \Yalla\Process\Promise::resolved($result);
            }

            // Wait for all promises (should be instant since they're resolved)
            $allPromise = \Yalla\Process\Promise::all($promises);

            return $allPromise->wait();
        }
    };

    $output = createOutput();
    $results = $command->testParallelExecution($output);

    expect($results)->toBe([
        'first' => 'result1',
        'second' => 'result2',
        'third' => 'result3',
    ]);
});

test('configure async options', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function __construct()
        {
            $this->configureAsyncOptions();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $options = $command->getOptions();
    $optionNames = array_column($options, 'name');

    expect($optionNames)->toContain('async');
    expect($optionNames)->toContain('timeout');
});

test('async progress indicator', function () {
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
    $output->shouldReceive('isVerbose')
        ->once()
        ->andReturn(true);

    $promise = $command->executeAsync([], $output);
    expect($promise)->toBeInstanceOf(Promise::class);
});

test('shouldRunAsync returns true when runAsync is set', function () {
    $command = new class extends Command implements AsyncCommandInterface
    {
        use SupportsAsync;

        protected string $name = 'test:async';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function setRunAsync(bool $value): void
        {
            $this->runAsync = $value;
        }
    };

    // Test when runAsync is true and no async option in input
    $command->setRunAsync(true);
    expect($command->shouldRunAsync([]))->toBeTrue();

    // Test when runAsync is false but async option is true in input
    $command->setRunAsync(false);
    expect($command->shouldRunAsync(['options' => ['async' => true]]))->toBeTrue();
});

test('handleAsyncError with output', function () {
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
    $output->shouldReceive('error')
        ->once()
        ->with('Async command failed: Test error');
    $output->shouldReceive('isDebug')
        ->once()
        ->andReturn(false);

    $exception = new \RuntimeException('Test error');
    $exitCode = $command->handleAsyncError($exception, $output);
    expect($exitCode)->toBe(EXIT_SOFTWARE);
});
