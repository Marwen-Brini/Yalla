<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Output\Output;

class TestCommand extends Command
{
    public function __construct()
    {
        $this->name = 'test';
        $this->description = 'Test command';

        $this->addArgument('name', 'Name argument', true);
        $this->addArgument('optional', 'Optional argument', false);
        $this->addOption('force', 'f', 'Force option', false);
        $this->addOption('output', 'o', 'Output format', 'json');
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $optional = $this->getArgument($input, 'optional', 'default');
        $force = $this->getOption($input, 'force', false);
        $outputFormat = $this->getOption($input, 'output', 'json');

        $output->writeln("Name: $name");
        $output->writeln("Optional: $optional");
        $output->writeln('Force: '.($force ? 'yes' : 'no'));
        $output->writeln("Output: $outputFormat");

        return 0;
    }
}

it('can create a command with arguments and options', function () {
    $command = new TestCommand;

    expect($command->getName())->toBe('test');
    expect($command->getDescription())->toBe('Test command');
    expect($command->getArguments())->toHaveCount(2);
    expect($command->getOptions())->toHaveCount(2);
});

it('can get arguments from input', function () {
    $command = new TestCommand;
    $output = new Output;

    $input = [
        'command' => 'test',
        'arguments' => ['John', 'Doe'],
        'options' => [],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(0);
    expect($capturedOutput)->toContain('Name: John');
    expect($capturedOutput)->toContain('Optional: Doe');
});

it('can get options from input', function () {
    $command = new TestCommand;
    $output = new Output;

    $input = [
        'command' => 'test',
        'arguments' => ['John'],
        'options' => ['force' => true, 'output' => 'xml'],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(0);
    expect($capturedOutput)->toContain('Force: yes');
    expect($capturedOutput)->toContain('Output: xml');
});

it('uses default values for missing arguments and options', function () {
    $command = new TestCommand;
    $output = new Output;

    $input = [
        'command' => 'test',
        'arguments' => ['John'],
        'options' => [],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(0);
    expect($capturedOutput)->toContain('Optional: default');
    expect($capturedOutput)->toContain('Force: no');
    expect($capturedOutput)->toContain('Output: json');
});
