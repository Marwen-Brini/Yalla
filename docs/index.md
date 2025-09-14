---
layout: home

hero:
  name: "Yalla CLI"
  text: "Build powerful CLI applications with PHP"
  tagline: A standalone PHP CLI framework built from scratch without dependencies
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/marwen-brini/yalla

features:
  - icon: 🚀
    title: Zero Dependencies
    details: Built entirely from scratch without relying on Symfony Console or other frameworks
  - icon: 🎨
    title: Beautiful Output
    details: ANSI color support, table rendering, progress bars, and more formatting options
  - icon: 🔧
    title: Interactive REPL
    details: Full-featured Read-Eval-Print-Loop for interactive PHP development with history and autocomplete
  - icon: 📦
    title: Command Scaffolding
    details: Built-in command generator to quickly create new commands with proper structure
  - icon: 🧪
    title: 100% Test Coverage
    details: Fully tested with Pest PHP, ensuring reliability and maintainability
  - icon: 🌍
    title: Cross-Platform
    details: Works seamlessly on Windows, macOS, and Linux with platform-specific optimizations
---

## Quick Example

```php
<?php

use Yalla\Commands\Command;
use Yalla\Output\Output;

class GreetCommand extends Command
{
    public function __construct()
    {
        $this->name = 'greet';
        $this->description = 'Greet someone';

        $this->addArgument('name', 'The name to greet', true);
        $this->addOption('yell', 'y', 'Yell the greeting', false);
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $message = "Hello, $name!";

        if ($this->getOption($input, 'yell')) {
            $message = strtoupper($message);
        }

        $output->success($message);

        return 0;
    }
}
```

## Installation

```bash
composer require marwen-brini/yalla
```

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- Composer 2.0+

## License

MIT © 2025 Marwen-Brini