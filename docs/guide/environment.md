# Environment Management

::: tip New in v2.0
Environment management with .env file support is a new feature in Yalla CLI v2.0.
:::

## Overview

The `Environment` class provides a robust way to manage environment variables with `.env` file support, type-safe getters, and variable expansion.

## Basic Usage

### Loading Environment Variables

```php
<?php

use Yalla\Environment\Environment;

// Load default .env file
$env = new Environment();

// Load multiple .env files
$env = new Environment(['.env', '.env.local', '.env.production']);

// Load manually
$env = new Environment([]);
$env->load();
```

### Accessing Variables

```php
// Get variable with default
$dbHost = $env->get('DB_HOST', 'localhost');

// Get required variable (throws exception if missing)
$apiKey = $env->getRequired('API_KEY');

// Check if variable exists
if ($env->has('DEBUG_MODE')) {
    // Variable exists
}
```

## Type-Safe Getters

### String Values

```php
$appName = $env->get('APP_NAME', 'My App');
$dbHost = $env->get('DB_HOST', 'localhost');
```

### Integer Values

```php
$dbPort = $env->getInt('DB_PORT', 3306);
$maxConnections = $env->getInt('MAX_CONNECTIONS', 100);
```

### Float Values

```php
$timeout = $env->getFloat('TIMEOUT', 30.5);
$rate = $env->getFloat('RATE_LIMIT', 1.5);
```

### Boolean Values

```php
$debug = $env->getBool('APP_DEBUG', false);
$maintenance = $env->getBool('MAINTENANCE_MODE', false);

// Recognizes: true, false, 1, 0, yes, no, on, off
```

### Array Values

```php
// Comma-separated values
// ALLOWED_HOSTS=host1.com,host2.com,host3.com
$hosts = $env->getArray('ALLOWED_HOSTS');
// Result: ['host1.com', 'host2.com', 'host3.com']

// With default
$empty = $env->getArray('MISSING_VAR', ['default']);
```

## .env File Format

### Basic Syntax

```bash
# Application settings
APP_NAME="Yalla CLI"
APP_ENV=production
APP_DEBUG=false

# Database configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret

# Features
ENABLE_CACHE=true
CACHE_TTL=3600
```

### Comments

```bash
# This is a comment
APP_NAME=Yalla  # Inline comments are supported

# Multi-line values are not supported
```

### Quoted Values

```bash
# Without quotes
APP_NAME=Yalla CLI

# With double quotes (recommended for values with spaces)
APP_NAME="Yalla CLI Application"

# With single quotes
APP_URL='https://example.com'
```

### Special Values

```bash
# Boolean values
DEBUG=true
CACHE=false

# Null value
OPTIONAL_SETTING=null

# Empty value
EMPTY_VAR=empty
EMPTY_VAR=

# Numeric values
PORT=8080
TIMEOUT=30.5
```

## Variable Expansion

Reference other environment variables:

```bash
# Base URL
APP_URL=https://example.com

# API endpoints using expansion
API_URL=${APP_URL}/api
WEBHOOK_URL=${APP_URL}/webhooks

# With default values
DATABASE_URL=${DB_URL:-postgresql://localhost/mydb}
```

Usage:

```php
$apiUrl = $env->get('API_URL');
// Result: https://example.com/api
```

## Environment Detection

### Check Environment Type

```php
// Check if production
if ($env->isProduction()) {
    // Production-specific logic
}

// Check if development
if ($env->isDevelopment()) {
    // Development-specific logic
}

// Check if staging
if ($env->isStaging()) {
    // Staging-specific logic
}

// Check if debug mode
if ($env->isDebug()) {
    $output->info('Debug mode enabled');
}
```

### Setting Environment

```bash
# In .env file
APP_ENV=production
APP_DEBUG=false
```

## Managing Variables

### Set Variables

```php
// Set a variable
$env->set('FEATURE_FLAG', 'enabled');

// Set multiple variables
$env->set('DB_HOST', 'localhost');
$env->set('DB_PORT', '3306');
```

### Get All Variables

```php
$all = $env->getAll();

foreach ($all as $key => $value) {
    echo "$key = $value\n";
}
```

