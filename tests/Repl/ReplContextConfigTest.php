<?php

declare(strict_types=1);

use Yalla\Repl\ReplConfig;
use Yalla\Repl\ReplContext;

test('loads shortcuts from config', function () {
    $config = new ReplConfig;
    $config->set('shortcuts', [
        'User' => '\\App\\Models\\User',
        'Post' => '\\App\\Models\\Post',
    ]);

    $context = new ReplContext($config);

    // processInput handles shortcuts replacement
    expect($context->processInput('User::find(1)'))->toBe('\\App\\Models\\User::find(1)');
    expect($context->processInput('Post::all()'))->toBe('\\App\\Models\\Post::all()');
});

test('loads imports from config', function () {
    $config = new ReplConfig;
    $config->set('imports', [
        'Carbon\\Carbon',
        ['class' => 'Illuminate\\Support\\Str', 'alias' => 'Str'],
    ]);

    $context = new ReplContext($config);
    $imports = $context->getImports();

    // Check that imports were loaded - getImports returns an associative array
    expect(count($imports))->toBe(2);

    // Carbon should use basename as alias
    expect($imports)->toHaveKey('Carbon');
    expect($imports['Carbon'])->toBe('Carbon\\Carbon');

    // Str should use the provided alias
    expect($imports)->toHaveKey('Str');
    expect($imports['Str'])->toBe('Illuminate\\Support\\Str');
});

test('getters return correct values', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    // Test getConfig
    expect($context->getConfig())->toBe($config);

    // Test getCommands - returns array of command names
    $commands = $context->getCommands();
    expect($commands)->toBeArray();
    expect($commands)->toContain('help');
    expect($commands)->toContain('exit');

    // Test getShortcuts
    $context->addShortcut('Test', '\\App\\Test');
    $shortcuts = $context->getShortcuts();
    expect($shortcuts)->toHaveKey('Test');
    expect($shortcuts['Test'])->toBe('\\App\\Test');

    // Test getVariables
    $context->setVariable('test', 123);
    $variables = $context->getVariables();
    expect($variables)->toHaveKey('test');
    expect($variables['test'])->toBe(123);
});

test('getVariable returns correct value', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $context->setVariable('test', 'value');
    expect($context->getVariable('test'))->toBe('value');

    // Non-existent variable returns null
    expect($context->getVariable('nonexistent'))->toBeNull();
});

test('getCommand returns callable', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    // Built-in command
    expect($context->getCommand('help'))->toBeCallable();

    // Non-existent command
    expect($context->getCommand('nonexistent'))->toBeNull();
});

test('setHistoryManager and getHistoryManager work', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $history = new \Yalla\Repl\History\HistoryManager($config);
    $context->setHistoryManager($history);

    expect($context->getHistoryManager())->toBe($history);
});

test('addCompleter adds custom completers', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $completer = function ($partial) {
        return ['test_completion'];
    };

    $context->addCompleter('test', $completer);
    $completers = $context->getCompleters();

    expect($completers)->toHaveKey('test');
    expect($completers['test'])->toBe($completer);
});

test('addFormatter and getFormatter work', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $formatter = function ($result) {
        return 'formatted: '.$result;
    };

    $context->addFormatter('string', $formatter);

    // getFormatter returns the formatter for a string value
    $foundFormatter = $context->getFormatter('test string');
    expect($foundFormatter)->toBeCallable();
});

test('getFormatter checks parent classes and interfaces', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    // Create a formatter for Exception class
    $exceptionFormatter = function ($result) {
        return 'Exception: '.$result->getMessage();
    };

    $context->addFormatter('Exception', $exceptionFormatter);

    // Create a RuntimeException (which extends Exception)
    $runtimeException = new \RuntimeException('Test error');

    // getFormatter should return the Exception formatter for RuntimeException
    $foundFormatter = $context->getFormatter($runtimeException);
    expect($foundFormatter)->toBeCallable();
    expect($foundFormatter)->toBe($exceptionFormatter);

    // Test with an object that doesn't match any formatter
    $stdClass = new \stdClass;
    $noFormatter = $context->getFormatter($stdClass);
    expect($noFormatter)->toBeNull();
});

test('addEvaluator adds custom evaluators', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $evaluator = function ($input) {
        return eval($input);
    };

    $context->addEvaluator('custom', $evaluator, 10);
    $evaluators = $context->getEvaluators();

    // Find the custom evaluator
    $found = false;
    foreach ($evaluators as $eval) {
        if ($eval['name'] === 'custom') {
            expect($eval['evaluator'])->toBe($evaluator);
            expect($eval['priority'])->toBe(10);
            $found = true;

            break;
        }
    }

    expect($found)->toBeTrue();
});

test('addNamespace adds namespace shortcut', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $context->addNamespace('Models', '\\App\\Models');

    // Test that namespace replacement works in processInput
    $result = $context->processInput('Models\\User::find(1)');
    expect($result)->toBe('\\App\\Models\\User::find(1)');
});

test('addMiddleware adds middleware', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $middleware = function ($input, $ctx) {
        return strtoupper($input);
    };

    $context->addMiddleware('input', $middleware);

    // Test that middleware is applied
    $result = $context->processInput('test');
    expect($result)->toBe('TEST');
});

test('addMiddleware throws exception for invalid type', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $middleware = function ($data, $ctx) {
        return $data;
    };

    expect(fn () => $context->addMiddleware('invalid', $middleware))
        ->toThrow(\InvalidArgumentException::class, 'Invalid middleware type: invalid');
});

test('processOutput applies middleware', function () {
    $config = new ReplConfig;
    $context = new ReplContext($config);

    $middleware = function ($output, $ctx) {
        return 'Modified: '.$output;
    };

    $context->addMiddleware('output', $middleware);

    $result = $context->processOutput('test');
    expect($result)->toBe('Modified: test');
});
