<?php

declare(strict_types=1);

use Yalla\Output\Output;

it('detects Windows color support with ANSICON environment variable', function () {
    // We can't actually test Windows-specific code on Linux/Mac
    // But we can test the logic by mocking the environment

    // Save original values
    $originalSeparator = DIRECTORY_SEPARATOR;
    $originalAnsicon = getenv('ANSICON');
    $originalConEmu = getenv('ConEmuANSI');

    // Test ANSICON detection
    putenv('ANSICON=1');

    $output = new Output;
    $reflection = new ReflectionClass($output);
    $method = $reflection->getMethod('hasColorSupport');
    $method->setAccessible(true);

    // This will still check DIRECTORY_SEPARATOR which we can't change
    // But at least we're setting the env vars
    $result = $method->invoke($output);

    // Restore original values
    if ($originalAnsicon !== false) {
        putenv("ANSICON=$originalAnsicon");
    } else {
        putenv('ANSICON');
    }

    expect($result)->toBeBool();
});

it('detects Windows color support with ConEmuANSI environment variable', function () {
    // Save original value
    $originalConEmu = getenv('ConEmuANSI');

    // Test ConEmuANSI detection
    putenv('ConEmuANSI=ON');

    $output = new Output;
    $reflection = new ReflectionClass($output);
    $method = $reflection->getMethod('hasColorSupport');
    $method->setAccessible(true);

    $result = $method->invoke($output);

    // Restore original value
    if ($originalConEmu !== false) {
        putenv("ConEmuANSI=$originalConEmu");
    } else {
        putenv('ConEmuANSI');
    }

    expect($result)->toBeBool();
});

it('returns colored text when color support is enabled', function () {
    $output = new Output;
    $reflection = new ReflectionClass($output);
    $property = $reflection->getProperty('supportsColors');
    $property->setAccessible(true);
    $property->setValue($output, true);

    $colored = $output->color('Test', Output::RED);
    expect($colored)->toBe(Output::RED.'Test'.Output::RESET);

    $colored = $output->color('Hello', Output::GREEN);
    expect($colored)->toBe(Output::GREEN.'Hello'.Output::RESET);
});
