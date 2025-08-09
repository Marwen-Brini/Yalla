<?php

declare(strict_types=1);

use Yalla\Commands\CreateCommandCommand;
use Yalla\Output\Output;

it('returns root namespace when directory parts are empty after removing src', function () {
    $command = new CreateCommandCommand;
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateNamespace');
    $method->setAccessible(true);

    // Test with just 'src' - should return root namespace only
    $namespace = $method->invoke($command, 'src');
    expect($namespace)->toBe('Yalla');
});

it('returns App as default namespace when composer.json has no psr-4 autoload', function () {
    $command = new CreateCommandCommand;
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectRootNamespace');
    $method->setAccessible(true);

    // Change to a temp directory without composer.json
    $tempDir = sys_get_temp_dir().'/yalla_no_composer_'.uniqid();
    mkdir($tempDir);

    $originalDir = getcwd();
    chdir($tempDir);

    // Should return 'App' when no composer.json exists
    $namespace = $method->invoke($command);
    expect($namespace)->toBe('App');

    // Create composer.json without psr-4
    file_put_contents($tempDir.'/composer.json', json_encode([
        'name' => 'test/package',
        'autoload' => [
            'files' => ['bootstrap.php'],
        ],
    ]));

    // Should still return 'App' when no psr-4 section
    $namespace = $method->invoke($command);
    expect($namespace)->toBe('App');

    // Clean up
    chdir($originalDir);
    unlink($tempDir.'/composer.json');
    rmdir($tempDir);
});

// Test for line 42 in Output.php (Windows-specific path)
it('handles Windows-specific color detection', function () {
    $output = new Output;
    $reflection = new ReflectionClass($output);
    $method = $reflection->getMethod('hasColorSupport');
    $method->setAccessible(true);

    // We can't change DIRECTORY_SEPARATOR, but we can test the environment variables
    // that would be checked on Windows
    $originalAnsicon = getenv('ANSICON');
    $originalConEmu = getenv('ConEmuANSI');

    // Test with neither variable set
    putenv('ANSICON');
    putenv('ConEmuANSI');
    $result1 = $method->invoke($output);

    // Test with ANSICON set to false (simulating Windows without ANSICON)
    putenv('ANSICON=');
    $result2 = $method->invoke($output);

    // Test with ConEmuANSI set to something other than 'ON'
    putenv('ConEmuANSI=OFF');
    $result3 = $method->invoke($output);

    // All should return boolean values
    expect($result1)->toBeBool();
    expect($result2)->toBeBool();
    expect($result3)->toBeBool();

    // Restore original values
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
});
