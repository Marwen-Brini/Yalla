# Yalla REPL Documentation

## Installation

When you install Yalla via Composer in your project:

```bash
composer require marwen-brini/yalla
```

## Setup

### 1. Initialize REPL Configuration

After installation, create a REPL configuration file for your project:

```bash
./vendor/bin/yalla init:repl
```

This creates a `repl.config.php` file in your project root with default settings.

### 2. Customize Configuration

Edit `repl.config.php` to add project-specific settings:

```php
return [
    // Add your custom REPL extensions
    'extensions' => [
        \App\Repl\CustomExtension::class,
    ],
    
    // Define shortcuts to frequently used classes
    'shortcuts' => [
        'User' => \App\Models\User::class,
        'Post' => \App\Models\Post::class,
        'DB' => \Illuminate\Support\Facades\DB::class,
    ],
    
    // Auto-import common classes
    'imports' => [
        \Carbon\Carbon::class,
        ['class' => \Illuminate\Support\Str::class, 'alias' => 'Str'],
    ],
    
    // Customize the prompt
    'display' => [
        'prompt' => '[{counter}] myapp> ',
        'welcome' => true,
        'colors' => true,
    ],
];
```

## Usage

### Starting the REPL

```bash
./vendor/bin/yalla repl
```

Or with a custom config file:

```bash
./vendor/bin/yalla repl --config=custom-repl.config.php
```

### REPL Commands

- `:help` - Show available commands
- `:exit` - Exit the REPL
- `:clear` - Clear the screen
- `:history` - Show command history
- `:vars` - Show defined variables
- `:imports` - Show imported classes

### Using Shortcuts

With shortcuts configured, you can use them directly:

```php
[1] > User::find(1)
[2] > Post::where('status', 'published')->get()
```

## Creating Custom Extensions

Create a class implementing `Yalla\Repl\ReplExtension`:

```php
<?php

namespace App\Repl;

use Yalla\Repl\ReplExtension;
use Yalla\Repl\ReplContext;

class CustomExtension implements ReplExtension
{
    public function register(ReplContext $context): void
    {
        // Add custom commands
        $context->addCommand('models', function($args, $output) {
            $output->writeln('Available models:');
            // List your models
        });
        
        // Add shortcuts
        $context->addShortcut('Cache', \Illuminate\Support\Facades\Cache::class);
        
        // Add custom completers
        $context->addCompleter('models', function($partial) {
            return ['User', 'Post', 'Comment'];
        });
    }
    
    public function boot(): void
    {
        // Additional initialization
    }
    
    public function getName(): string
    {
        return 'CustomExtension';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function getDescription(): string
    {
        return 'Custom REPL extension for my app';
    }
}
```

Then register it in your `repl.config.php`:

```php
'extensions' => [
    \App\Repl\CustomExtension::class,
],
```

## Framework Integration Examples

### Laravel

```php
// repl.config.php
return [
    'bootstrap' => [
        'file' => __DIR__ . '/vendor/autoload.php',
        'files' => [
            __DIR__ . '/bootstrap/app.php',
        ]
    ],
    
    'shortcuts' => [
        'User' => \App\Models\User::class,
        'DB' => \Illuminate\Support\Facades\DB::class,
        'Cache' => \Illuminate\Support\Facades\Cache::class,
        'Log' => \Illuminate\Support\Facades\Log::class,
    ],
];
```

### Symfony

```php
// repl.config.php
return [
    'bootstrap' => [
        'file' => __DIR__ . '/vendor/autoload.php',
        'files' => [
            __DIR__ . '/config/bootstrap.php',
        ]
    ],
    
    'variables' => [
        'kernel' => new \App\Kernel('dev', true),
        'container' => $kernel->getContainer(),
    ],
];
```

## Tips

1. **Performance Mode**: Enable performance tracking to see execution times:
   ```php
   'display' => ['performance' => true]
   ```

2. **Stack Traces**: Enable full stack traces for debugging:
   ```php
   'display' => ['stacktrace' => true]
   ```

3. **Custom History File**: Store history per project:
   ```php
   'history' => ['file' => __DIR__ . '/.repl_history']
   ```

4. **Disable Colors**: For terminals that don't support ANSI colors:
   ```bash
   ./vendor/bin/yalla repl --no-colors
   ```