<?php

declare(strict_types=1);

use Yalla\Output\Output;

beforeEach(function () {
    $this->output = new class extends Output
    {
        public array $outputBuffer = [];

        protected bool $supportsColors = true; // Force color support

        public function __construct()
        {
            // Override parent constructor to force color support
            // Don't call parent::__construct() to avoid color detection
        }

        public function write(string $message, bool $newline = false): void
        {
            $this->outputBuffer[] = ['message' => $message, 'newline' => $newline];
            if ($newline) {
                $this->outputBuffer[] = ['message' => PHP_EOL, 'newline' => false];
            }
        }

        public function writeln(string $message): void
        {
            $this->write($message, true);
        }

        public function getOutput(): string
        {
            $result = '';
            foreach ($this->outputBuffer as $item) {
                $result .= $item['message'];
            }

            return $result;
        }

        public function clearBuffer(): void
        {
            $this->outputBuffer = [];
        }

        public function color(string $text, string $color): string
        {
            // Always apply colors for testing
            return $color.$text.self::RESET;
        }
    };
});

test('box method draws a box around content', function () {
    $this->output->box('Hello World', Output::CYAN);

    $output = $this->output->getOutput();

    expect($output)->toContain('╔═');
    expect($output)->toContain('═╗');
    expect($output)->toContain('║ Hello World');
    expect($output)->toContain('║');
    expect($output)->toContain('╚═');
    expect($output)->toContain('═╝');
});

test('box method handles multi-line content', function () {
    $this->output->box("Line 1\nLonger Line 2\nL3", Output::GREEN);

    $output = $this->output->getOutput();

    expect($output)->toContain('╔═');
    expect($output)->toContain('═╗');
    expect($output)->toContain('║ Line 1');
    expect($output)->toContain('║ Longer Line 2');
    expect($output)->toContain('║ L3');
    expect($output)->toContain('╚═');
    expect($output)->toContain('═╝');
});

test('progressBar displays progress correctly', function () {
    $this->output->clearBuffer();
    $this->output->progressBar(5, 10);

    $output = $this->output->getOutput();

    // Progress bar should show 50% filled
    expect($output)->toContain('[');
    expect($output)->toContain(']');
    expect($output)->toContain('50%');
    expect($output)->toContain('█'); // Filled portion
    expect($output)->toContain('░'); // Empty portion
});

test('progressBar handles complete progress', function () {
    $this->output->clearBuffer();
    $this->output->progressBar(10, 10);

    $output = $this->output->getOutput();

    expect($output)->toContain('[██████████████████████████████████████████████████] 100%');
    expect($output)->toContain(PHP_EOL); // Should add newline when complete
});

test('progressBar handles zero total', function () {
    $this->output->clearBuffer();
    $this->output->progressBar(0, 0);

    $output = $this->output->getOutput();

    expect($output)->toBe(''); // Should return early without output
});

test('progressBar with custom width', function () {
    $this->output->clearBuffer();
    $this->output->progressBar(1, 2, 10);

    $output = $this->output->getOutput();

    expect($output)->toContain('[█████░░░░░] 50%');
});

test('spinner displays correct frame', function () {
    $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];

    for ($i = 0; $i < 10; $i++) {
        $this->output->clearBuffer();
        $this->output->spinner($i);

        $output = $this->output->getOutput();
        expect($output)->toBe("\r".$frames[$i]);
    }
});

test('spinner wraps around frames', function () {
    $this->output->clearBuffer();
    $this->output->spinner(10); // Should wrap to first frame

    $output = $this->output->getOutput();
    expect($output)->toBe("\r⠋");

    $this->output->clearBuffer();
    $this->output->spinner(15); // Should wrap to 6th frame

    $output = $this->output->getOutput();
    expect($output)->toBe("\r⠴");
});

test('dim method outputs dimmed text', function () {
    $this->output->clearBuffer();
    $this->output->dim('Dimmed text');

    $output = $this->output->getOutput();

    expect($output)->toContain(Output::DIM.'Dimmed text'.Output::RESET);
});

test('bold method outputs bold text', function () {
    $this->output->clearBuffer();
    $this->output->bold('Bold text');

    $output = $this->output->getOutput();

    expect($output)->toContain(Output::BOLD.'Bold text'.Output::RESET);
});

test('underline method outputs underlined text', function () {
    $this->output->clearBuffer();
    $this->output->underline('Underlined text');

    $output = $this->output->getOutput();

    expect($output)->toContain(Output::UNDERLINE.'Underlined text'.Output::RESET);
});

test('section method returns OutputSection instance', function () {
    $this->output->clearBuffer();
    $section = $this->output->section('Test Section');

    expect($section)->toBeInstanceOf(\Yalla\Output\OutputSection::class);
    expect($section->getTitle())->toBe('Test Section');
});

test('tree method displays flat array', function () {
    $this->output->clearBuffer();
    $this->output->tree([
        'file1.php',
        'file2.php',
        'file3.php',
    ]);

    $output = $this->output->getOutput();

    // Tree shows array indices and values
    expect($output)->toContain('0: ');
    expect($output)->toContain('file1.php');
    expect($output)->toContain('1: ');
    expect($output)->toContain('file2.php');
    expect($output)->toContain('2: ');
    expect($output)->toContain('file3.php');
});

test('tree method displays nested structure', function () {
    $this->output->clearBuffer();
    $this->output->tree([
        'src' => [
            'file1.php',
            'file2.php',
        ],
    ]);

    $output = $this->output->getOutput();

    // Check for proper structure
    expect($output)->toContain('src');
    expect($output)->toContain('├── 0: ');
    expect($output)->toContain('file1.php');
    expect($output)->toContain('└── 1: ');
    expect($output)->toContain('file2.php');
});

test('tree method handles mixed content', function () {
    $this->output->clearBuffer();
    $this->output->tree([
        'config' => 'app.php',
        'routes' => [
            'web.php',
            'api.php',
        ],
    ]);

    $output = $this->output->getOutput();

    expect($output)->toContain('config: ');
    expect($output)->toContain('app.php');
    expect($output)->toContain('routes');
    expect($output)->toContain('├── 0: ');
    expect($output)->toContain('web.php');
    expect($output)->toContain('└── 1: ');
    expect($output)->toContain('api.php');
});

test('tree method handles deeply nested structures', function () {
    $this->output->clearBuffer();
    $this->output->tree([
        'level1' => [
            'level2' => [
                'level3' => [
                    'deep.file',
                ],
            ],
        ],
    ]);

    $output = $this->output->getOutput();

    expect($output)->toContain('level1');
    expect($output)->toContain('level2');
    expect($output)->toContain('level3');
    expect($output)->toContain('deep.file');
    expect($output)->toContain('└──'); // Check for tree structure
});
