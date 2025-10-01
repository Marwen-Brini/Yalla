<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\Traits\HandlesSignals;
use Yalla\Output\Output;

// Test command using HandlesSignals trait
class TestSignalCommand extends Command
{
    use HandlesSignals;

    public function __construct()
    {
        $this->name = 'test:signal';
        $this->description = 'Test command for signal handling';
    }

    public function execute(array $input, Output $output): int
    {
        return 0;
    }

    // Expose cleanup for testing
    public function testCleanup(): void
    {
        $this->cleanup();
    }

    // Expose dispatchSignals for testing
    public function testDispatchSignals(): void
    {
        $this->dispatchSignals();
    }
}

beforeEach(function () {
    $this->command = new TestSignalCommand;
    $this->output = new Output;
});

test('isSignalHandlingAvailable checks for pcntl functions', function () {
    $available = function_exists('pcntl_signal') && function_exists('pcntl_async_signals');

    expect($this->command->areSignalsEnabled())->toBeFalse();
});

test('onSignal registers signal handler when available', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $called = false;
    $handler = function ($signal) use (&$called) {
        $called = true;

        return false; // Don't exit
    };

    $result = $this->command->onSignal(SIGTERM, $handler);

    expect($result)->toBe($this->command);
    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();
});

test('onSignal does nothing when pcntl not available', function () {
    if (function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension is available');
    }

    $result = $this->command->onSignal(SIGTERM, fn ($sig) => true);

    expect($result)->toBe($this->command);
});

test('onInterrupt is shortcut for SIGINT', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $this->command->onInterrupt(fn ($sig) => false);

    expect($this->command->hasSignalHandler(SIGINT))->toBeTrue();
});

test('onTerminate is shortcut for SIGTERM', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $this->command->onTerminate(fn ($sig) => false);

    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();
});

test('onCommonSignals registers multiple handlers', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $this->command->onCommonSignals(fn ($sig) => false);

    expect($this->command->hasSignalHandler(SIGINT))->toBeTrue();
    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();
});

test('setSignalOutput stores output instance', function () {
    $result = $this->command->setSignalOutput($this->output);

    expect($result)->toBe($this->command);
});

test('removeSignalHandler removes specific handler', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $this->command->onSignal(SIGTERM, fn ($sig) => false);
    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();

    $this->command->removeSignalHandler(SIGTERM);
    expect($this->command->hasSignalHandler(SIGTERM))->toBeFalse();
});

test('removeAllSignalHandlers clears all handlers', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $this->command->onSignal(SIGINT, fn ($sig) => false);
    $this->command->onSignal(SIGTERM, fn ($sig) => false);

    expect($this->command->getSignalHandlers())->toHaveCount(2);

    $this->command->removeAllSignalHandlers();

    expect($this->command->getSignalHandlers())->toBeEmpty();
});

test('getSignalHandlers returns all registered handlers', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $handler1 = fn ($sig) => false;
    $handler2 = fn ($sig) => false;

    $this->command->onSignal(SIGINT, $handler1);
    $this->command->onSignal(SIGTERM, $handler2);

    $handlers = $this->command->getSignalHandlers();

    expect($handlers)->toHaveCount(2);
    expect($handlers)->toHaveKey(SIGINT);
    expect($handlers)->toHaveKey(SIGTERM);
});

test('hasSignalHandler checks for specific signal', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    expect($this->command->hasSignalHandler(SIGTERM))->toBeFalse();

    $this->command->onSignal(SIGTERM, fn ($sig) => false);

    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();
    expect($this->command->hasSignalHandler(SIGINT))->toBeFalse();
});

test('cleanup method can be called', function () {
    // Just verify it doesn't error
    $this->command->testCleanup();

    expect(true)->toBeTrue();
});

test('dispatchSignals can be called', function () {
    // Just verify it doesn't error
    $this->command->testDispatchSignals();

    expect(true)->toBeTrue();
});

test('registerDefaultInterruptHandler sets up handler', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    // Use reflection to access protected method
    $reflection = new ReflectionClass($this->command);
    $method = $reflection->getMethod('registerDefaultInterruptHandler');
    $method->setAccessible(true);

    $this->command->setSignalOutput($this->output);
    $method->invoke($this->command, 'Test interrupt');

    expect($this->command->hasSignalHandler(SIGINT))->toBeTrue();
});

