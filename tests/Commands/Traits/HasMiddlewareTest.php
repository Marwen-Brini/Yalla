<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\Middleware\MiddlewareInterface;
use Yalla\Commands\Traits\HasMiddleware;
use Yalla\Output\Output;

test('add single middleware', function () {
    $command = new class extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $middleware = \Mockery::mock(MiddlewareInterface::class);
    $command->middleware($middleware);

    expect($command->hasMiddleware())->toBeTrue();
    expect($command->getMiddleware())->toHaveCount(1);
});

test('add multiple middleware', function () {
    $command = new class extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $middleware1 = \Mockery::mock(MiddlewareInterface::class);
    $middleware2 = \Mockery::mock(MiddlewareInterface::class);
    $middleware3 = \Mockery::mock(MiddlewareInterface::class);

    $command->middlewares([$middleware1, $middleware2, $middleware3]);

    expect($command->hasMiddleware())->toBeTrue();
    expect($command->getMiddleware())->toHaveCount(3);
});

test('clear middleware', function () {
    $command = new class extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $middleware = \Mockery::mock(MiddlewareInterface::class);
    $command->middleware($middleware);

    expect($command->hasMiddleware())->toBeTrue();

    $command->clearMiddleware();

    expect($command->hasMiddleware())->toBeFalse();
    expect($command->getMiddleware())->toHaveCount(0);
});

test('clear middleware with initialized pipeline', function () {
    $command = new class extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function initPipelineForTest(): void {
            // Force pipeline to be initialized
            $this->getMiddlewarePipeline();
        }
    };

    $middleware = \Mockery::mock(MiddlewareInterface::class);
    $command->middleware($middleware);

    // Initialize the pipeline
    $command->initPipelineForTest();

    expect($command->hasMiddleware())->toBeTrue();

    // Now clear - should hit line 101
    $command->clearMiddleware();

    expect($command->hasMiddleware())->toBeFalse();
    expect($command->getMiddleware())->toHaveCount(0);
});

test('execute with middleware', function () {
    $executionOrder = [];

    $command = new class($executionOrder) extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function execute(array $input, Output $output): int
        {
            $commandExecution = function($cmd, $in, $out) {
                $this->order[] = 'command';
                return 0;
            };

            return $this->executeWithMiddleware($input, $output, $commandExecution);
        }
    };

    $middleware = new class($executionOrder) implements MiddlewareInterface {
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            $this->order[] = 'before';
            $result = $next($command, $input, $output);
            $this->order[] = 'after';
            return $result;
        }

        public function getPriority(): int { return 100; }
        public function shouldApply(Command $command, array $input): bool { return true; }
    };

    $command->middleware($middleware);

    $output = createOutput();
    $result = $command->execute([], $output);

    expect($result)->toBe(0);
    expect($executionOrder)->toBe(['before', 'command', 'after']);
});

test('execute without middleware', function () {
    $executionOrder = [];

    $command = new class($executionOrder) extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function execute(array $input, Output $output): int
        {
            $commandExecution = function($cmd, $in, $out) {
                $this->order[] = 'command';
                return 42;
            };

            return $this->executeWithMiddleware($input, $output, $commandExecution);
        }
    };

    $output = createOutput();
    $result = $command->execute([], $output);

    expect($result)->toBe(42);
    expect($executionOrder)->toBe(['command']);
});

test('chain middleware', function () {
    $command = new class extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $middleware1 = \Mockery::mock(MiddlewareInterface::class);
    $middleware2 = \Mockery::mock(MiddlewareInterface::class);

    $result = $command
        ->middleware($middleware1)
        ->middleware($middleware2);

    expect($result)->toBe($command);
    expect($command->getMiddleware())->toHaveCount(2);
});

test('get middleware pipeline', function () {
    $command = new class extends Command {
        use HasMiddleware;

        protected string $name = 'test:command';

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function testGetPipeline()
        {
            return $this->getMiddlewarePipeline();
        }
    };

    $middleware = \Mockery::mock(MiddlewareInterface::class);
    $command->middleware($middleware);

    $pipeline = $command->testGetPipeline();
    expect($pipeline)->toBeInstanceOf(\Yalla\Commands\Middleware\MiddlewarePipeline::class);
});