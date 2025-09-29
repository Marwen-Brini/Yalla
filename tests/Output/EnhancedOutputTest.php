<?php

declare(strict_types=1);

use Yalla\Output\Output;
use Yalla\Output\OutputSection;

// Helper function to create testable output instance
function createTestOutput() {
    return new class extends Output {
        private array $buffer = [];

        public function write(string $message, bool $newline = false): void
        {
            $this->buffer[] = $message . ($newline ? PHP_EOL : '');
        }

        public function getBuffer(): array
        {
            return $this->buffer;
        }

        public function clearBuffer(): void
        {
            $this->buffer = [];
        }

        protected function hasColorSupport(): bool
        {
            return false; // Disable colors for testing
        }
    };
}

beforeEach(function () {
    $this->output = createTestOutput();
});

test('semantic output methods with icons', function () {
    $this->output->success('Operation successful');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('✅ Operation successful');

    $this->output->clearBuffer();
    $this->output->error('Operation failed');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('❌ Operation failed');

    $this->output->clearBuffer();
    $this->output->warning('Be careful');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('⚠️  Be careful');

    $this->output->clearBuffer();
    $this->output->info('Information');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('ℹ️  Information');

    $this->output->clearBuffer();
    $this->output->comment('A comment');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('💡 A comment');

    $this->output->clearBuffer();
    $this->output->question('A question?');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('❓ A question?');

    $this->output->clearBuffer();
    $this->output->note('A note');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('📝 A note');

    $this->output->clearBuffer();
    $this->output->caution('Caution!');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('⚡ Caution!');
});

test('verbosity levels', function () {
    // Test default verbosity (NORMAL)
    expect($this->output->getVerbosity())->toBe(Output::VERBOSITY_NORMAL);
    expect($this->output->isQuiet())->toBeFalse();
    expect($this->output->isVerbose())->toBeFalse();
    expect($this->output->isDebug())->toBeFalse();
    expect($this->output->isTrace())->toBeFalse();

    // Test QUIET
    $this->output->setVerbosity(Output::VERBOSITY_QUIET);
    expect($this->output->isQuiet())->toBeTrue();
    expect($this->output->isVerbose())->toBeFalse();

    // Test VERBOSE
    $this->output->setVerbosity(Output::VERBOSITY_VERBOSE);
    expect($this->output->isVerbose())->toBeTrue();
    expect($this->output->isDebug())->toBeFalse();
    expect($this->output->isTrace())->toBeFalse();

    // Test DEBUG
    $this->output->setVerbosity(Output::VERBOSITY_DEBUG);
    expect($this->output->isVerbose())->toBeTrue();
    expect($this->output->isDebug())->toBeTrue();
    expect($this->output->isTrace())->toBeFalse();

    // Test TRACE
    $this->output->setVerbosity(Output::VERBOSITY_TRACE);
    expect($this->output->isVerbose())->toBeTrue();
    expect($this->output->isDebug())->toBeTrue();
    expect($this->output->isTrace())->toBeTrue();
});

test('conditional output', function () {
    // Test verbose output
    $this->output->setVerbosity(Output::VERBOSITY_NORMAL);
    $this->output->verbose('This should not appear');
    $buffer = $this->output->getBuffer();
    expect($buffer)->toBeEmpty();

    $this->output->setVerbosity(Output::VERBOSITY_VERBOSE);
    $this->output->verbose('This should appear');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('This should appear');

    // Test debug output
    $this->output->clearBuffer();
    $this->output->setVerbosity(Output::VERBOSITY_VERBOSE);
    $this->output->debug('Debug message');
    $buffer = $this->output->getBuffer();
    expect($buffer)->toBeEmpty(); // Should not appear in verbose mode

    $this->output->setVerbosity(Output::VERBOSITY_DEBUG);
    $this->output->debug('Debug message');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('🔍 Debug message');

    // Test trace output
    $this->output->clearBuffer();
    $this->output->setVerbosity(Output::VERBOSITY_DEBUG);
    $this->output->trace('Trace message');
    $buffer = $this->output->getBuffer();
    expect($buffer)->toBeEmpty(); // Should not appear in debug mode

    $this->output->setVerbosity(Output::VERBOSITY_TRACE);
    $this->output->trace('Trace message');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toContain('[TRACE] Trace message');
});

