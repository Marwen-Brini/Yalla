<?php

declare(strict_types=1);

namespace Tests\Repl;

use ReflectionClass;
use Yalla\Output\Output;
use Yalla\Repl\ReplConfig;
use Yalla\Repl\ReplContext;
use Yalla\Repl\ReplSession;

// Test model with protected properties
class TestModelWithProtected
{
    protected $id;

    protected $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}

// Test model with public properties
class TestModelWithPublic
{
    public $id;

    public $title;

    public function __construct($id, $title)
    {
        $this->id = $id;
        $this->title = $title;
    }
}

// Test model with __toString
class TestModelWithToString
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return "Model: {$this->value}";
    }
}

beforeEach(function () {
    $this->config = new ReplConfig;
    $this->context = new ReplContext($this->config);
    $this->output = new Output;
    $this->session = new ReplSession($this->context, $this->output, $this->config);

    // Use reflection to access private methods
    $this->reflectionClass = new ReflectionClass($this->session);
});

test('isTableArray correctly identifies table-suitable arrays', function () {
    $method = $this->reflectionClass->getMethod('isTableArray');
    $method->setAccessible(true);

    // Arrays of arrays with same structure - should be true
    $arrayOfArrays = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ];
    expect($method->invoke($this->session, $arrayOfArrays))->toBeTrue();

    // Arrays of objects - should be false (fixed behavior)
    $arrayOfObjects = [
        new TestModelWithProtected(1, 'Test1'),
        new TestModelWithProtected(2, 'Test2'),
    ];
    expect($method->invoke($this->session, $arrayOfObjects))->toBeFalse();

    // Mixed array - should be false
    $mixedArray = [
        ['id' => 1, 'name' => 'Array'],
        new TestModelWithProtected(2, 'Object'),
    ];
    expect($method->invoke($this->session, $mixedArray))->toBeFalse();

    // Empty array - should be false
    expect($method->invoke($this->session, []))->toBeFalse();

    // Arrays with different keys - should be false
    $differentKeys = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'title' => 'Bob'],
    ];
    expect($method->invoke($this->session, $differentKeys))->toBeFalse();
});

test('formatValue handles objects with public properties', function () {
    $method = $this->reflectionClass->getMethod('formatValue');
    $method->setAccessible(true);

    $object = new TestModelWithPublic(123, 'Hello World');
    $result = $method->invoke($this->session, $object);

    // Should show class name and first 2 public properties
    expect($result)->toContain('TestModelWithPublic');
    expect($result)->toContain('id: 123');
    expect($result)->toContain('title: "Hello World"');
});

test('formatValue handles objects with __toString', function () {
    $method = $this->reflectionClass->getMethod('formatValue');
    $method->setAccessible(true);

    $object = new TestModelWithToString('test-value');
    $result = $method->invoke($this->session, $object);

    // Should show class name and toString result
    expect($result)->toContain('TestModelWithToString');
    expect($result)->toContain('Model: test-value');
});

test('formatValue handles objects with no public properties', function () {
    $method = $this->reflectionClass->getMethod('formatValue');
    $method->setAccessible(true);

    $object = new TestModelWithProtected(1, 'Test');
    $result = $method->invoke($this->session, $object);

    // Should show class name with "object" suffix
    expect($result)->toContain('TestModelWithProtected');
    expect($result)->toContain('object');
});

test('displayArray handles arrays of objects correctly', function () {
    $method = $this->reflectionClass->getMethod('displayArray');
    $method->setAccessible(true);

    // Capture output
    ob_start();

    // Array of objects with protected properties should display as list
    $objects = [
        new TestModelWithProtected(1, 'Test1'),
        new TestModelWithProtected(2, 'Test2'),
    ];

    $method->invoke($this->session, $objects);

    $output = ob_get_clean();

    // Should display as list, not table
    expect($output)->toContain('[');
    expect($output)->toContain(']');
    expect($output)->toContain('TestModelWithProtected');
});

test('displayArray shows table for arrays of arrays', function () {
    $method = $this->reflectionClass->getMethod('displayArray');
    $method->setAccessible(true);

    // Mock the output to capture table calls
    $mockOutput = $this->createMock(Output::class);

    // Expect table method to be called once
    $mockOutput->expects($this->once())
        ->method('table')
        ->with(
            ['id', 'name'],
            $this->anything()
        );

    // Replace output with mock
    $outputProperty = $this->reflectionClass->getProperty('output');
    $outputProperty->setAccessible(true);
    $outputProperty->setValue($this->session, $mockOutput);

    // Array of arrays should display as table (need more than 3 items to trigger table display)
    $arrays = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
        ['id' => 3, 'name' => 'Charlie'],
        ['id' => 4, 'name' => 'David'],
    ];

    $method->invoke($this->session, $arrays);
});

test('formatValue truncates long strings', function () {
    $method = $this->reflectionClass->getMethod('formatValue');
    $method->setAccessible(true);

    $longString = str_repeat('a', 100);
    $result = $method->invoke($this->session, $longString);

    // Should truncate to 50 chars (47 + ...)
    expect(strlen(strip_tags($result)))->toBeLessThanOrEqual(53); // 50 + quotes + ellipsis
    expect($result)->toContain('...');
});

test('formatValue handles scalar types correctly', function () {
    $method = $this->reflectionClass->getMethod('formatValue');
    $method->setAccessible(true);

    // Test null
    $result = $method->invoke($this->session, null);
    expect($result)->toContain('null');

    // Test boolean true
    $result = $method->invoke($this->session, true);
    expect($result)->toContain('true');

    // Test boolean false
    $result = $method->invoke($this->session, false);
    expect($result)->toContain('false');

    // Test integer
    $result = $method->invoke($this->session, 42);
    expect($result)->toContain('42');

    // Test float
    $result = $method->invoke($this->session, 3.14);
    expect($result)->toContain('3.14');

    // Test string
    $result = $method->invoke($this->session, 'hello');
    expect($result)->toContain('"hello"');

    // Test array
    $result = $method->invoke($this->session, [1, 2, 3]);
    expect($result)->toContain('array(3)');
});
