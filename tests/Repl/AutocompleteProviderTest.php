<?php

declare(strict_types=1);

use Yalla\Repl\Autocomplete\AutocompleteProvider;
use Yalla\Repl\ReplContext;
use Yalla\Repl\ReplConfig;

beforeEach(function () {
    $this->config = new ReplConfig();
    $this->context = new ReplContext($this->config);
    $this->autocomplete = new AutocompleteProvider($this->context);
});

test('returns empty array for empty input', function () {
    $completions = $this->autocomplete->complete('');
    
    expect($completions)->toBe([]);
});

test('completes REPL commands starting with colon', function () {
    $completions = $this->autocomplete->complete(':he');
    
    expect($completions)->toContain(':help');
    expect($completions)->not->toContain(':exit');
    expect($completions)->not->toContain(':history');
});

test('completes all REPL commands with just colon', function () {
    $completions = $this->autocomplete->complete(':');
    
    expect($completions)->toContain(':help');
    expect($completions)->toContain(':exit');
    expect($completions)->toContain(':clear');
    expect($completions)->toContain(':history');
    expect($completions)->toContain(':vars');
    expect($completions)->toContain(':imports');
});

test('completes class shortcuts', function () {
    $this->context->addShortcut('User', '\App\Models\User');
    $this->context->addShortcut('Post', '\App\Models\Post');
    $this->context->addShortcut('Product', '\App\Models\Product');
    
    $completions = $this->autocomplete->complete('Po');
    
    expect($completions)->toContain('Post::');
    expect($completions)->not->toContain('User::');
    expect($completions)->not->toContain('Product::'); // Product doesn't start with 'Po'
});

test('completes variables starting with dollar sign', function () {
    $this->context->setVariable('name', 'Test');
    $this->context->setVariable('number', 42);
    $this->context->setVariable('data', []);
    
    $completions = $this->autocomplete->complete('$n');
    
    expect($completions)->toContain('$name');
    expect($completions)->toContain('$number');
    expect($completions)->not->toContain('$data');
});

test('completes PHP built-in functions', function () {
    $completions = $this->autocomplete->complete('str_rep');
    
    expect($completions)->toContain('str_repeat()');
    expect($completions)->toContain('str_replace()');
    
    // Should limit to reasonable number
    $completions = $this->autocomplete->complete('str_');
    expect(count($completions))->toBeLessThanOrEqual(20);
});

test('uses cache for repeated completions', function () {
    // First call
    $completions1 = $this->autocomplete->complete(':he');
    
    // Second call with same input should use cache
    $completions2 = $this->autocomplete->complete(':he');
    
    expect($completions1)->toBe($completions2);
});

test('completes case insensitive for shortcuts', function () {
    $this->context->addShortcut('User', '\App\Models\User');
    $this->context->addShortcut('UserProfile', '\App\Models\UserProfile');
    
    $completions = $this->autocomplete->complete('user');
    
    expect($completions)->toContain('UserProfile::');
    // Note: stripos is used for case-insensitive matching
});

test('custom completers from extensions work', function () {
    $customCompleter = function($partial, $context) {
        if (str_starts_with('custom', $partial)) {
            return ['custom_completion'];
        }
        return [];
    };
    
    $this->context->addCompleter('custom', $customCompleter);
    
    $completions = $this->autocomplete->complete('cust');
    
    expect($completions)->toContain('custom_completion');
});

test('respects max suggestions configuration', function () {
    // Set max suggestions to 5
    $this->config->set('autocomplete.max_suggestions', 5);
    
    // Add many shortcuts
    for ($i = 0; $i < 10; $i++) {
        $this->context->addShortcut("Class$i", "\\App\\Models\\Class$i");
    }
    
    $completions = $this->autocomplete->complete('Class');
    
    expect(count($completions))->toBe(5);
});

test('handles mixed completion types', function () {
    $this->context->addShortcut('Helper', '\App\Helpers\Helper');
    $this->context->setVariable('helper', 'value');
    
    // Test with 'Hel' to match Helper shortcut
    $completions = $this->autocomplete->complete('Hel');
    expect($completions)->toContain('Helper::');
    
    // Test with ':hel' to match :help command
    $completions2 = $this->autocomplete->complete(':hel');
    expect($completions2)->toContain(':help');
});

test('returns unique completions', function () {
    // Add a custom completer that returns duplicates
    $this->context->addCompleter('test', function($partial) {
        return [':help', ':help', ':help'];
    });
    
    $completions = $this->autocomplete->complete(':help');
    
    // array_unique is applied, so no duplicates
    $uniqueCompletions = array_unique($completions);
    expect(count($completions))->toBe(count($uniqueCompletions));
});