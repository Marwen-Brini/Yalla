<?php

declare(strict_types=1);

use Yalla\Commands\CreateCommandCommand;
use Yalla\Output\Output;

beforeEach(function () {
    // Clean up any test files
    $testFile = getcwd().'/src/Commands/TestGeneratedCommand.php';
    if (file_exists($testFile)) {
        unlink($testFile);
    }
});

afterEach(function () {
    // Clean up any test files
    $testFile = getcwd().'/src/Commands/TestGeneratedCommand.php';
    if (file_exists($testFile)) {
        unlink($testFile);
    }
});

it('creates a new command file', function () {
    $command = new CreateCommandCommand;
    $output = new Output;

    $input = [
        'command' => 'create:command',
        'arguments' => ['test-generated'],
        'options' => [],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    ob_end_clean();

    expect($result)->toBe(0);
    expect(file_exists(getcwd().'/src/Commands/TestGeneratedCommand.php'))->toBeTrue();

    // Clean up
    unlink(getcwd().'/src/Commands/TestGeneratedCommand.php');
});

it('fails when file already exists without force', function () {
    $command = new CreateCommandCommand;
    $output = new Output;

    // Create a file first
    $testFile = getcwd().'/src/Commands/TestGeneratedCommand.php';
    file_put_contents($testFile, '<?php // test');

    $input = [
        'command' => 'create:command',
        'arguments' => ['test-generated'],
        'options' => [],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    ob_end_clean();

    expect($result)->toBe(1);

    // Clean up
    unlink($testFile);
});

it('overwrites file with force option', function () {
    $command = new CreateCommandCommand;
    $output = new Output;

    // Create a file first
    $testFile = getcwd().'/src/Commands/TestGeneratedCommand.php';
    file_put_contents($testFile, '<?php // old content');

    $input = [
        'command' => 'create:command',
        'arguments' => ['test-generated'],
        'options' => ['force' => true],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    ob_end_clean();

    expect($result)->toBe(0);

    $content = file_get_contents($testFile);
    expect($content)->not->toContain('// old content');
    expect($content)->toContain('class TestGeneratedCommand');

    // Clean up
    unlink($testFile);
});

it('uses custom class name when provided', function () {
    $command = new CreateCommandCommand;
    $output = new Output;

    $input = [
        'command' => 'create:command',
        'arguments' => ['test'],
        'options' => ['class' => 'CustomNameCommand'],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    ob_end_clean();

    expect($result)->toBe(0);
    expect(file_exists(getcwd().'/src/Commands/CustomNameCommand.php'))->toBeTrue();

    $content = file_get_contents(getcwd().'/src/Commands/CustomNameCommand.php');
    expect($content)->toContain('class CustomNameCommand');

    // Clean up
    unlink(getcwd().'/src/Commands/CustomNameCommand.php');
});

it('converts various command name formats to class names', function () {
    $command = new CreateCommandCommand;
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateClassName');
    $method->setAccessible(true);

    expect($method->invoke($command, 'serve'))->toBe('ServeCommand');
    expect($method->invoke($command, 'make:model'))->toBe('MakeModelCommand');
    expect($method->invoke($command, 'create-user'))->toBe('CreateUserCommand');
    expect($method->invoke($command, 'test_command'))->toBe('TestCommandCommand');
});

it('detects root namespace from composer.json', function () {
    $command = new CreateCommandCommand;
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('detectRootNamespace');
    $method->setAccessible(true);

    $namespace = $method->invoke($command);

    expect($namespace)->toBe('Yalla');
});

it('generates correct namespace from directory', function () {
    $command = new CreateCommandCommand;
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('generateNamespace');
    $method->setAccessible(true);

    expect($method->invoke($command, 'src/Commands'))->toBe('Yalla\\Commands');
    expect($method->invoke($command, 'src/Commands/Admin'))->toBe('Yalla\\Commands\\Admin');
    expect($method->invoke($command, 'Commands'))->toBe('Yalla\\Commands');
});
