# Getting Started

## Introduction

Yalla CLI is a standalone PHP CLI framework designed to help you build powerful command-line applications without external dependencies. Unlike other PHP CLI frameworks that rely on heavy dependencies like Symfony Console, Yalla is built entirely from scratch, giving you complete control and understanding of your CLI application.

## Why Yalla?

- **Zero Dependencies**: No hidden complexity or bloat from third-party packages
- **Educational**: Perfect for learning how CLI frameworks work under the hood
- **Lightweight**: Minimal footprint, fast execution
- **Feature-Rich**: Despite being dependency-free, includes all essential CLI features
- **Modern PHP**: Built with PHP 8.1+ features and best practices

## Key Features

### Core Features
- Command routing and execution
- Argument and option parsing (both long and short formats)
- Colored terminal output
- Table rendering with Unicode box drawing
- Progress bars and spinners
- Command validation

### Interactive REPL
- Full PHP REPL with evaluation
- Command history
- Autocomplete support
- Variable persistence
- Multiple display modes
- Custom extensions

### Developer Experience
- Command scaffolding generator
- 100% test coverage
- Cross-platform support
- Comprehensive documentation

## Quick Start

### 1. Install via Composer

```bash
composer require marwen-brini/yalla
```

### 2. Create Your CLI Application

Create a file `bin/yalla`:

```php
#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use Yalla\Application;

$app = new Application('My CLI App', '1.0.0');
$app->run();
```

### 3. Make it Executable

```bash
chmod +x bin/yalla
```

### 4. Run Your Application

```bash
./bin/yalla
```

This will display the list of available commands. You can now start adding your own custom commands!

## Next Steps

- [Installation Guide](./installation.md) - Detailed installation instructions
- [Creating Commands](./commands.md) - Learn how to create custom commands
- [REPL Documentation](./repl.md) - Explore the interactive REPL features
- [API Reference](/api/application) - Complete API documentation