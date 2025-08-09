<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

abstract class Command
{
    protected string $name;

    protected string $description;

    protected array $arguments = [];

    protected array $options = [];

    abstract public function execute(array $input, Output $output): int;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function addArgument(string $name, string $description, bool $required = false): self
    {
        $this->arguments[] = [
            'name' => $name,
            'description' => $description,
            'required' => $required,
        ];

        return $this;
    }

    protected function addOption(string $name, ?string $shortcut, string $description, $default = null): self
    {
        $this->options[] = [
            'name' => $name,
            'shortcut' => $shortcut,
            'description' => $description,
            'default' => $default,
        ];

        return $this;
    }

    protected function getArgument(array $input, string $name, $default = null)
    {
        $index = array_search($name, array_column($this->arguments, 'name'));

        return $input['arguments'][$index] ?? $default;
    }

    protected function getOption(array $input, string $name, $default = null)
    {
        return $input['options'][$name] ?? $default;
    }
}
