<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

class HelpCommand extends Command
{
    private CommandRegistry $registry;

    public function __construct(CommandRegistry $registry)
    {
        $this->name = 'help';
        $this->description = 'Display help for a command';
        $this->registry = $registry;
        
        $this->addArgument('command_name', 'The command name', false);
    }

    public function execute(array $input, Output $output): int
    {
        $commandName = $this->getArgument($input, 'command_name');
        
        if ($commandName) {
            return $this->showCommandHelp($commandName, $output);
        }
        
        return $this->showGeneralHelp($output);
    }

    private function showCommandHelp(string $commandName, Output $output): int
    {
        $command = $this->registry->get($commandName);
        
        if (!$command) {
            $output->error("Command '$commandName' not found.");
            return 1;
        }
        
        $output->writeln($output->color('Description:', Output::YELLOW));
        $output->writeln('  ' . $command->getDescription());
        $output->writeln('');
        
        $output->writeln($output->color('Usage:', Output::YELLOW));
        $usage = '  ' . $commandName;
        
        foreach ($command->getOptions() as $option) {
            $usage .= ' [--' . $option['name'];
            if ($option['shortcut']) {
                $usage .= '|-' . $option['shortcut'];
            }
            $usage .= ']';
        }
        
        foreach ($command->getArguments() as $argument) {
            if ($argument['required']) {
                $usage .= ' <' . $argument['name'] . '>';
            } else {
                $usage .= ' [' . $argument['name'] . ']';
            }
        }
        
        $output->writeln($usage);
        
        if (!empty($command->getArguments())) {
            $output->writeln('');
            $output->writeln($output->color('Arguments:', Output::YELLOW));
            foreach ($command->getArguments() as $argument) {
                $argLine = '  ' . $output->color($argument['name'], Output::GREEN);
                $argLine .= '  ' . $argument['description'];
                if ($argument['required']) {
                    $argLine .= ' ' . $output->color('(required)', Output::RED);
                }
                $output->writeln($argLine);
            }
        }
        
        if (!empty($command->getOptions())) {
            $output->writeln('');
            $output->writeln($output->color('Options:', Output::YELLOW));
            foreach ($command->getOptions() as $option) {
                $optLine = '  --' . $output->color($option['name'], Output::GREEN);
                if ($option['shortcut']) {
                    $optLine .= ', -' . $output->color($option['shortcut'], Output::GREEN);
                }
                $optLine .= '  ' . $option['description'];
                $output->writeln($optLine);
            }
        }
        
        return 0;
    }

    private function showGeneralHelp(Output $output): int
    {
        $output->writeln($output->color('Yalla CLI', Output::CYAN));
        $output->writeln('');
        $output->writeln('Usage:');
        $output->writeln('  command [options] [arguments]');
        $output->writeln('');
        $output->writeln('Available commands:');
        
        foreach ($this->registry->all() as $command) {
            $output->writeln(sprintf('  %-15s %s', 
                $output->color($command->getName(), Output::GREEN),
                $command->getDescription()
            ));
        }
        
        return 0;
    }
}