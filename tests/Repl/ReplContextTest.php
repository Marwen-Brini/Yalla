<?php

declare(strict_types=1);

use Yalla\Repl\ReplConfig;
use Yalla\Repl\ReplContext;

test('can add and retrieve shortcuts', function () {
    $context = new ReplContext(new ReplConfig);
    $context->addShortcut('User', '\App\Models\User');

    $processed = $context->processInput('User::find(1)');

    expect($processed)->toBe('\App\Models\User::find(1)');
});

test('can register and execute commands', function () {
    $context = new ReplContext(new ReplConfig);
    $executed = false;

    $context->addCommand('test', function () use (&$executed) {
        $executed = true;
    });

    $command = $context->getCommand('test');
    $command();

    expect($executed)->toBeTrue();
});

test('can manage variables', function () {
    $context = new ReplContext(new ReplConfig);

    $context->setVariable('foo', 'bar');
    $context->setVariable('num', 42);

    expect($context->getVariable('foo'))->toBe('bar');
    expect($context->getVariable('num'))->toBe(42);
    expect($context->getVariable('nonexistent'))->toBeNull();
});

test('can add and retrieve imports', function () {
    $context = new ReplContext(new ReplConfig);

    $context->addImport('\Carbon\Carbon', 'Carbon');
    $context->addImport('\App\Models\User');  // Should use basename as alias

    $imports = $context->getImports();

    expect($imports)->toHaveKey('Carbon');
    expect($imports['Carbon'])->toBe('\Carbon\Carbon');
    expect($imports)->toHaveKey('User');
    expect($imports['User'])->toBe('\App\Models\User');
});

test('processes multiple shortcuts in input', function () {
    $context = new ReplContext(new ReplConfig);

    $context->addShortcut('Post', '\App\Models\Post');
    $context->addShortcut('User', '\App\Models\User');

    $input = 'Post::find(1)->user && new User()';
    $processed = $context->processInput($input);

    expect($processed)->toBe('\App\Models\Post::find(1)->user && new \App\Models\User()');
});

test('can add formatters and retrieve them by type', function () {
    $context = new ReplContext(new ReplConfig);

    $formatter = function ($value, $output) {
        $output->writeln("Custom format: $value");
    };

    $context->addFormatter('CustomClass', $formatter);

    $obj = new class
    {
        public function __toString()
        {
            return 'CustomClass';
        }
    };

    // Since we can't actually instantiate CustomClass, test with string type
    $context->addFormatter('string', $formatter);
    $retrieved = $context->getFormatter('test string');

    expect($retrieved)->toBe($formatter);
});

test('middleware processes input and output', function () {
    $context = new ReplContext(new ReplConfig);

    $context->addMiddleware('input', function ($input) {
        return strtoupper($input);
    });

    $context->addMiddleware('output', function ($output) {
        return "Result: $output";
    });

    $processedInput = $context->processInput('hello');
    expect($processedInput)->toBe('HELLO');

    $processedOutput = $context->processOutput('world');
    expect($processedOutput)->toBe('Result: world');
});
