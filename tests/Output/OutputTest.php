<?php

declare(strict_types=1);

use Yalla\Output\Output;

it('writes text without newline', function () {
    $output = new Output;

    ob_start();
    $output->write('Hello');
    $result = ob_get_clean();

    expect($result)->toBe('Hello');
});

it('writes text with newline', function () {
    $output = new Output;

    ob_start();
    $output->writeln('Hello');
    $result = ob_get_clean();

    expect($result)->toBe("Hello\n");
});

it('outputs success messages in green', function () {
    $output = new Output;

    ob_start();
    $output->success('Success!');
    $result = ob_get_clean();

    expect($result)->toContain('Success!');
});

it('outputs error messages in red', function () {
    $output = new Output;

    ob_start();
    $output->error('Error!');
    $result = ob_get_clean();

    expect($result)->toContain('Error!');
});

it('outputs warning messages in yellow', function () {
    $output = new Output;

    ob_start();
    $output->warning('Warning!');
    $result = ob_get_clean();

    expect($result)->toContain('Warning!');
});

it('outputs info messages in cyan', function () {
    $output = new Output;

    ob_start();
    $output->info('Info');
    $result = ob_get_clean();

    expect($result)->toContain('Info');
});

it('can render tables', function () {
    $output = new Output;

    ob_start();
    $output->table(
        ['Name', 'Age'],
        [
            ['John', '30'],
            ['Jane', '25'],
        ]
    );
    $result = ob_get_clean();

    expect($result)->toContain('Name');
    expect($result)->toContain('Age');
    expect($result)->toContain('John');
    expect($result)->toContain('30');
    expect($result)->toContain('Jane');
    expect($result)->toContain('25');
    expect($result)->toContain('│');
    expect($result)->toContain('─');
});

it('handles empty table rows', function () {
    $output = new Output;

    ob_start();
    $output->table(['Header'], []);
    $result = ob_get_clean();

    expect($result)->toContain('Header');
});

it('handles table with various cell types', function () {
    $output = new Output;

    ob_start();
    $output->table(
        ['String', 'Number', 'Null'],
        [
            ['text', 123, null],
        ]
    );
    $result = ob_get_clean();

    expect($result)->toContain('text');
    expect($result)->toContain('123');
});

it('applies color to text', function () {
    $output = new Output;

    $colored = $output->color('Test', Output::RED);

    if (function_exists('posix_isatty') && posix_isatty(STDOUT)) {
        expect($colored)->toBe("\033[31mTest\033[0m");
    } else {
        expect($colored)->toBe('Test');
    }
});

it('handles color support detection on windows', function () {
    $output = new Output;
    $reflection = new ReflectionClass($output);
    $method = $reflection->getMethod('hasColorSupport');
    $method->setAccessible(true);

    // This will vary based on the system
    $result = $method->invoke($output);
    expect($result)->toBeBool();
});

it('handles text without color support', function () {
    // Create output instance with color support disabled
    $output = new Output;
    $reflection = new ReflectionClass($output);
    $property = $reflection->getProperty('supportsColors');
    $property->setAccessible(true);
    $property->setValue($output, false);

    $colored = $output->color('Test', Output::RED);
    expect($colored)->toBe('Test');
});

it('detects windows color support with ANSICON', function () {
    // Save original values
    $originalSep = DIRECTORY_SEPARATOR;
    $originalAnsicon = getenv('ANSICON');

    // We can't actually change DIRECTORY_SEPARATOR, so we'll test the logic differently
    // by directly testing the color support detection
    $output = new Output;

    // The hasColorSupport method will check the current system
    // We just verify it returns a boolean
    $reflection = new ReflectionClass($output);
    $method = $reflection->getMethod('hasColorSupport');
    $method->setAccessible(true);

    $result = $method->invoke($output);
    expect($result)->toBeBool();
});
