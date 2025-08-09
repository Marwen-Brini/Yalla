<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\CommandRegistry;
use Yalla\Output\Output;

class DummyCommand extends Command
{
    public function __construct(string $name = 'dummy')
    {
        $this->name = $name;
        $this->description = 'Dummy command';
    }

    public function execute(array $input, Output $output): int
    {
        return 0;
    }
}

it('can register and retrieve commands', function () {
    $registry = new CommandRegistry;
    $command = new DummyCommand('test');

    $registry->register($command);

    expect($registry->has('test'))->toBeTrue();
    expect($registry->get('test'))->toBe($command);
});

it('returns null for non-existent commands', function () {
    $registry = new CommandRegistry;

    expect($registry->get('nonexistent'))->toBeNull();
    expect($registry->has('nonexistent'))->toBeFalse();
});

it('can retrieve all registered commands', function () {
    $registry = new CommandRegistry;
    $command1 = new DummyCommand('test1');
    $command2 = new DummyCommand('test2');

    $registry->register($command1);
    $registry->register($command2);

    $all = $registry->all();

    expect($all)->toHaveCount(2);
    expect($all['test1'])->toBe($command1);
    expect($all['test2'])->toBe($command2);
});
