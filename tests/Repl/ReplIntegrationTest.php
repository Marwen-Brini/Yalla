<?php

declare(strict_types=1);

namespace Tests\Repl;

use Yalla\Output\Output;
use Yalla\Repl\ReplConfig;
use Yalla\Repl\ReplContext;
use Yalla\Repl\ReplSession;
use ReflectionClass;

// ORM-like model with protected properties (simulating Eloquent/Doctrine)
class ORMModel {
    protected $id;
    protected $title;
    protected $status;
    
    public function __construct($id, $title, $status = 'active') {
        $this->id = $id;
        $this->title = $title;
        $this->status = $status;
    }
}

// WordPress Post-like model
class Post {
    public $ID;
    public $post_title;
    protected $post_content;
    
    public function __construct($id, $title, $content = '') {
        $this->ID = $id;
        $this->post_title = $title;
        $this->post_content = $content;
    }
    
    public static function count() {
        return 42;
    }
}

beforeEach(function () {
    $this->config = new ReplConfig();
    $this->context = new ReplContext($this->config);
    $this->output = new Output();
    $this->session = new ReplSession($this->context, $this->output, $this->config);
    
    $this->reflectionClass = new ReflectionClass($this->session);
});

test('handles ORM models with protected properties correctly', function () {
    $displayArray = $this->reflectionClass->getMethod('displayArray');
    $displayArray->setAccessible(true);
    
    // Array of ORM models (like Eloquent collection)
    $models = [
        new ORMModel(1, 'First Post'),
        new ORMModel(2, 'Second Post'),
        new ORMModel(3, 'Third Post'),
    ];
    
    // Capture output
    ob_start();
    $displayArray->invoke($this->session, $models);
    $output = ob_get_clean();
    
    // Should display as list, not empty table
    expect($output)->toContain('[');
    expect($output)->toContain(']');
    expect($output)->toContain('ORMModel object');
    expect($output)->not->toContain('│'); // No table borders
});

test('handles WordPress-like Post models correctly', function () {
    $formatValue = $this->reflectionClass->getMethod('formatValue');
    $formatValue->setAccessible(true);
    
    $post = new Post(123, 'Hello World', 'Content here');
    $result = $formatValue->invoke($this->session, $post);
    
    // Should show class name and public properties
    expect($result)->toContain('Post');
    expect($result)->toContain('ID: 123');
    expect($result)->toContain('post_title: "Hello World"');
});

test('Post::count() works with semicolon', function () {
    $evaluateExpression = $this->reflectionClass->getMethod('evaluateExpression');
    $evaluateExpression->setAccessible(true);
    
    // This was the original failing case from KNOWN-ISSUES.md
    $result = $evaluateExpression->invoke($this->session, 'Tests\Repl\Post::count();');
    expect($result)->toBe(42);
    
    // Also test without semicolon
    $result = $evaluateExpression->invoke($this->session, 'Tests\Repl\Post::count()');
    expect($result)->toBe(42);
});

test('mixed arrays display correctly', function () {
    $displayArray = $this->reflectionClass->getMethod('displayArray');
    $displayArray->setAccessible(true);
    
    // Mixed array of arrays and objects
    $mixed = [
        ['id' => 1, 'name' => 'Array Item'],
        new ORMModel(2, 'Object Item'),
        ['id' => 3, 'name' => 'Another Array'],
        new Post(4, 'Post Object'),
    ];
    
    // Capture output
    ob_start();
    $displayArray->invoke($this->session, $mixed);
    $output = ob_get_clean();
    
    // Should display as list since it's mixed
    expect($output)->toContain('[');
    expect($output)->toContain(']');
    expect($output)->not->toContain('│'); // No table borders
    expect($output)->toContain('ORMModel object');
    expect($output)->toContain('Post');
});

test('arrays of arrays display as table when appropriate', function () {
    $isTableArray = $this->reflectionClass->getMethod('isTableArray');
    $isTableArray->setAccessible(true);
    
    // Homogeneous array of arrays - should be table
    $data = [
        ['id' => 1, 'name' => 'Alice', 'age' => 30],
        ['id' => 2, 'name' => 'Bob', 'age' => 25],
        ['id' => 3, 'name' => 'Charlie', 'age' => 35],
        ['id' => 4, 'name' => 'Diana', 'age' => 28],
    ];
    
    expect($isTableArray->invoke($this->session, $data))->toBeTrue();
    
    // Array with inconsistent keys - should not be table
    $inconsistent = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'title' => 'Bob'], // Different key
    ];
    
    expect($isTableArray->invoke($this->session, $inconsistent))->toBeFalse();
});

test('empty collections display correctly', function () {
    $displayArray = $this->reflectionClass->getMethod('displayArray');
    $displayArray->setAccessible(true);
    
    // Capture output for empty array
    ob_start();
    $displayArray->invoke($this->session, []);
    $output = ob_get_clean();
    
    expect(trim($output))->toBe('[]');
});

test('small arrays display inline', function () {
    $displayArray = $this->reflectionClass->getMethod('displayArray');
    $displayArray->setAccessible(true);
    
    // Small array (3 or fewer items) should display inline
    $small = [1, 2, 3];
    
    ob_start();
    $displayArray->invoke($this->session, $small);
    $output = ob_get_clean();
    
    // Should be on one line
    expect(substr_count($output, "\n"))->toBe(1); // Only one newline at the end
    expect($output)->toContain('[');
    expect($output)->toContain(']');
});