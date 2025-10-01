<?php

declare(strict_types=1);

namespace Yalla\Commands;

class CommandRegistry
{
    private array $commands = [];

    private array $aliases = [];

    public function register(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    public function registerAlias(string $alias, string $commandName): void
    {
        $this->aliases[$alias] = $commandName;
    }

    public function get(string $name): ?Command
    {
        // Check if it's an alias first
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        return $this->commands[$name] ?? null;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function resolveAlias(string $alias): ?string
    {
        return $this->aliases[$alias] ?? null;
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