### Clear Variables

```php
// Clear all loaded variables
$env->clear();
```

### Reload Variables

```php
// Reload from files
$env->reload();

// Reload with overwrite
$env->load(overwrite: true);
```

## Advanced Usage

### Multiple Environment Files

Load environment-specific configuration:

```php
// Development
$env = new Environment([
    '.env',
    '.env.local',
    '.env.development'
]);

// Production
$env = new Environment([
    '.env',
    '.env.production'
]);
```

### Precedence

Later files override earlier ones:

```php
// .env
DB_HOST=localhost

// .env.local (overrides .env)
DB_HOST=127.0.0.1

$env = new Environment(['.env', '.env.local']);
echo $env->get('DB_HOST'); // 127.0.0.1
```

### Integration with Commands

```php
use Yalla\Commands\Command;
use Yalla\Environment\Environment;
use Yalla\Output\Output;

class ConfigCommand extends Command
{
    private Environment $env;

    public function __construct()
    {
        parent::__construct();

        $this->name = 'config';
        $this->description = 'Show configuration';

        $this->env = new Environment();
    }

    public function execute(array $input, Output $output): int
    {
        $output->info('Current Configuration:');
        $output->writeln('');

        $output->writeln('APP_NAME: ' . $this->env->get('APP_NAME'));
        $output->writeln('APP_ENV: ' . $this->env->get('APP_ENV'));
        $output->writeln('APP_DEBUG: ' . ($this->env->getBool('APP_DEBUG') ? 'true' : 'false'));

        if ($this->env->isProduction()) {
            $output->warning('Running in PRODUCTION mode');
        }

        return 0;
    }
}
```

### Validation

```php
class DatabaseCommand extends Command
{
    public function execute(array $input, Output $output): int
    {
        $env = new Environment();

        // Validate required variables
        try {
            $host = $env->getRequired('DB_HOST');
            $port = $env->getRequired('DB_PORT');
            $database = $env->getRequired('DB_DATABASE');
        } catch (\RuntimeException $e) {
            $output->error('Missing required environment variable: ' . $e->getMessage());
            return 1;
        }

        // Use variables
        $output->info("Connecting to $host:$port/$database");

        return 0;
    }
}
```

## Best Practices

### 1. Use .env.example

Create a template file:

```bash
# .env.example
APP_NAME=
APP_ENV=production
APP_DEBUG=false

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

### 2. Never Commit .env Files

```bash
# .gitignore
.env
.env.local
.env.*.local
```

### 3. Document Variables

```bash
# .env.example with documentation

# Application name (string)
APP_NAME=Yalla CLI

# Environment: production, development, staging
APP_ENV=production

# Enable debug mode (boolean: true/false)
APP_DEBUG=false

# Database connection settings
DB_HOST=localhost           # Database host
DB_PORT=3306                # Database port (default: 3306)
DB_DATABASE=myapp           # Database name
```

### 4. Use Type-Safe Getters

```php
// Good - Type-safe
$port = $env->getInt('DB_PORT', 3306);
$debug = $env->getBool('APP_DEBUG', false);

// Bad - Requires manual casting
$port = (int) $env->get('DB_PORT', '3306');
$debug = $env->get('APP_DEBUG', 'false') === 'true';
```

### 5. Provide Defaults

```php
// Good - Has default
$timeout = $env->getInt('TIMEOUT', 30);

// Bad - No default, may fail
$timeout = $env->getInt('TIMEOUT');
```

## Error Handling

### Missing Required Variables

```php
try {
    $apiKey = $env->getRequired('API_KEY');
} catch (\RuntimeException $e) {
    $output->error('API_KEY is required but not set');
    return 1;
}
```

### Invalid File

```php
try {
    $env = new Environment(['/path/to/nonexistent.env']);
} catch (\RuntimeException $e) {
    $output->error('Failed to load environment file');
    return 1;
}
```

## See Also

- [Commands](/guide/commands) - Using environment in commands
- [Getting Started](/guide/getting-started) - Application setup
- [Best Practices](#best-practices) - Security and best practices
