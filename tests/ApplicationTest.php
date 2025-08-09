<?php

declare(strict_types=1);

use Yalla\Application;
use Yalla\Commands\Command;
use Yalla\Output\Output;

class MockCommand extends Command
{
    public function __construct()
    {
        $this->name = 'mock';
        $this->description = 'Mock command';
    }

    public function execute(array $input, Output $output): int
    {
        $output->writeln('Mock executed');

        return 0;
    }
}

class FailingCommand extends Command
{
    public function __construct()
    {
        $this->name = 'fail';
        $this->description = 'Failing command';
    }

    public function execute(array $input, Output $output): int
    {
        throw new Exception('Command failed');
    }
}

it('can create an application instance', function () {
    $app = new Application('Test CLI', '1.0.0');

    expect($app)->toBeInstanceOf(Application::class);
    expect($app->getName())->toBe('Test CLI');
    expect($app->getVersion())->toBe('1.0.0');
});

it('has default name and version', function () {
    $app = new Application;

    expect($app->getName())->toBe('Yalla CLI');
    expect($app->getVersion())->toBe('1.0.0');
});

it('can register custom commands', function () {
    $app = new Application;
    $command = new MockCommand;

    $result = $app->register($command);

    expect($result)->toBe($app);
});

it('runs default list command when no command provided', function () {
    $app = new Application;

    $_SERVER['argv'] = ['yalla'];

    ob_start();
    $exitCode = $app->run();
    ob_end_clean();

    expect($exitCode)->toBe(0);
});

it('runs specified command', function () {
    $app = new Application;
    $app->register(new MockCommand);

    $_SERVER['argv'] = ['yalla', 'mock'];

    ob_start();
    $exitCode = $app->run();
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Mock executed');
});

it('returns error for non-existent command', function () {
    $app = new Application;

    $_SERVER['argv'] = ['yalla', 'nonexistent'];

    ob_start();
    $exitCode = $app->run();
    $output = ob_get_clean();

    expect($exitCode)->toBe(1);
    expect($output)->toContain("Command 'nonexistent' not found");
});

it('handles command exceptions gracefully', function () {
    $app = new Application;
    $app->register(new FailingCommand);

    $_SERVER['argv'] = ['yalla', 'fail'];

    ob_start();
    $exitCode = $app->run();
    $output = ob_get_clean();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('Command failed');
});

it('handles missing argv gracefully', function () {
    $app = new Application;

    unset($_SERVER['argv']);

    ob_start();
    $exitCode = $app->run();
    ob_end_clean();

    expect($exitCode)->toBe(0);
});