test('timestamped output', function () {
    expect($this->output->hasTimestamps())->toBeFalse();

    // Enable timestamps
    $this->output->withTimestamps(true);
    expect($this->output->hasTimestamps())->toBeTrue();

    // Test custom format
    $this->output->setTimestampFormat('H:i:s');

    $this->output->writeln('Test message');
    $buffer = $this->output->getBuffer();

    // Check timestamp is present (format: [HH:MM:SS] Test message)
    expect($buffer[0])->toMatch('/\[\d{2}:\d{2}:\d{2}\] Test message/');

    // Disable timestamps
    $this->output->clearBuffer();
    $this->output->withTimestamps(false);
    $this->output->writeln('No timestamp');
    $buffer = $this->output->getBuffer();
    expect($buffer[0])->toBe("No timestamp" . PHP_EOL);
});

test('sql query logging', function () {
    $this->output->setVerbosity(Output::VERBOSITY_DEBUG);

    $query = 'SELECT * FROM users WHERE id = ? AND status = ?';
    $bindings = [123, 'active'];

    $this->output->sql($query, $bindings);
    $buffer = $this->output->getBuffer();

    expect($buffer[0])->toContain("SELECT * FROM users WHERE id = 123 AND status = 'active'");
});

test('grouped output', function () {
    $this->output->group('Test Group', function(Output $output) {
        $output->writeln('Inside group');
        $output->writeln('Another line');
    });

    $buffer = $this->output->getBuffer();
    expect($buffer)->toHaveCount(6); // Empty line, section title, empty line, two content lines, empty line
    expect($buffer[1])->toContain('Test Group');
    expect($buffer[3])->toContain('Inside group');
    expect($buffer[4])->toContain('Another line');
});

test('output section', function () {
    $section = $this->output->createSection('Test Section');
    expect($section)->toBeInstanceOf(OutputSection::class);
    expect($section->getTitle())->toBe('Test Section');

    $section->writeln('First line');
    $section->writeln('Second line');
    expect($section->getContent())->toBe(['First line', 'Second line']);

    $section->clear();
    expect($section->getContent())->toBeEmpty();
    expect($section->isCleared())->toBeTrue();

    $section->overwrite('New content');
    expect($section->getContent())->toBe(['New content']);
});

test('method chaining', function () {
    $result = $this->output
        ->setVerbosity(Output::VERBOSITY_DEBUG)
        ->withTimestamps(true)
        ->setTimestampFormat('Y-m-d');

    expect($result)->toBeInstanceOf(Output::class);
    expect($this->output->isDebug())->toBeTrue();
    expect($this->output->hasTimestamps())->toBeTrue();
});

test('color constants', function () {
    expect(Output::GRAY)->toBe("\033[90m");
    expect(Output::DARK_GRAY)->toBe("\033[90m");
    expect(Output::BRIGHT_YELLOW)->toBe("\033[93m");
});

test('empty message with timestamp', function () {
    $this->output->withTimestamps(true);
    $this->output->writeln('');
    $buffer = $this->output->getBuffer();
    // Empty messages should not get timestamps
    expect($buffer[0])->toBe(PHP_EOL);
});

test('interpolate query', function () {
    $this->output->setVerbosity(Output::VERBOSITY_DEBUG);

    $tests = [
        [
            'query' => 'SELECT * FROM users WHERE id = ?',
            'bindings' => [42],
            'expected' => 'SELECT * FROM users WHERE id = 42'
        ],
        [
            'query' => 'INSERT INTO posts (title, content) VALUES (?, ?)',
            'bindings' => ['Test Title', 'Test Content'],
            'expected' => "INSERT INTO posts (title, content) VALUES ('Test Title', 'Test Content')"
        ],
        [
            'query' => 'UPDATE users SET active = ? WHERE id = ?',
            'bindings' => [true, 100],
            'expected' => 'UPDATE users SET active = 1 WHERE id = 100'
        ],
    ];

    foreach ($tests as $test) {
        $this->output->clearBuffer();
        $this->output->sql($test['query'], $test['bindings']);
        $buffer = $this->output->getBuffer();
        expect($buffer[0])->toContain($test['expected']);
    }
});