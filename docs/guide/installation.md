# Installation

## Requirements

Before installing Yalla CLI, ensure your system meets the following requirements:

- **PHP**: 8.1, 8.2, 8.3, or 8.4
- **Composer**: 2.0 or higher
- **Operating System**: Windows, macOS, or Linux

## Installation Methods

### Via Composer (Recommended)

The recommended way to install Yalla is through [Composer](https://getcomposer.org/):

```bash
composer require marwen-brini/yalla
```

This will install Yalla and set up the autoloader automatically.

### Manual Installation

If you prefer to install manually:

1. Clone the repository:
```bash
git clone https://github.com/Marwen-Brini/Yalla.git
cd Yalla
```

2. Install dependencies:
```bash
composer install
```

3. Include the autoloader in your project:
```php
require_once 'path/to/Yalla/vendor/autoload.php';
```

## Setting Up Your First CLI

### 1. Create the CLI Entry Point

Create a new file `bin/cli` (or any name you prefer):

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Yalla\Application;

$app = new Application('My CLI', '1.0.0');
$app->run();
```

### 2. Make it Executable

On Unix-like systems (Linux, macOS):

```bash
chmod +x bin/cli
```

On Windows, you can create a batch file `cli.bat`:

```batch
@echo off
php "%~dp0\cli" %*
```

### 3. Test Your Installation

Run your CLI application:

```bash
./bin/cli
```

You should see the default command list output:

```
╔══════════════════════════════╗
║     My CLI v1.0.0           ║
╚══════════════════════════════╝

Available commands:
  help              Show help for a command
  list              List all available commands
  create:command    Create a new command class
  repl              Start an interactive REPL session
```

## Global Installation

To make your CLI available globally:

### On Unix-like Systems

1. Move your CLI to a directory in your PATH:
```bash
sudo ln -s /path/to/your/bin/cli /usr/local/bin/mycli
```

2. Now you can run it from anywhere:
```bash
mycli
```

### On Windows

1. Add your `bin` directory to the system PATH
2. Or copy your files to a directory already in PATH

## Development Setup

For developing with Yalla:

### 1. Install Development Dependencies

```bash
composer install --dev
```

### 2. Run Tests

```bash
composer test
```

### 3. Check Code Coverage

```bash
composer test-coverage
```

### 4. Format Code

```bash
composer format
```

## Troubleshooting

### Common Issues

#### "Command not found" Error
- Ensure the shebang line (`#!/usr/bin/env php`) is correct
- Check that PHP is in your system PATH
- Verify file permissions are set correctly

#### Autoloader Not Found
- Run `composer install` to generate the autoloader
- Check the path to `vendor/autoload.php` is correct

#### PHP Version Error
- Verify your PHP version: `php -v`
- Ensure you have PHP 8.1 or higher installed

## Next Steps

Now that you have Yalla installed, you can:

- [Create your first command](./commands.md)
- [Learn about arguments and options](./arguments-options.md)
- [Explore the REPL](./repl.md)
- [Set up testing](./testing.md)