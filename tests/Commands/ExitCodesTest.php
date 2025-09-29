<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\ExitCodes;
use Yalla\Output\Output;

// Test command implementation that exposes protected methods for testing
class TestableCommand extends Command
{
    protected string $name = 'test:command';

    protected string $description = 'Test command for exit code testing';

    public function execute(array $input, Output $output): int
    {
        return self::EXIT_SUCCESS;
    }

    // Expose protected methods for testing
    public function testReturnWithCode(int $code = ExitCodes::EXIT_SUCCESS, ?string $message = null): int
    {
        $this->output = new Output;

        return $this->returnWithCode($code, $message);
    }

    public function testReturnSuccess(?string $message = null): int
    {
        $this->output = new Output;

        return $this->returnSuccess($message);
    }

    public function testReturnError(string $message, int $code = ExitCodes::EXIT_FAILURE): int
    {
        $this->output = new Output;

        return $this->returnError($message, $code);
    }

    public function testHandleException(\Throwable $exception): int
    {
        $this->output = new Output;

        return $this->handleException($exception);
    }

    public function testMapExceptionToExitCode(\Throwable $exception): int
    {
        return $this->mapExceptionToExitCode($exception);
    }
}

beforeEach(function () {
    $this->output = new Output;
    $this->command = new TestableCommand;
});

// Test exit code constants
test('exit codes constants are defined correctly', function () {
    expect(ExitCodes::EXIT_SUCCESS)->toBe(0);
    expect(ExitCodes::EXIT_FAILURE)->toBe(1);
    expect(ExitCodes::EXIT_USAGE)->toBe(2);

    // POSIX standard codes
    expect(ExitCodes::EXIT_USAGE_ERROR)->toBe(64);
    expect(ExitCodes::EXIT_DATAERR)->toBe(65);
    expect(ExitCodes::EXIT_NOINPUT)->toBe(66);
    expect(ExitCodes::EXIT_NOUSER)->toBe(67);
    expect(ExitCodes::EXIT_NOHOST)->toBe(68);
    expect(ExitCodes::EXIT_UNAVAILABLE)->toBe(69);
    expect(ExitCodes::EXIT_SOFTWARE)->toBe(70);
    expect(ExitCodes::EXIT_OSERR)->toBe(71);
    expect(ExitCodes::EXIT_OSFILE)->toBe(72);
    expect(ExitCodes::EXIT_CANTCREAT)->toBe(73);
    expect(ExitCodes::EXIT_IOERR)->toBe(74);
    expect(ExitCodes::EXIT_TEMPFAIL)->toBe(75);
    expect(ExitCodes::EXIT_PROTOCOL)->toBe(76);
    expect(ExitCodes::EXIT_NOPERM)->toBe(77);
    expect(ExitCodes::EXIT_CONFIG)->toBe(78);

    // Custom application codes
    expect(ExitCodes::EXIT_LOCKED)->toBe(80);
    expect(ExitCodes::EXIT_TIMEOUT)->toBe(81);
    expect(ExitCodes::EXIT_CANCELLED)->toBe(82);
    expect(ExitCodes::EXIT_VALIDATION)->toBe(83);
    expect(ExitCodes::EXIT_MISSING_DEPS)->toBe(84);
    expect(ExitCodes::EXIT_NOT_FOUND)->toBe(85);
    expect(ExitCodes::EXIT_CONFLICT)->toBe(86);
    expect(ExitCodes::EXIT_ROLLBACK)->toBe(87);
    expect(ExitCodes::EXIT_PARTIAL)->toBe(88);

    // Signal-based codes
    expect(ExitCodes::EXIT_COMMAND_NOT_FOUND)->toBe(127);
    expect(ExitCodes::EXIT_SIGINT)->toBe(130);  // 128 + 2 (SIGINT)
    expect(ExitCodes::EXIT_SIGTERM)->toBe(143); // 128 + 15 (SIGTERM)
});

