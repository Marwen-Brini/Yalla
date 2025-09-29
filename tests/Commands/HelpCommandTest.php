<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\CommandRegistry;
use Yalla\Commands\HelpCommand;
use Yalla\Output\Output;

// Helper function to create sample command
function createSampleCommand()
{
    return new class extends Command {
        public function __construct()
        {
            $this->name = 'sample';
            $this->description = 'A sample command';

            $this->addArgument('file', 'Input file', true);
            $this->addArgument('output', 'Output file', false);
            $this->addOption('verbose', 'v', 'Verbose output', false);
            $this->addOption('format', 'f', 'Output format', 'json');
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };
}

it('shows general help when no command specified', function () {
    $registry = new CommandRegistry;
    $registry->register(createSampleCommand());

    $helpCommand = new HelpCommand($registry);
    $output = new Output;

    $input = [
        'command' => 'help',
        'arguments' => [],
        'options' => [],
    ];

    ob_start();
    $result = $helpCommand->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(0);
    expect($capturedOutput)->toContain('Yalla CLI');
    expect($capturedOutput)->toContain('Available commands:');
    expect($capturedOutput)->toContain('sample');
});

it('shows command-specific help', function () {
    $registry = new CommandRegistry;
    $registry->register(createSampleCommand());

    $helpCommand = new HelpCommand($registry);
    $output = new Output;

    $input = [
        'command' => 'help',
        'arguments' => ['sample'],
        'options' => [],
    ];

    ob_start();
    $result = $helpCommand->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(0);
    expect($capturedOutput)->toContain('Description:');
    expect($capturedOutput)->toContain('A sample command');
    expect($capturedOutput)->toContain('Arguments:');
    expect($capturedOutput)->toContain('Options:');
});

it('returns error for non-existent command', function () {
    $registry = new CommandRegistry;

    $helpCommand = new HelpCommand($registry);
    $output = new Output;

    $input = [
        'command' => 'help',
        'arguments' => ['nonexistent'],
        'options' => [],
    ];

    ob_start();
    $result = $helpCommand->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(1);
    expect($capturedOutput)->toContain("Command 'nonexistent' not found");
});
