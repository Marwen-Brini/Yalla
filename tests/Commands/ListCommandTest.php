<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\CommandRegistry;
use Yalla\Commands\ListCommand;
use Yalla\Output\Output;

class ExampleCommand extends Command
{
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->description = "Description for $name";
    }

    public function execute(array $input, Output $output): int
    {
        return 0;
    }
}

it('lists all registered commands', function () {
    $registry = new CommandRegistry;
    $registry->register(new ExampleCommand('test1'));
    $registry->register(new ExampleCommand('test2'));

    $listCommand = new ListCommand($registry);
    $output = new Output;

    $input = [
        'command' => 'list',
        'arguments' => [],
        'options' => [],
    ];

    ob_start();
    $result = $listCommand->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(0);
    expect($capturedOutput)->toContain('Yalla CLI');
    expect($capturedOutput)->toContain('Available commands:');
    expect($capturedOutput)->toContain('test1');
    expect($capturedOutput)->toContain('test2');
    expect($capturedOutput)->toContain('Description for test1');
    expect($capturedOutput)->toContain('Description for test2');
});