test('all exit codes have unique values', function () {
    $reflection = new \ReflectionClass(ExitCodes::class);
    $constants = $reflection->getConstants();

    $exitCodes = [];
    foreach ($constants as $name => $value) {
        if (str_starts_with($name, 'EXIT_')) {
            expect($exitCodes)->not->toContain($value, "Duplicate exit code value {$value} for {$name}");
            $exitCodes[$name] = $value;
        }
    }
});

test('getExitCodeDescription returns correct descriptions', function () {
    $descriptions = [
        0 => 'Success',
        1 => 'General failure',
        2 => 'Incorrect usage',
        64 => 'Command line usage error',
        65 => 'Data format error',
        66 => 'Cannot open input',
        67 => 'Addressee unknown',
        68 => 'Host name unknown',
        69 => 'Service unavailable',
        70 => 'Internal software error',
        71 => 'System error',
        72 => 'Critical OS file missing',
        73 => 'Cannot create output',
        74 => 'I/O error',
        75 => 'Temporary failure',
        76 => 'Remote error',
        77 => 'Permission denied',
        78 => 'Configuration error',
        80 => 'Resource locked',
        81 => 'Operation timed out',
        82 => 'Cancelled by user',
        83 => 'Validation failed',
        84 => 'Missing dependencies',
        85 => 'Resource not found',
        86 => 'Resource conflict',
        87 => 'Operation rolled back',
        88 => 'Operation partially completed',
        127 => 'Command not found',
        130 => 'Interrupted by Ctrl+C',
        143 => 'Terminated',
        999 => 'Unknown error (code: 999)',
    ];

    foreach ($descriptions as $code => $expected) {
        expect(Command::getExitCodeDescription($code))->toBe($expected);
    }
});

test('all defined exit codes have descriptions', function () {
    $reflection = new \ReflectionClass(ExitCodes::class);
    $constants = $reflection->getConstants();

    foreach ($constants as $name => $value) {
        if (str_starts_with($name, 'EXIT_')) {
            $description = Command::getExitCodeDescription($value);
            expect($description)->not->toMatch('/^Unknown error \(code: \d+\)$/');
        }
    }
});

test('returnWithCode returns correct code and outputs message', function () {
    ob_start();
    $code = $this->command->testReturnWithCode(ExitCodes::EXIT_SUCCESS, 'Success message');
    $output = ob_get_clean();

    expect($code)->toBe(ExitCodes::EXIT_SUCCESS);
    expect($output)->toContain('Success message');

    ob_start();
    $code = $this->command->testReturnWithCode(ExitCodes::EXIT_FAILURE, 'Error message');
    $output = ob_get_clean();

    expect($code)->toBe(ExitCodes::EXIT_FAILURE);
    expect($output)->toContain('Error message');
});

test('returnSuccess returns success code', function () {
    ob_start();
    $code = $this->command->testReturnSuccess('Operation successful');
    $output = ob_get_clean();

    expect($code)->toBe(ExitCodes::EXIT_SUCCESS);
    expect($output)->toContain('Operation successful');
});

test('returnError returns error code', function () {
    ob_start();
    $code = $this->command->testReturnError('Something went wrong', ExitCodes::EXIT_CONFIG);
    $output = ob_get_clean();

    expect($code)->toBe(ExitCodes::EXIT_CONFIG);
    expect($output)->toContain('Something went wrong');
});

test('mapExceptionToExitCode maps exceptions correctly', function () {
    $mappings = [
        // Standard PHP exceptions
        ['exception' => new \InvalidArgumentException, 'expected' => ExitCodes::EXIT_USAGE],
        ['exception' => new \BadMethodCallException, 'expected' => ExitCodes::EXIT_SOFTWARE],
        ['exception' => new \DomainException, 'expected' => ExitCodes::EXIT_CONFIG],
        ['exception' => new \RangeException, 'expected' => ExitCodes::EXIT_DATAERR],
        ['exception' => new \UnexpectedValueException, 'expected' => ExitCodes::EXIT_DATAERR],
        ['exception' => new \LengthException, 'expected' => ExitCodes::EXIT_DATAERR],
        ['exception' => new \OutOfBoundsException, 'expected' => ExitCodes::EXIT_DATAERR],
        ['exception' => new \OverflowException, 'expected' => ExitCodes::EXIT_TEMPFAIL],
        ['exception' => new \UnderflowException, 'expected' => ExitCodes::EXIT_TEMPFAIL],
        ['exception' => new \RuntimeException, 'expected' => ExitCodes::EXIT_SOFTWARE],
        ['exception' => new \LogicException, 'expected' => ExitCodes::EXIT_SOFTWARE],
        ['exception' => new \Exception, 'expected' => ExitCodes::EXIT_FAILURE],
    ];

    foreach ($mappings as $mapping) {
        $result = $this->command->testMapExceptionToExitCode($mapping['exception']);
        expect($result)->toBe(
            $mapping['expected'],
            'Failed for '.get_class($mapping['exception'])
        );
    }
});

