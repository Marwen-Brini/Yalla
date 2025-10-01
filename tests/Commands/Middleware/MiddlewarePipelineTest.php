<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\Middleware\MiddlewareInterface;
use Yalla\Commands\Middleware\MiddlewarePipeline;
use Yalla\Output\Output;

test('add middleware', function () {
    $pipeline = new MiddlewarePipeline;
    $middleware = \Mockery::mock(MiddlewareInterface::class);
    $middleware->shouldReceive('getPriority')->andReturn(100);

    $pipeline->add($middleware);
    expect($pipeline->getMiddleware())->toHaveCount(1);
});

test('add multiple middleware', function () {
    $pipeline = new MiddlewarePipeline;
    $middleware1 = \Mockery::mock(MiddlewareInterface::class);
    $middleware1->shouldReceive('getPriority')->andReturn(100);
    $middleware2 = \Mockery::mock(MiddlewareInterface::class);
    $middleware2->shouldReceive('getPriority')->andReturn(50);

    $pipeline->addMultiple([$middleware1, $middleware2]);
    expect($pipeline->getMiddleware())->toHaveCount(2);
});

test('add multiple with invalid middleware throws exception', function () {
    $pipeline = new MiddlewarePipeline;
    $invalidMiddleware = new stdClass;

    expect(fn () => $pipeline->addMultiple([$invalidMiddleware]))
        ->toThrow(InvalidArgumentException::class, 'Middleware must implement MiddlewareInterface');
});

test('remove middleware', function () {
    $pipeline = new MiddlewarePipeline;

    $middleware1 = new class implements MiddlewareInterface
    {
        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            return $next($command, $input, $output);
        }

        public function getPriority(): int
        {
            return 100;
        }

        public function shouldApply(Command $command, array $input): bool
        {
            return true;
        }
    };

    $middleware2 = \Mockery::mock(MiddlewareInterface::class);
    $middleware2->shouldReceive('getPriority')->andReturn(50);

    $pipeline->add($middleware1);
    $pipeline->add($middleware2);

    expect($pipeline->getMiddleware())->toHaveCount(2);

    $pipeline->remove(get_class($middleware1));
    expect($pipeline->getMiddleware())->toHaveCount(1);
});

test('middleware execution', function () {
    $pipeline = new MiddlewarePipeline;
    $executionOrder = [];

    $middleware1 = new class($executionOrder) implements MiddlewareInterface
    {
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            $this->order[] = 'middleware1_before';
            $result = $next($command, $input, $output);
            $this->order[] = 'middleware1_after';

            return $result;
        }

        public function getPriority(): int
        {
            return 100;
        }

        public function shouldApply(Command $command, array $input): bool
        {
            return true;
        }
    };

    $middleware2 = new class($executionOrder) implements MiddlewareInterface
    {
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            $this->order[] = 'middleware2_before';
            $result = $next($command, $input, $output);
            $this->order[] = 'middleware2_after';

            return $result;
        }

        public function getPriority(): int
        {
            return 50;
        }

        public function shouldApply(Command $command, array $input): bool
        {
            return true;
        }
    };

    $pipeline->add($middleware1);
    $pipeline->add($middleware2);

    $command = createTestCommand();
    $output = createOutput();

    $destination = function ($cmd, $in, $out) use (&$executionOrder) {
        $executionOrder[] = 'destination';

        return 0;
    };

    $result = $pipeline->execute($command, [], $output, $destination);

    expect($result)->toBe(0);
    expect($executionOrder)->toBe([
        'middleware1_before',
        'middleware2_before',
        'destination',
        'middleware2_after',
        'middleware1_after',
    ]);
});

test('middleware priority', function () {
    $pipeline = new MiddlewarePipeline;
    $executionOrder = [];

    $lowPriority = new class($executionOrder) implements MiddlewareInterface
    {
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            $this->order[] = 'low';

            return $next($command, $input, $output);
        }

        public function getPriority(): int
        {
            return 10;
        }

        public function shouldApply(Command $command, array $input): bool
        {
            return true;
        }
    };

    $highPriority = new class($executionOrder) implements MiddlewareInterface
    {
        private array $order;

        public function __construct(array &$order)
        {
            $this->order = &$order;
        }

        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            $this->order[] = 'high';

            return $next($command, $input, $output);
        }

        public function getPriority(): int
        {
            return 200;
        }

        public function shouldApply(Command $command, array $input): bool
        {
            return true;
        }
    };

    $pipeline->add($lowPriority);
    $pipeline->add($highPriority);

    $command = createTestCommand();
    $output = createOutput();

    $destination = function ($cmd, $in, $out) use (&$executionOrder) {
        $executionOrder[] = 'destination';

        return 0;
    };

    $pipeline->execute($command, [], $output, $destination);

    expect($executionOrder)->toBe(['high', 'low', 'destination']);
});

test('conditional middleware', function () {
    $pipeline = new MiddlewarePipeline;
    $executed = false;

    $conditionalMiddleware = new class($executed) implements MiddlewareInterface
    {
        private bool $executed;

        public function __construct(bool &$executed)
        {
            $this->executed = &$executed;
        }

        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            $this->executed = true;

            return $next($command, $input, $output);
        }

        public function getPriority(): int
        {
            return 100;
        }

        public function shouldApply(Command $command, array $input): bool
        {
            return isset($input['options']['debug']);
        }
    };

    $pipeline->add($conditionalMiddleware);

    $command = createTestCommand();
    $output = createOutput();

    $destination = function ($cmd, $in, $out) {
        return 0;
    };

    // Execute without debug option - middleware should not run
    $pipeline->execute($command, [], $output, $destination);
    expect($executed)->toBeFalse();

    // Execute with debug option - middleware should run
    $pipeline->execute($command, ['options' => ['debug' => true]], $output, $destination);
    expect($executed)->toBeTrue();
});

test('clear middleware', function () {
    $pipeline = new MiddlewarePipeline;
    $middleware = \Mockery::mock(MiddlewareInterface::class);

    $pipeline->add($middleware);
    expect($pipeline->hasMiddleware())->toBeTrue();

    $pipeline->clear();
    expect($pipeline->hasMiddleware())->toBeFalse();
    expect($pipeline->getMiddleware())->toHaveCount(0);
});

test('count middleware', function () {
    $pipeline = new MiddlewarePipeline;

    expect($pipeline->count())->toBe(0);

    $middleware1 = \Mockery::mock(MiddlewareInterface::class);
    $middleware1->shouldReceive('getPriority')->andReturn(100);
    $middleware2 = \Mockery::mock(MiddlewareInterface::class);
    $middleware2->shouldReceive('getPriority')->andReturn(50);

    $pipeline->add($middleware1);
    expect($pipeline->count())->toBe(1);

    $pipeline->add($middleware2);
    expect($pipeline->count())->toBe(2);
});

test('middleware modifies exit code', function () {
    $pipeline = new MiddlewarePipeline;

    $modifyingMiddleware = new class implements MiddlewareInterface
    {
        public function handle(Command $command, array $input, Output $output, Closure $next): int
        {
            $result = $next($command, $input, $output);

            // Modify the exit code
            return $result === 0 ? 42 : $result;
        }

        public function getPriority(): int
        {
            return 100;
        }

        public function shouldApply(Command $command, array $input): bool
        {
            return true;
        }
    };

    $pipeline->add($modifyingMiddleware);

    $command = createTestCommand();
    $output = createOutput();

    $destination = function ($cmd, $in, $out) {
        return 0; // Original exit code
    };

    $result = $pipeline->execute($command, [], $output, $destination);
    expect($result)->toBe(42); // Modified exit code
});
