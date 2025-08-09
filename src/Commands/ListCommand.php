<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

class ListCommand extends Command
{
    private CommandRegistry $registry;

    public function __construct(CommandRegistry $registry)
    {
        $this->name = 'list';
        $this->description = 'List all available commands';
        $this->registry = $registry;
    }

    public function execute(array $input, Output $output): int
    {
        $output->writeln($output->color('Yalla CLI', Output::CYAN));
        $output->writeln('');
        $output->writeln($output->color('Available commands:', Output::YELLOW));

        $commands = [];
        foreach ($this->registry->all() as $command) {
            $commands[] = [
                $output->color($command->getName(), Output::GREEN),
                $command->getDescription(),
            ];
        }

        $output->table(['Command', 'Description'], $commands);

        $output->writeln('');
        $output->writeln('Run '.$output->color('yalla help [command]', Output::GREEN).' for more information about a command.');

        return 0;
    }
}
