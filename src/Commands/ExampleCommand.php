<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

class ExampleCommand extends Command
{
    public function __construct()
    {
        $this->name = 'example';
        $this->description = 'An example command';

        $this->addArgument('name', 'Your name', 'World');
        $this->addOption('greeting', 'g', 'Custom greeting', 'Hello');
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $greeting = $this->getOption($input, 'greeting');

        $output->success("$greeting, $name!");
        $output->writeln('');
        $output->info('This is an example command.');
        $output->dim('Customize it or create your own commands.');

        return 0;
    }
}
