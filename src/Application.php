<?php

declare(strict_types=1);

namespace Yalla;

use Yalla\Commands\CommandRegistry;
use Yalla\Commands\HelpCommand;
use Yalla\Commands\ListCommand;
use Yalla\Input\InputParser;
use Yalla\Output\Output;

class Application
{
    private string $name;

    private string $version;

    private CommandRegistry $registry;

    private Output $output;

    private InputParser $input;

    public function __construct(string $name = 'Yalla CLI', string $version = '1.0.0')
    {
        $this->name = $name;
        $this->version = $version;
        $this->registry = new CommandRegistry;
        $this->output = new Output;
        $this->input = new InputParser;

        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands(): void
    {
        $this->registry->register(new HelpCommand($this->registry));
        $this->registry->register(new ListCommand($this->registry));
    }

    public function register($command): self
    {
        $this->registry->register($command);

        return $this;
    }

    public function run(): int
    {
        try {
            $argv = $_SERVER['argv'] ?? [];
            array_shift($argv);

            $parsed = $this->input->parse($argv);

            if (empty($parsed['command'])) {
                $parsed['command'] = 'list';
            }

            $command = $this->registry->get($parsed['command']);

            if (! $command) {
                $this->output->error("Command '{$parsed['command']}' not found.");

                return 1;
            }

            return $command->execute($parsed, $this->output);

        } catch (\Exception $e) {
            $this->output->error($e->getMessage());

            return 1;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
