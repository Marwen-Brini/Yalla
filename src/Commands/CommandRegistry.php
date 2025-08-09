<?php

declare(strict_types=1);

namespace Yalla\Commands;

class CommandRegistry
{
    private array $commands = [];

    public function register(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    public function get(string $name): ?Command
    {
        return $this->commands[$name] ?? null;
    }

    public function all(): array
    {
        return $this->commands;
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }
}
