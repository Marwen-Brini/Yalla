<?php

declare(strict_types=1);

use Yalla\Output\Output;

class TestableWindowsOutput extends Output
{
    protected function isWindows(): bool
    {
        return true;
    }

    protected function hasWindowsColorSupport(): bool
    {
        return true;
    }
}

class TestableUnixOutput extends Output
{
    protected function isWindows(): bool
    {
        return false;
    }

    protected function hasUnixColorSupport(): bool
    {
        return true;
    }
}

it('detects Windows platform and checks Windows color support', function () {
    // This creates an Output instance that thinks it's on Windows
    $output = new TestableWindowsOutput;

    expect($output)->toBeInstanceOf(Output::class);

    // The constructor already sets supportsColors based on hasColorSupport()
    // Since TestableWindowsOutput returns true for hasWindowsColorSupport(),
    // colors should work
    $colored = $output->color('Test', Output::RED);
    expect($colored)->toBe(Output::RED.'Test'.Output::RESET);
});

it('detects Unix platform and checks Unix color support', function () {
    // This creates an Output instance that thinks it's on Unix
    $output = new TestableUnixOutput;

    expect($output)->toBeInstanceOf(Output::class);

    // The constructor already sets supportsColors based on hasColorSupport()
    // Since TestableUnixOutput returns true for hasUnixColorSupport(),
    // colors should work
    $colored = $output->color('Test', Output::GREEN);
    expect($colored)->toBe(Output::GREEN.'Test'.Output::RESET);
});

it('tests actual platform detection methods', function () {
    $output = new Output;
    $reflection = new ReflectionClass($output);

    // Test isWindows
    $method = $reflection->getMethod('isWindows');
    $method->setAccessible(true);
    $isWindows = $method->invoke($output);
    expect($isWindows)->toBeBool();
    expect($isWindows)->toBe(DIRECTORY_SEPARATOR === '\\');

    // Test hasWindowsColorSupport
    $method = $reflection->getMethod('hasWindowsColorSupport');
    $method->setAccessible(true);

    $originalAnsicon = getenv('ANSICON');
    $originalConEmu = getenv('ConEmuANSI');

    // Test with ANSICON set
    putenv('ANSICON=1');
    expect($method->invoke($output))->toBeTrue();

    // Test with ANSICON not set but ConEmuANSI=ON
    putenv('ANSICON');
    putenv('ConEmuANSI=ON');
    expect($method->invoke($output))->toBeTrue();

    // Test with neither set
    putenv('ANSICON');
    putenv('ConEmuANSI=OFF');
    expect($method->invoke($output))->toBeFalse();

    // Restore
    if ($originalAnsicon !== false) {
        putenv("ANSICON=$originalAnsicon");
    } else {
        putenv('ANSICON');
    }
    if ($originalConEmu !== false) {
        putenv("ConEmuANSI=$originalConEmu");
    } else {
        putenv('ConEmuANSI');
    }

    // Test hasUnixColorSupport
    $method = $reflection->getMethod('hasUnixColorSupport');
    $method->setAccessible(true);
    $result = $method->invoke($output);
    expect($result)->toBeBool();
});
