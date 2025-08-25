<?php

declare(strict_types=1);

use Yalla\Commands\InitCommand;
use Yalla\Output\Output;

beforeEach(function () {
    // Clean up any existing files
    $files = ['testcli', 'cli', 'yalla.config.php'];
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

afterEach(function () {
    // Clean up created files
    $files = ['testcli', 'cli', 'yalla.config.php'];
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('command has correct name and description', function () {
    $command = new InitCommand;

    expect($command->getName())->toBe('init');
    expect($command->getDescription())->toBe('Initialize Yalla CLI in your project');
});

test('creates CLI entry point and config', function () {
    $command = new InitCommand;
    $output = new Output;

    // We're in the package directory, so it will skip some steps
    ob_start();
    $result = $command->execute([
        'options' => [
            'name' => 'Test App',
            'bin' => 'testcli',
            'force' => false,
        ],
    ], $output);
    ob_end_clean();

    expect($result)->toBe(0);
    expect(file_exists('testcli'))->toBeTrue();
    expect(file_exists('yalla.config.php'))->toBeTrue();

    // Check CLI content
    $cliContent = file_get_contents('testcli');
    expect($cliContent)->toContain('Test App');
    expect($cliContent)->toContain('require __DIR__ . \'/vendor/autoload.php\'');

    // Check config content
    $configContent = file_get_contents('yalla.config.php');
    expect($configContent)->toContain("'commands' =>");
    expect($configContent)->toContain("'command_namespace' =>");
});

test('does not overwrite without force', function () {
    // Create existing files
    file_put_contents('testcli', '#!/usr/bin/env php');
    file_put_contents('yalla.config.php', '<?php return [];');

    $command = new InitCommand;
    $output = new Output;

    ob_start();
    $result = $command->execute([
        'options' => [
            'bin' => 'testcli',
            'force' => false,
        ],
    ], $output);
    $output = ob_get_clean();

    expect($result)->toBe(0);
    expect($output)->toContain('already exists');
});

test('overwrites with force option', function () {
    // Create existing file
    file_put_contents('testcli', 'old content');

    $command = new InitCommand;
    $output = new Output;

    ob_start();
    $result = $command->execute([
        'options' => [
            'bin' => 'testcli',
            'force' => true,
        ],
    ], $output);
    ob_end_clean();

    expect($result)->toBe(0);

    // Check file was overwritten
    $content = file_get_contents('testcli');
    expect($content)->not->toContain('old content');
    expect($content)->toContain('#!/usr/bin/env php');
});