test('handleException returns appropriate exit code', function () {
    $exception = new \RuntimeException('Test error message');

    ob_start();
    $code = $this->command->testHandleException($exception);
    $output = ob_get_clean();

    expect($code)->toBe(ExitCodes::EXIT_SOFTWARE);
    expect($output)->toContain('Test error message');
});

test('handleException includes stack trace in debug mode', function () {
    // Create a test command with debug output
    $command = new class extends Command
    {
        protected string $name = 'test:debug';

        protected string $description = 'Test debug command';

        public function execute(array $input, Output $output): int
        {
            return self::EXIT_SUCCESS;
        }

        public function testHandleExceptionDebug(\Throwable $exception): int
        {
            // Create an output mock that reports debug mode
            $this->output = new class extends Output
            {
                public function isDebug(): bool
                {
                    return true;
                }
            };

            return $this->handleException($exception);
        }
    };

    $exception = new \RuntimeException('Debug test error');

    ob_start();
    $code = $command->testHandleExceptionDebug($exception);
    $output = ob_get_clean();

    expect($code)->toBe(ExitCodes::EXIT_SOFTWARE);
    expect($output)->toContain('Debug test error');
    expect($output)->toContain('Stack trace:');
});

test('setOutput method works correctly', function () {
    // Create a test command that exposes setOutput
    $command = new class extends Command
    {
        protected string $name = 'test:output';

        protected string $description = 'Test output command';

        public function execute(array $input, Output $output): int
        {
            return self::EXIT_SUCCESS;
        }

        public function testSetOutput(Output $output): self
        {
            return $this->setOutput($output);
        }

        public function getOutput(): ?Output
        {
            return $this->output;
        }
    };

    $output = new Output;
    $result = $command->testSetOutput($output);

    // Test that it returns self for chaining
    expect($result)->toBe($command);
    // Test that output was set
    expect($command->getOutput())->toBe($output);
});

test('Command class implements ExitCodes interface', function () {
    $reflection = new \ReflectionClass(Command::class);
    expect($reflection->implementsInterface(ExitCodes::class))->toBeTrue();
});

test('exit codes follow POSIX standards', function () {
    // Success should be 0
    expect(ExitCodes::EXIT_SUCCESS)->toBe(0);

    // General errors should be 1-2
    expect(ExitCodes::EXIT_FAILURE)->toBeLessThanOrEqual(2);
    expect(ExitCodes::EXIT_USAGE)->toBeLessThanOrEqual(2);

    // POSIX reserved range (64-113) should be used for specific errors
    $posixCodes = [
        ExitCodes::EXIT_USAGE_ERROR,
        ExitCodes::EXIT_DATAERR,
        ExitCodes::EXIT_NOINPUT,
        ExitCodes::EXIT_NOPERM,
        ExitCodes::EXIT_CONFIG,
    ];

    foreach ($posixCodes as $code) {
        expect($code)->toBeGreaterThanOrEqual(64);
        expect($code)->toBeLessThanOrEqual(113);
    }

    // Signal-based codes follow 128+n convention
    expect(ExitCodes::EXIT_SIGINT)->toBe(128 + 2);   // SIGINT = 2
    expect(ExitCodes::EXIT_SIGTERM)->toBe(128 + 15); // SIGTERM = 15
});
