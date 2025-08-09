<?php

declare(strict_types=1);

use Yalla\Commands\CreateCommandCommand;
use Yalla\Output\Output;

it('creates directory if it does not exist', function () {
    $command = new CreateCommandCommand;
    $output = new Output;

    $customDir = getcwd().'/src/Commands/Custom';

    // Ensure directory doesn't exist
    if (is_dir($customDir)) {
        rmdir($customDir);
    }

    $input = [
        'command' => 'create:command',
        'arguments' => ['test'],
        'options' => ['dir' => 'src/Commands/Custom'],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    ob_end_clean();

    expect($result)->toBe(0);
    expect(is_dir($customDir))->toBeTrue();
    expect(file_exists($customDir.'/TestCommand.php'))->toBeTrue();

    // Clean up
    unlink($customDir.'/TestCommand.php');
    rmdir($customDir);
});

it('adds Command suffix if not present', function () {
    $command = new CreateCommandCommand;
    $output = new Output;

    $input = [
        'command' => 'create:command',
        'arguments' => ['test'],
        'options' => ['class' => 'CustomClass'],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    ob_end_clean();

    expect($result)->toBe(0);
    expect(file_exists(getcwd().'/src/Commands/CustomClassCommand.php'))->toBeTrue();

    // Clean up
    unlink(getcwd().'/src/Commands/CustomClassCommand.php');
});

it('handles missing composer.json gracefully', function () {
    $command = new CreateCommandCommand;
    $reflection = new ReflectionClass($command);

    // Test getProjectRoot when no composer.json exists up the tree
    $getProjectRoot = $reflection->getMethod('getProjectRoot');
    $getProjectRoot->setAccessible(true);

    // Save current directory
    $originalDir = getcwd();

    // Change to temp directory
    $tempDir = sys_get_temp_dir().'/yalla_test_'.uniqid();
    mkdir($tempDir);
    chdir($tempDir);

    $result = $getProjectRoot->invoke($command);

    expect($result)->toBe($tempDir);

    // Restore original directory
    chdir($originalDir);
    rmdir($tempDir);
});

it('handles directory without src prefix in namespace generation', function () {
    $command = new CreateCommandCommand;
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateNamespace');
    $method->setAccessible(true);

    // Test with directory that doesn't start with 'src'
    expect($method->invoke($command, 'Commands'))->toBe('Yalla\\Commands');
    expect($method->invoke($command, 'App/Commands'))->toBe('Yalla\\App\\Commands');
});
