<?php

declare(strict_types=1);

use Yalla\Repl\ReplCommand;
use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;
use Yalla\Output\Output;

test('can create ReplCommand with default options', function () {
    $command = new ReplCommand();
    
    expect($command->getName())->toBe('repl');
    expect($command->getDescription())->toBe('Start an interactive REPL session');
    
    // Test that options are set up
    $options = $command->getOptions();
    expect($options)->toBeArray();
    expect(count($options))->toBe(5); // config, bootstrap, no-history, no-colors, quiet
});

test('can register extensions', function () {
    $command = new ReplCommand();
    
    // Create a simple mock extension
    $extension = new class implements ReplExtension {
        public function register(ReplContext $context): void {}
        public function boot(): void {}
        public function getName(): string { return 'test'; }
        public function getVersion(): string { return '1.0.0'; }
        public function getDescription(): string { return 'Test extension'; }
    };
    
    $result = $command->registerExtension($extension);
    
    expect($result)->toBe($command);
});

test('chaining works for registerExtension', function () {
    $command = new ReplCommand();
    
    // Create simple mock extensions
    $extension1 = new class implements ReplExtension {
        public function register(ReplContext $context): void {}
        public function boot(): void {}
        public function getName(): string { return 'test1'; }
        public function getVersion(): string { return '1.0.0'; }
        public function getDescription(): string { return 'Test extension 1'; }
    };
    
    $extension2 = new class implements ReplExtension {
        public function register(ReplContext $context): void {}
        public function boot(): void {}
        public function getName(): string { return 'test2'; }
        public function getVersion(): string { return '1.0.0'; }
        public function getDescription(): string { return 'Test extension 2'; }
    };
    
    $result = $command->registerExtension($extension1)
                      ->registerExtension($extension2);
    
    expect($result)->toBe($command);
});