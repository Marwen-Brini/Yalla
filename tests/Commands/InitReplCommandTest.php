<?php

declare(strict_types=1);

use Yalla\Commands\InitReplCommand;
use Yalla\Output\Output;

beforeEach(function () {
    // Clean up any existing config file
    if (file_exists('repl.config.php')) {
        unlink('repl.config.php');
    }
});

afterEach(function () {
    // Clean up created config file
    if (file_exists('repl.config.php')) {
        unlink('repl.config.php');
    }
});

test('creates repl config file', function () {
    $command = new InitReplCommand;
    $output = new Output;

    // Capture output
    ob_start();
    $result = $command->execute(['options' => []], $output);
    ob_end_clean();

    expect($result)->toBe(0);
    expect(file_exists('repl.config.php'))->toBeTrue();

    // Check the content includes expected configuration
    $content = file_get_contents('repl.config.php');
    expect($content)->toContain("'extensions' =>");
    expect($content)->toContain("'bootstrap' =>");
    expect($content)->toContain("'shortcuts' =>");
    expect($content)->toContain("'display' =>");
    expect($content)->toContain("'prompt' => '[{counter}] > '");
});

test('does not overwrite existing config without force', function () {
    // Create an existing config file
    file_put_contents('repl.config.php', '<?php return ["test" => true];');

    $command = new InitReplCommand;
    $output = new Output;

    // Capture output
    ob_start();
    $result = $command->execute(['options' => []], $output);
    ob_end_clean();

    expect($result)->toBe(1);

    // Check original file is unchanged
    $content = file_get_contents('repl.config.php');
    expect($content)->toContain('"test" => true');
});

test('overwrites existing config with force option', function () {
    // Create an existing config file
    file_put_contents('repl.config.php', '<?php return ["test" => true];');

    $command = new InitReplCommand;
    $output = new Output;

    // Capture output
    ob_start();
    $result = $command->execute(['options' => ['force' => true]], $output);
    ob_end_clean();

    expect($result)->toBe(0);

    // Check file was overwritten
    $content = file_get_contents('repl.config.php');
    expect($content)->not->toContain('"test" => true');
    expect($content)->toContain("'extensions' =>");
});

test('command has correct name and description', function () {
    $command = new InitReplCommand;

    expect($command->getName())->toBe('init:repl');
    expect($command->getDescription())->toBe('Initialize REPL configuration file');
});
