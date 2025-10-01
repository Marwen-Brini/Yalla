<?php

declare(strict_types=1);

use Yalla\Application;
use Yalla\Commands\Command;
use Yalla\Commands\CommandRegistry;
use Yalla\Output\Output;

// Test command for aliasing
class TestAliasCommand extends Command
{
    public function __construct()
    {
        $this->name = 'test:alias';
        $this->description = 'Test command for aliasing';
    }

    public function execute(array $input, Output $output): int
    {
        $output->writeln('Test alias command executed');

        return 0;
    }
}

test('Command can have aliases set', function () {
    $command = new TestAliasCommand;

    expect($command->getAliases())->toBeEmpty();

    $command->setAliases(['ta', 'test-a']);

    expect($command->getAliases())->toBe(['ta', 'test-a']);
});

test('Command can add single alias', function () {
    $command = new TestAliasCommand;

    $command->addAlias('ta');

    expect($command->getAliases())->toBe(['ta']);
    expect($command->hasAlias('ta'))->toBeTrue();
    expect($command->hasAlias('other'))->toBeFalse();
});

test('Command addAlias prevents duplicates', function () {
    $command = new TestAliasCommand;

    $command->addAlias('ta');
    $command->addAlias('ta');

    expect($command->getAliases())->toBe(['ta']);
});

test('Command addAlias is chainable', function () {
    $command = new TestAliasCommand;

    $result = $command->addAlias('ta')->addAlias('test');

    expect($result)->toBe($command);
    expect($command->getAliases())->toBe(['ta', 'test']);
});

test('CommandRegistry can register aliases', function () {
    $registry = new CommandRegistry;
    $command = new TestAliasCommand;

    $registry->register($command);
    $registry->registerAlias('ta', 'test:alias');

    expect($registry->resolveAlias('ta'))->toBe('test:alias');
    expect($registry->resolveAlias('unknown'))->toBeNull();
});

test('CommandRegistry get resolves aliases', function () {
    $registry = new CommandRegistry;
    $command = new TestAliasCommand;

    $registry->register($command);
    $registry->registerAlias('ta', 'test:alias');

    $resolved = $registry->get('ta');

    expect($resolved)->toBe($command);
    expect($resolved->getName())->toBe('test:alias');
});

test('CommandRegistry get returns null for unknown alias', function () {
    $registry = new CommandRegistry;

    expect($registry->get('unknown'))->toBeNull();
});

test('CommandRegistry getAliases returns all aliases', function () {
    $registry = new CommandRegistry;
    $command = new TestAliasCommand;

    $registry->register($command);
    $registry->registerAlias('ta', 'test:alias');
    $registry->registerAlias('test-a', 'test:alias');

    $aliases = $registry->getAliases();

    expect($aliases)->toBe([
        'ta' => 'test:alias',
        'test-a' => 'test:alias',
    ]);
});

test('Application alias method creates alias', function () {
    $app = new Application;
    $command = new TestAliasCommand;

    $app->register($command);
    $result = $app->alias('test:alias', 'ta');

    expect($result)->toBeInstanceOf(Application::class);
    expect($command->hasAlias('ta'))->toBeTrue();
});

test('Application alias method is chainable', function () {
    $app = new Application;
    $command = new TestAliasCommand;

    $app->register($command);
    $result = $app->alias('test:alias', 'ta')
        ->alias('test:alias', 'test-a');

    expect($result)->toBeInstanceOf(Application::class);
    expect($command->getAliases())->toContain('ta');
    expect($command->getAliases())->toContain('test-a');
});

test('Application alias handles non-existent command gracefully', function () {
    $app = new Application;

    $result = $app->alias('nonexistent', 'ne');

    expect($result)->toBeInstanceOf(Application::class);
});

test('Multiple aliases can point to same command', function () {
    $registry = new CommandRegistry;
    $command = new TestAliasCommand;

    $registry->register($command);
    $registry->registerAlias('ta', 'test:alias');
    $registry->registerAlias('test-a', 'test:alias');
    $registry->registerAlias('alias', 'test:alias');

    expect($registry->get('ta'))->toBe($command);
    expect($registry->get('test-a'))->toBe($command);
    expect($registry->get('alias'))->toBe($command);
    expect($registry->get('test:alias'))->toBe($command);
});

test('Command hasAlias checks for alias existence', function () {
    $command = new TestAliasCommand;

    $command->setAliases(['ta', 'test-a', 'alias']);

    expect($command->hasAlias('ta'))->toBeTrue();
    expect($command->hasAlias('test-a'))->toBeTrue();
    expect($command->hasAlias('alias'))->toBeTrue();
    expect($command->hasAlias('unknown'))->toBeFalse();
    expect($command->hasAlias('test:alias'))->toBeFalse();
});

test('setAliases returns self for chaining', function () {
    $command = new TestAliasCommand;

    $result = $command->setAliases(['ta']);

    expect($result)->toBe($command);
});
