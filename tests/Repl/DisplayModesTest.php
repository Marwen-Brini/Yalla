<?php

declare(strict_types=1);

namespace Tests\Repl;

use Yalla\Output\Output;
use Yalla\Repl\ReplConfig;
use Yalla\Repl\ReplContext;
use Yalla\Repl\ReplSession;
use ReflectionClass;

// Test class with various properties for verbose mode testing
class TestClassForModes {
    public $publicProp = 'public';
    protected $protectedProp = 'protected';
    private $privateProp = 'private';
    
    public function publicMethod() {}
    protected function protectedMethod() {}
    private function privateMethod() {}
    
    public function __toString() {
        return 'TestClass instance';
    }
}

beforeEach(function () {
    $this->config = new ReplConfig();
    $this->context = new ReplContext($this->config);
    $this->output = new Output();
    $this->session = new ReplSession($this->context, $this->output, $this->config);
    
    $this->reflectionClass = new ReflectionClass($this->session);
});

test('default display mode is compact', function () {
    expect($this->config->get('display.mode'))->toBe('compact');
});

test('can change display mode via config', function () {
    $this->config->set('display.mode', 'verbose');
    expect($this->config->get('display.mode'))->toBe('verbose');
    
    $this->config->set('display.mode', 'json');
    expect($this->config->get('display.mode'))->toBe('json');
    
    $this->config->set('display.mode', 'dump');
    expect($this->config->get('display.mode'))->toBe('dump');
});

test('compact mode displays values concisely', function () {
    $this->config->set('display.mode', 'compact');
    
    $displayResult = $this->reflectionClass->getMethod('displayResult');
    $displayResult->setAccessible(true);
    
    // Capture output for scalar value
    ob_start();
    $displayResult->invoke($this->session, 'test string');
    $output = ob_get_clean();
    
    expect($output)->toContain('"test string"');
    expect($output)->not->toContain('═══'); // No verbose headers
});

test('json mode displays values as JSON', function () {
    $this->config->set('display.mode', 'json');
    
    $displayResult = $this->reflectionClass->getMethod('displayResult');
    $displayResult->setAccessible(true);
    
    // Test with array
    $testData = ['name' => 'John', 'age' => 30];
    
    ob_start();
    $displayResult->invoke($this->session, $testData);
    $output = ob_get_clean();
    
    // Should be valid JSON
    $decoded = json_decode(trim(strip_tags($output)), true);
    expect($decoded)->toBe($testData);
});

test('dump mode uses var_dump', function () {
    $this->config->set('display.mode', 'dump');
    
    $displayResult = $this->reflectionClass->getMethod('displayResult');
    $displayResult->setAccessible(true);
    
    // Test with array
    $testData = ['test' => 'value'];
    
    ob_start();
    $displayResult->invoke($this->session, $testData);
    $output = ob_get_clean();
    
    // var_dump output contains specific patterns
    expect($output)->toContain('array(');
    expect($output)->toContain('string(');
});

test('verbose mode shows detailed object information', function () {
    $this->config->set('display.mode', 'verbose');
    
    $displayResult = $this->reflectionClass->getMethod('displayResult');
    $displayResult->setAccessible(true);
    
    $object = new TestClassForModes();
    
    ob_start();
    $displayResult->invoke($this->session, $object);
    $output = ob_get_clean();
    
    // Should show detailed information
    expect($output)->toContain('═══ Object Details ═══');
    expect($output)->toContain('Class:');
    expect($output)->toContain('Properties:');
    expect($output)->toContain('public $publicProp');
    expect($output)->toContain('protected $protectedProp');
    expect($output)->toContain('private $privateProp');
    expect($output)->toContain('Public Methods:');
    expect($output)->toContain('publicMethod');
});

test('verbose mode shows detailed array information', function () {
    $this->config->set('display.mode', 'verbose');
    
    $displayResult = $this->reflectionClass->getMethod('displayResult');
    $displayResult->setAccessible(true);
    
    $array = [
        'name' => 'Alice',
        'age' => 30,
        'hobbies' => ['reading', 'coding'],
    ];
    
    ob_start();
    $displayResult->invoke($this->session, $array);
    $output = ob_get_clean();
    
    expect($output)->toContain('═══ Array Details ═══');
    expect($output)->toContain('Type: Associative');
    expect($output)->toContain('Count: 3');
    expect($output)->toContain("['name']");
    expect($output)->toContain("['age']");
    expect($output)->toContain("['hobbies']");
});

test('mode command shows current mode when no argument', function () {
    // The setDisplayMode method is on the context, not the session
    ob_start();
    $this->context->setDisplayMode('', $this->output, $this->context);
    $output = ob_get_clean();
    
    expect($output)->toContain('Display Mode');
    expect($output)->toContain('Current mode:');
    expect($output)->toContain('Available modes:');
    expect($output)->toContain('compact');
    expect($output)->toContain('verbose');
    expect($output)->toContain('json');
    expect($output)->toContain('dump');
});

test('mode command changes display mode', function () {
    // Initial mode
    expect($this->config->get('display.mode'))->toBe('compact');
    
    // Change mode
    ob_start();
    $this->context->setDisplayMode('verbose', $this->output, $this->context);
    $output = ob_get_clean();
    
    expect($output)->toContain('Display mode changed to: verbose');
    expect($this->config->get('display.mode'))->toBe('verbose');
});

test('mode command rejects invalid modes', function () {
    ob_start();
    $this->context->setDisplayMode('invalid', $this->output, $this->context);
    $output = ob_get_clean();
    
    expect($output)->toContain('Invalid mode: invalid');
    expect($output)->toContain('Valid modes:');
    
    // Mode should not change
    expect($this->config->get('display.mode'))->toBe('compact');
});

test('json mode handles objects that cannot be JSON encoded', function () {
    $this->config->set('display.mode', 'json');
    
    $displayResult = $this->reflectionClass->getMethod('displayResult');
    $displayResult->setAccessible(true);
    
    // Create a recursive structure that cannot be JSON encoded
    $recursive = new \stdClass();
    $recursive->self = $recursive;
    
    ob_start();
    $displayResult->invoke($this->session, $recursive);
    $output = ob_get_clean();
    
    // Should fall back to compact mode with error message
    expect($output)->toContain('Cannot display as JSON');
});

test('verbose mode truncates large arrays', function () {
    $this->config->set('display.mode', 'verbose');
    
    $displayResult = $this->reflectionClass->getMethod('displayResult');
    $displayResult->setAccessible(true);
    
    // Create array with more than 20 items
    $largeArray = array_fill(0, 30, 'value');
    
    ob_start();
    $displayResult->invoke($this->session, $largeArray);
    $output = ob_get_clean();
    
    expect($output)->toContain('═══ Array Details ═══');
    expect($output)->toContain('Count: 30');
    expect($output)->toContain('... and 10 more items');
});