<?php

declare(strict_types=1);

namespace Tests\Repl;

use ReflectionClass;
use Yalla\Output\Output;
use Yalla\Repl\ReplConfig;
use Yalla\Repl\ReplContext;
use Yalla\Repl\ReplSession;

beforeEach(function () {
    $this->config = new ReplConfig;
    $this->context = new ReplContext($this->config);
    $this->output = new Output;
    $this->session = new ReplSession($this->context, $this->output, $this->config);

    // Use reflection to access private methods
    $this->reflectionClass = new ReflectionClass($this->session);
});

test('evaluateExpression strips trailing semicolons', function () {
    // Get the private evaluateExpression method
    $method = $this->reflectionClass->getMethod('evaluateExpression');
    $method->setAccessible(true);

    // Test expressions with semicolons
    $testCases = [
        '2 + 2;' => 4,
        '5 * 3;' => 15,
        '"hello";' => 'hello',
        'true;' => true,
        'null;' => null,
        '[];' => [],
        '2 + 2' => 4, // Without semicolon should also work
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($this->session, $input);
        expect($result)->toBe($expected);
    }
});

test('evaluateExpression handles multiple trailing semicolons', function () {
    $method = $this->reflectionClass->getMethod('evaluateExpression');
    $method->setAccessible(true);

    // Test with multiple semicolons and spaces
    $testCases = [
        '2 + 2;;' => 4,
        '3 * 3; ' => 9,
        '10 / 2;  ' => 5,
        '"test";;;' => 'test',
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($this->session, $input);
        expect($result)->toBe($expected);
    }
});

test('evaluateExpression preserves semicolons within strings', function () {
    $method = $this->reflectionClass->getMethod('evaluateExpression');
    $method->setAccessible(true);

    // Semicolons inside strings should not be stripped
    $testCases = [
        '"hello; world"' => 'hello; world',
        '"test;"' => 'test;',
        "'semicolon;'" => 'semicolon;',
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($this->session, $input);
        expect($result)->toBe($expected);
    }
});

test('variable assignments work with trailing semicolons', function () {
    $method = $this->reflectionClass->getMethod('executeVariableAssignment');
    $method->setAccessible(true);

    // Test variable assignments with semicolons
    $testInputs = [
        '$x = 5;',
        '$y = "hello";',
        '$z = true;',
        '$arr = [1, 2, 3];',
    ];

    foreach ($testInputs as $input) {
        // Capture output to prevent it from being displayed during tests
        ob_start();

        try {
            // This should not throw an exception
            expect(fn () => $method->invoke($this->session, $input))->not->toThrow(\Exception::class);
        } finally {
            ob_end_clean();
        }
    }
});

test('complex expressions with semicolons work correctly', function () {
    $method = $this->reflectionClass->getMethod('evaluateExpression');
    $method->setAccessible(true);

    // Test more complex expressions
    $testCases = [
        'array_sum([1, 2, 3]);' => 6,
        'strlen("hello");' => 5,
        'max(1, 5, 3);' => 5,
        'implode(", ", ["a", "b", "c"]);' => 'a, b, c',
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($this->session, $input);
        expect($result)->toBe($expected);
    }
});

test('class method calls work with semicolons', function () {
    $method = $this->reflectionClass->getMethod('evaluateExpression');
    $method->setAccessible(true);

    // Create a test class in the context
    class TestModelForSemicolon
    {
        public static function count(): int
        {
            return 42;
        }

        public static function getName(): string
        {
            return 'TestModel';
        }
    }

    // Test static method calls with semicolons
    $testCases = [
        'Tests\Repl\TestModelForSemicolon::count();' => 42,
        'Tests\Repl\TestModelForSemicolon::getName();' => 'TestModel',
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($this->session, $input);
        expect($result)->toBe($expected);
    }
});
