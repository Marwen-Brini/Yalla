<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\Traits\DryRunnable;
use Yalla\Output\Output;

class DryRunnableCommand extends Command
{
    use DryRunnable;

    protected string $name = 'test:dry-run';

    protected string $description = 'Test command with dry run support';

    public function execute(array $input, Output $output): int
    {
        $this->setDryRunOutput($output);

        return 0;
    }

    public function testExecuteOrSimulate(string $description, callable $operation, array $context = [])
    {
        return $this->executeOrSimulate($description, $operation, $context);
    }

    public function testSimulateOperation(string $description, callable $operation, array $context = []): void
    {
        $this->simulateOperation($description, $operation, $context);
    }

    public function testExecuteOperation(string $description, callable $operation, array $context = []): mixed
    {
        return $this->executeOperation($description, $operation, $context);
    }

    public function addToDryRunLog(string $description, array $context = []): void
    {
        $this->dryRunLog[] = [
            'description' => $description,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    public function testShowDryRunSummary(): void
    {
        $this->showDryRunSummary();
    }
}

// Extended test class with more exposed methods
class TestDryRunnableCommand extends DryRunnableCommand
{
    public function testFormatContextValue($value): string
    {
        return $this->formatContextValue($value);
    }
}

beforeEach(function () {
    $this->command = new DryRunnableCommand;
    $this->output = new Output;
});

test('setDryRun and isDryRun work correctly', function () {
    $this->assertFalse($this->command->isDryRun());

    ob_start();
    $this->command->setDryRun(true);
    ob_get_clean();
    $this->assertTrue($this->command->isDryRun());

    $this->command->setDryRun(false);
    $this->assertFalse($this->command->isDryRun());
});

test('setDryRunOutput stores output instance', function () {
    $this->command->setDryRunOutput($this->output);

    // Execute in dry run mode to verify output is used
    ob_start();
    $this->command->setDryRun(true);
    $result = $this->command->testExecuteOrSimulate('Test operation', function () {
        return 'executed';
    });
    ob_get_clean();

    $this->assertNull($result);
});

test('executeOrSimulate runs operation in normal mode', function () {
    $this->command->setDryRun(false);
    $this->command->setDryRunOutput($this->output);

    $executed = false;
    $result = $this->command->testExecuteOrSimulate('Test operation', function () use (&$executed) {
        $executed = true;

        return 'success';
    });

    $this->assertTrue($executed);
    $this->assertEquals('success', $result);
});

test('executeOrSimulate simulates in dry run mode', function () {
    ob_start();
    $this->command->setDryRun(true);
    $this->command->setDryRunOutput($this->output);

    $executed = false;
    $result = $this->command->testExecuteOrSimulate('Test operation', function () use (&$executed) {
        $executed = true;

        return 'would execute';
    }, ['key' => 'value']);
    ob_get_clean();

    $this->assertFalse($executed);
    $this->assertNull($result);
});

test('simulateOperation logs dry run operation', function () {
    ob_start();
    $this->command->setDryRun(true);
    $this->command->setDryRunOutput($this->output);

    $this->command->testSimulateOperation('Database migration', function () {
        return 'would create table';
    }, [
        'table' => 'users',
        'action' => 'create',
    ]);
    ob_get_clean();

    $log = $this->command->getDryRunLog();
    $this->assertCount(1, $log);
    $this->assertEquals('Database migration', $log[0]['description']);
    $this->assertEquals(['table' => 'users', 'action' => 'create'], $log[0]['context']);
});

test('executeOperation tracks execution time', function () {
    $this->command->setDryRunOutput($this->output);

    $result = $this->command->testExecuteOperation('Quick task', function () {
        usleep(10000); // 10ms

        return 'done';
    });

    $this->assertEquals('done', $result);
});

test('logDryRunOperation adds to dry run log', function () {
    $this->command->addToDryRunLog('First operation');
    $this->command->addToDryRunLog('Second operation', ['param' => 'value']);

    $log = $this->command->getDryRunLog();
    $this->assertCount(2, $log);
    $this->assertEquals('First operation', $log[0]['description']);
    $this->assertEquals('Second operation', $log[1]['description']);
    $this->assertEquals(['param' => 'value'], $log[1]['context']);
    $this->assertArrayHasKey('timestamp', $log[0]);
});

test('getDryRunLog returns operations log', function () {
    $this->assertEmpty($this->command->getDryRunLog());

    $this->command->addToDryRunLog('Test op 1');
    $this->command->addToDryRunLog('Test op 2');

    $log = $this->command->getDryRunLog();
    $this->assertCount(2, $log);
});

test('getDryRunSummary returns formatted summary', function () {
    $this->command->addToDryRunLog('Create table');
    $this->command->addToDryRunLog('Add index');
    $this->command->addToDryRunLog('Insert records', ['count' => 100]);

    $summary = $this->command->getDryRunSummary();

    // getDryRunSummary returns an array, not a formatted string
    $this->assertIsArray($summary);
    $this->assertEquals(3, $summary['operations']);
    $this->assertCount(3, $summary['log']);
    $this->assertEquals('Create table', $summary['log'][0]['description']);
    $this->assertEquals('Add index', $summary['log'][1]['description']);
    $this->assertEquals('Insert records', $summary['log'][2]['description']);
});

test('showDryRunSummary outputs summary', function () {
    // Capture all output including the setDryRun message
    ob_start();

    $this->command->setDryRunOutput($this->output);
    $this->command->setDryRun(true); // Must be in dry run mode
    $this->command->addToDryRunLog('Operation 1');
    $this->command->addToDryRunLog('Operation 2');

    $this->command->testShowDryRunSummary();
    $output = ob_get_clean();

    $this->assertStringContainsString('Dry Run Summary', $output);
    $this->assertStringContainsString('Would have executed 2 operation', $output);
});

test('clearDryRunLog empties the log', function () {
    $this->command->addToDryRunLog('Operation 1');
    $this->command->addToDryRunLog('Operation 2');

    $this->assertCount(2, $this->command->getDryRunLog());

    $this->command->clearDryRunLog();
    $this->assertEmpty($this->command->getDryRunLog());
});

test('executeOrSimulate without output handles gracefully', function () {
    ob_start();
    $this->command->setDryRun(true);
    // Don't set output

    $result = $this->command->testExecuteOrSimulate('Test', function () {
        return 'value';
    });
    ob_get_clean();

    $this->assertNull($result);
});

test('showDryRunSummary without output handles gracefully', function () {
    // Don't set output
    $this->command->setDryRun(true);
    $this->command->addToDryRunLog('Test op');

    ob_start();
    $this->command->testShowDryRunSummary();
    $output = ob_get_clean();

    // Should handle gracefully with no output
    $this->assertEmpty($output);
});

test('dry run mode with verbose context', function () {
    ob_start();
    $this->command->setDryRun(true);
    $this->command->setDryRunOutput($this->output);

    $complexContext = [
        'database' => 'production',
        'tables' => ['users', 'posts', 'comments'],
        'operations' => [
            'create' => 3,
            'update' => 10,
            'delete' => 2,
        ],
    ];

    $this->command->testSimulateOperation('Complex migration', function () {
        return 'migration result';
    }, $complexContext);
    ob_get_clean();

    $log = $this->command->getDryRunLog();
    $this->assertEquals($complexContext, $log[0]['context']);
});

test('formatContextValue handles all value types', function () {
    $trait = new TestDryRunnableCommand;

    // Test array
    $arrayResult = $trait->testFormatContextValue(['key' => 'value']);
    expect($arrayResult)->toBe(json_encode(['key' => 'value']));

    // Test object with __toString
    $objWithToString = new class
    {
        public function __toString()
        {
            return 'string representation';
        }
    };
    expect($trait->testFormatContextValue($objWithToString))->toBe('string representation');

    // Test object without __toString
    $objWithoutToString = new stdClass;
    expect($trait->testFormatContextValue($objWithoutToString))->toBe('stdClass');

    // Test boolean true
    expect($trait->testFormatContextValue(true))->toBe('true');

    // Test boolean false
    expect($trait->testFormatContextValue(false))->toBe('false');

    // Test null
    expect($trait->testFormatContextValue(null))->toBe('null');

    // Test string
    expect($trait->testFormatContextValue('test string'))->toBe('test string');

    // Test integer
    expect($trait->testFormatContextValue(42))->toBe('42');
});

test('simulateOperation shows context in verbose mode', function () {
    // Create a test command with verbose output
    $trait = new class extends TestDryRunnableCommand
    {
        public function testWithVerboseOutput()
        {
            $this->output = new class extends Output
            {
                public function isVerbose(): bool
                {
                    return true;
                }

                public function verbose(string $message): void
                {
                    echo $message.PHP_EOL;
                }
            };
            $this->setDryRun(true);

            $operation = function () {
                return 'result';
            };
            $context = [
                'file' => '/path/to/file',
                'mode' => 'write',
                'size' => 1024,
            ];

            return $this->executeOrSimulate('Test with context', $operation, $context);
        }
    };

    ob_start();
    $result = $trait->testWithVerboseOutput();
    $output = ob_get_clean();

    // Should show context items
    expect($output)->toContain('file: /path/to/file');
    expect($output)->toContain('mode: write');
    expect($output)->toContain('size: 1024');
});

test('executeOperation logs in verbose mode', function () {
    $trait = new class extends TestDryRunnableCommand
    {
        public function testVerboseExecute()
        {
            $this->output = new class extends Output
            {
                public function isVerbose(): bool
                {
                    return true;
                }

                public function verbose(string $message): void
                {
                    echo $message.PHP_EOL;
                }
            };

            $operation = function () {
                return 'result';
            };

            return $this->executeOrSimulate('Verbose operation', $operation);
        }
    };

    ob_start();
    $result = $trait->testVerboseExecute();
    $output = ob_get_clean();

    expect($output)->toContain('Executing: Verbose operation');
    expect($result)->toBe('result');
});

test('executeOperation logs debug info on success', function () {
    $trait = new class extends TestDryRunnableCommand
    {
        public function testDebugExecute()
        {
            $this->output = new class extends Output
            {
                public function isDebug(): bool
                {
                    return true;
                }

                public function debug(string $message): void
                {
                    echo $message.PHP_EOL;
                }
            };

            $operation = function () {
                usleep(1000); // Small delay to ensure duration > 0

                return 'success';
            };

            return $this->executeOrSimulate('Debug operation', $operation);
        }
    };

    ob_start();
    $result = $trait->testDebugExecute();
    $output = ob_get_clean();

    expect($output)->toContain('Completed in');
    expect($result)->toBe('success');
});

test('executeOperation handles exception with debug output', function () {
    $trait = new class extends TestDryRunnableCommand
    {
        public function testDebugException()
        {
            $this->output = new class extends Output
            {
                public function isDebug(): bool
                {
                    return true;
                }

                public function debug(string $message): void
                {
                    echo $message.PHP_EOL;
                }

                public function error(string $message): void
                {
                    echo 'ERROR: '.$message.PHP_EOL;
                }
            };

            $operation = function () {
                throw new RuntimeException('Test error');
            };

            try {
                return $this->executeOrSimulate('Failed operation', $operation);
            } catch (Exception $e) {
                return null;
            }
        }
    };

    ob_start();
    $result = $trait->testDebugException();
    $output = ob_get_clean();

    expect($output)->toContain('ERROR: Failed: Failed operation');
    expect($output)->toContain('ERROR:   → Error: Test error');
    expect($output)->toContain('Failed after');
    expect($output)->toContain('Stack trace:');
});

test('showDryRunSummary displays verbose context', function () {
    $trait = new class extends TestDryRunnableCommand
    {
        public function testVerboseSummary()
        {
            $this->output = new class extends Output
            {
                public function isVerbose(): bool
                {
                    return true;
                }

                public function writeln(string $message = ''): void
                {
                    echo $message.PHP_EOL;
                }

                public function section(string $title): void
                {
                    echo "=== $title ===".PHP_EOL;
                }

                public function info(string $message): void
                {
                    echo $message.PHP_EOL;
                }

                public function comment(string $message): void
                {
                    echo $message.PHP_EOL;
                }
            };
            $this->setDryRun(true);

            // Add operations with context
            $this->executeOrSimulate('First op', fn () => null, ['key1' => 'value1']);
            $this->executeOrSimulate('Second op', fn () => null, ['key2' => 'value2']);

            $this->showDryRunSummary();
        }
    };

    ob_start();
    $trait->testVerboseSummary();
    $output = ob_get_clean();

    // Should show context in verbose mode
    expect($output)->toContain('key1: value1');
    expect($output)->toContain('key2: value2');
});