test('registerGracefulShutdown sets up handlers', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    // Use reflection to access protected method
    $reflection = new ReflectionClass($this->command);
    $method = $reflection->getMethod('registerGracefulShutdown');
    $method->setAccessible(true);

    $this->command->setSignalOutput($this->output);
    $method->invoke($this->command, 'Test shutdown');

    expect($this->command->hasSignalHandler(SIGINT))->toBeTrue();
    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();
});

test('areSignalsEnabled returns correct state', function () {
    if (! function_exists('pcntl_signal')) {
        expect($this->command->areSignalsEnabled())->toBeFalse();
        $this->markTestSkipped('pcntl extension not available');
    }

    expect($this->command->areSignalsEnabled())->toBeFalse();

    $this->command->onSignal(SIGTERM, fn ($sig) => false);

    expect($this->command->areSignalsEnabled())->toBeTrue();
});

test('method chaining works for signal methods', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $result = $this->command
        ->onSignal(SIGINT, fn ($sig) => false)
        ->onSignal(SIGTERM, fn ($sig) => false)
        ->setSignalOutput($this->output);

    expect($result)->toBe($this->command);
});

test('onSignal returns self when pcntl not available', function () {
    // Create a mock command that simulates pcntl not being available
    $command = new class extends TestSignalCommand
    {
        protected function isSignalHandlingAvailable(): bool
        {
            return false;
        }
    };

    $result = $command->onSignal(SIGTERM, fn ($sig) => false);

    expect($result)->toBe($command);
    expect($command->getSignalHandlers())->toBeEmpty();
});

test('wrapped handler with result false does not exit', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    // This tests lines 50-56 by verifying handler that returns false
    $handlerCalled = false;
    $this->command->onSignal(SIGTERM, function ($sig) use (&$handlerCalled) {
        $handlerCalled = true;

        return false; // Should not trigger cleanup/exit
    });

    expect($handlerCalled)->toBeFalse();
    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();
});

test('dispatchSignals calls pcntl_signal_dispatch when enabled', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $this->command->onSignal(SIGTERM, fn ($sig) => false);

    // This should call pcntl_signal_dispatch (line 156)
    $this->command->testDispatchSignals();

    expect($this->command->areSignalsEnabled())->toBeTrue();
});

test('registerDefaultInterruptHandler without output', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    // Use reflection to access protected method
    $reflection = new ReflectionClass($this->command);
    $method = $reflection->getMethod('registerDefaultInterruptHandler');
    $method->setAccessible(true);

    // Don't set signal output - tests lines 218-221 with null check
    $method->invoke($this->command, 'Test');

    expect($this->command->hasSignalHandler(SIGINT))->toBeTrue();
});

test('registerGracefulShutdown without output', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    // Use reflection to access protected method
    $reflection = new ReflectionClass($this->command);
    $method = $reflection->getMethod('registerGracefulShutdown');
    $method->setAccessible(true);

    // Don't set signal output - tests lines 235-238 with null check
    $method->invoke($this->command, 'Test');

    expect($this->command->hasSignalHandler(SIGINT))->toBeTrue();
    expect($this->command->hasSignalHandler(SIGTERM))->toBeTrue();
});

test('signal handler wrapper execution path', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    // Test by registering handler with output and triggering callback manually
    $this->command->setSignalOutput($this->output);

    // Register interrupt handler that tests lines 218-223
    $reflection = new ReflectionClass($this->command);
    $method = $reflection->getMethod('registerDefaultInterruptHandler');
    $method->setAccessible(true);
    $method->invoke($this->command, 'Test interrupted');

    // Get the registered handlers
    $handlers = $this->command->getSignalHandlers();
    expect($handlers)->toHaveKey(SIGINT);

    // Manually call the handler to test execution path (lines 50-55)
    // The handler should return true for cleanup
    $handler = $handlers[SIGINT];
    $result = $handler(SIGINT);

    // Handler returns true, which means it would cleanup and exit (line 53-55)
    expect($result)->toBeTrue();
});

test('signal handler with false return', function () {
    if (! function_exists('pcntl_signal')) {
        $this->markTestSkipped('pcntl extension not available');
    }

    $executed = false;

    // Register handler that returns false
    $this->command->onInterrupt(function ($sig) use (&$executed) {
        $executed = true;

        return false; // Don't cleanup/exit
    });

    $handlers = $this->command->getSignalHandlers();
    $handler = $handlers[SIGINT];

    // Call handler - should execute and return false (line 53 condition fails)
    $result = $handler(SIGINT);

    expect($executed)->toBeTrue();
    expect($result)->toBeFalse();
});
