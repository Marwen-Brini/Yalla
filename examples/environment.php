#!/usr/bin/env php
<?php

/**
 * Environment Management Example
 *
 * This example demonstrates the Environment class for managing
 * environment variables and .env files in Yalla CLI
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Environment\Environment;
use Yalla\Output\Output;

$output = new Output;

$output->section('Environment Management Example');

// Create sample .env files for demonstration
$tempDir = sys_get_temp_dir().'/yalla_env_example';
if (! is_dir($tempDir)) {
    mkdir($tempDir);
}

// Create main .env file
$envContent = <<<'ENV'
# Application Settings
APP_NAME="Yalla CLI Demo"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yalla_demo
DB_USERNAME=root
DB_PASSWORD="secret123"

# Using variable expansion
BASE_URL=http://localhost
API_URL=${BASE_URL}/api
WEBHOOK_URL=$BASE_URL/webhooks

# Arrays and Lists
ALLOWED_HOSTS=["localhost", "127.0.0.1", "example.com"]
REDIS_SERVERS=server1.redis.local,server2.redis.local,server3.redis.local

# Special Values
FEATURE_ENABLED=true
MAINTENANCE_MODE=false
MAX_UPLOAD_SIZE=10485760
PI_VALUE=3.14159

# Multiline with quotes
LONG_TEXT="This is a long text
that spans multiple lines
with preserved formatting"
ENV;

file_put_contents($tempDir.'/.env', $envContent);

// Create .env.local for overrides
$localEnvContent = <<<'ENV'
# Local overrides
APP_ENV=local
APP_DEBUG=false
DB_PASSWORD="local_password"

# Additional local settings
LOCAL_ONLY=true
CACHE_DRIVER=redis
ENV;

file_put_contents($tempDir.'/.env.local', $localEnvContent);

// ===== Basic Usage =====
$output->writeln($output->color('=== Basic Usage ===', Output::CYAN));
$output->writeln('');

$env = new Environment([$tempDir.'/.env']);

$output->writeln('Loading from .env file:');
$output->info('APP_NAME: '.$env->get('APP_NAME'));
$output->info('APP_ENV: '.$env->get('APP_ENV'));
$output->info('APP_DEBUG: '.$env->get('APP_DEBUG'));
$output->writeln('');

// ===== Getting Values with Different Types =====
$output->writeln($output->color('=== Typed Getters ===', Output::CYAN));
$output->writeln('');

// String (default)
$output->writeln('String value:');
$output->info('DB_HOST: '.$env->get('DB_HOST'));

// Integer
$output->writeln('Integer value:');
$output->info('DB_PORT: '.$env->getInt('DB_PORT'));
$output->info('MAX_UPLOAD_SIZE: '.number_format($env->getInt('MAX_UPLOAD_SIZE')).' bytes');

// Float
$output->writeln('Float value:');
$output->info('PI_VALUE: '.$env->getFloat('PI_VALUE'));

// Boolean
$output->writeln('Boolean values:');
$output->info('FEATURE_ENABLED: '.($env->getBool('FEATURE_ENABLED') ? 'true' : 'false'));
$output->info('MAINTENANCE_MODE: '.($env->getBool('MAINTENANCE_MODE') ? 'true' : 'false'));
$output->info('APP_DEBUG: '.($env->getBool('APP_DEBUG') ? 'true' : 'false'));

// Array
$output->writeln('Array values:');
$allowedHosts = $env->getArray('ALLOWED_HOSTS');
$output->info('ALLOWED_HOSTS: '.implode(', ', $allowedHosts));
$redisServers = $env->getArray('REDIS_SERVERS');
$output->info('REDIS_SERVERS: '.implode(', ', $redisServers));
$output->writeln('');

// ===== Variable Expansion =====
$output->writeln($output->color('=== Variable Expansion ===', Output::CYAN));
$output->writeln('');

$output->info('BASE_URL: '.$env->get('BASE_URL'));
$output->info('API_URL: '.$env->get('API_URL').' (expanded from ${BASE_URL}/api)');
$output->info('WEBHOOK_URL: '.$env->get('WEBHOOK_URL').' (expanded from $BASE_URL/webhooks)');
$output->writeln('');

// ===== Environment Detection =====
$output->writeln($output->color('=== Environment Detection ===', Output::CYAN));
$output->writeln('');

$output->writeln('Current environment: '.$env->get('APP_ENV', 'production'));
$output->writeln('');

$output->writeln('Environment checks:');
$output->info('Is Production? '.($env->isProduction() ? 'Yes' : 'No'));
$output->info('Is Development? '.($env->isDevelopment() ? 'Yes' : 'No'));
$output->info('Is Testing? '.($env->isTesting() ? 'Yes' : 'No'));
$output->info('Is Staging? '.($env->isStaging() ? 'Yes' : 'No'));
$output->info('Is Debug Mode? '.($env->isDebug() ? 'Yes' : 'No'));
$output->writeln('');

// ===== Loading Multiple Files with Overrides =====
$output->writeln($output->color('=== Multiple Files with Overrides ===', Output::CYAN));
$output->writeln('');

// Load with .env.local overrides
$envWithLocal = new Environment([
    $tempDir.'/.env',
    $tempDir.'/.env.local',
]);

$output->writeln('Values after loading .env.local:');
$output->info('APP_ENV: '.$envWithLocal->get('APP_ENV').' (overridden from .env.local)');
$output->info('APP_DEBUG: '.$envWithLocal->get('APP_DEBUG').' (overridden from .env.local)');
$output->info('LOCAL_ONLY: '.$envWithLocal->get('LOCAL_ONLY').' (new from .env.local)');
$output->info('APP_NAME: '.$envWithLocal->get('APP_NAME').' (unchanged from .env)');
$output->writeln('');

// ===== Required Variables =====
$output->writeln($output->color('=== Required Variables ===', Output::CYAN));
$output->writeln('');

try {
    $dbConnection = $envWithLocal->getRequired('DB_CONNECTION');
    $output->success('DB_CONNECTION is set: '.$dbConnection);
} catch (RuntimeException $e) {
    $output->error('DB_CONNECTION is not set!');
}

try {
    $missingVar = $envWithLocal->getRequired('MISSING_REQUIRED_VAR');
} catch (RuntimeException $e) {
    $output->error('Error: '.$e->getMessage());
}
$output->writeln('');

// ===== Setting Variables at Runtime =====
$output->writeln($output->color('=== Runtime Variable Management ===', Output::CYAN));
$output->writeln('');

$output->writeln('Setting new variable at runtime:');
$envWithLocal->set('RUNTIME_VAR', 'dynamically set');
$output->info('RUNTIME_VAR: '.$envWithLocal->get('RUNTIME_VAR'));

$output->writeln('Checking if variable exists:');
$output->info('Has RUNTIME_VAR? '.($envWithLocal->has('RUNTIME_VAR') ? 'Yes' : 'No'));
$output->info('Has UNDEFINED_VAR? '.($envWithLocal->has('UNDEFINED_VAR') ? 'Yes' : 'No'));
$output->writeln('');

// ===== Default Values =====
$output->writeln($output->color('=== Default Values ===', Output::CYAN));
$output->writeln('');

$output->info('UNDEFINED_VAR with default: '.$envWithLocal->get('UNDEFINED_VAR', 'default_value'));
$output->info('UNDEFINED_INT with default: '.$envWithLocal->getInt('UNDEFINED_INT', 42));
$output->info('UNDEFINED_BOOL with default: '.($envWithLocal->getBool('UNDEFINED_BOOL', true) ? 'true' : 'false'));
$output->info('UNDEFINED_ARRAY with default: '.implode(', ', $envWithLocal->getArray('UNDEFINED_ARRAY', ['a', 'b', 'c'])));
$output->writeln('');

// ===== Practical Example: Database Configuration =====
$output->writeln($output->color('=== Practical Example: Database Configuration ===', Output::CYAN));
$output->writeln('');

class DatabaseConfig
{
    private Environment $env;

    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    public function getDSN(): string
    {
        $connection = $this->env->getRequired('DB_CONNECTION');
        $host = $this->env->get('DB_HOST', 'localhost');
        $port = $this->env->getInt('DB_PORT', 3306);
        $database = $this->env->getRequired('DB_DATABASE');
        $username = $this->env->get('DB_USERNAME', 'root');
        $password = $this->env->get('DB_PASSWORD', '');

        return sprintf(
            '%s:host=%s;port=%d;dbname=%s',
            $connection,
            $host,
            $port,
            $database
        );
    }

    public function shouldLogQueries(): bool
    {
        return $this->env->isDebug() || $this->env->isDevelopment();
    }

    public function getConnectionTimeout(): int
    {
        return $this->env->getInt('DB_TIMEOUT', 30);
    }
}

$dbConfig = new DatabaseConfig($envWithLocal);
$output->writeln('Database configuration:');
$output->info('DSN: '.$dbConfig->getDSN());
$output->info('Log Queries: '.($dbConfig->shouldLogQueries() ? 'Yes' : 'No'));
$output->info('Connection Timeout: '.$dbConfig->getConnectionTimeout().' seconds');
$output->writeln('');

// ===== Environment-Based Behavior =====
$output->writeln($output->color('=== Environment-Based Behavior ===', Output::CYAN));
$output->writeln('');

function executeWithEnvironmentCheck(Environment $env, Output $output): void
{
    if ($env->isProduction()) {
        $output->warning('⚠️  Running in PRODUCTION mode - be careful!');
        $output->writeln('Safety checks enabled');
        $output->writeln('Logging level: ERROR');
    } elseif ($env->isDevelopment()) {
        $output->info('Running in DEVELOPMENT mode');
        $output->writeln('Debug output enabled');
        $output->writeln('Logging level: DEBUG');
    } elseif ($env->isTesting()) {
        $output->info('Running in TESTING mode');
        $output->writeln('Using test database');
        $output->writeln('Mocking external services');
    } else {
        $output->writeln('Running in '.strtoupper($env->get('APP_ENV', 'unknown')).' mode');
    }
}

// Test different environments
$environments = ['production', 'development', 'testing', 'staging'];

foreach ($environments as $envName) {
    $testEnv = new Environment([]);
    $testEnv->set('APP_ENV', $envName);
    $output->writeln("Behavior for {$envName}:");
    executeWithEnvironmentCheck($testEnv, $output);
    $output->writeln('');
}

// ===== All Variables =====
$output->writeln($output->color('=== All Loaded Variables ===', Output::CYAN));
$output->writeln('');

$allVars = $envWithLocal->all();
$output->writeln('Total variables loaded: '.count($allVars));
$output->writeln('');

// Display first 10 variables
$output->writeln('Sample variables:');
$count = 0;
foreach ($allVars as $key => $value) {
    if ($count >= 10) {
        break;
    }
    if (strpos($key, 'PASSWORD') !== false) {
        $value = '********'; // Hide sensitive data
    }
    $output->info("{$key}: {$value}");
    $count++;
}
$output->writeln('... and '.(count($allVars) - 10).' more');
$output->writeln('');

// ===== Clean up =====
$output->writeln($output->color('=== Cleanup ===', Output::CYAN));
$output->writeln('');

// Clear environment
$envWithLocal->clear();
$output->writeln('Environment cleared');
$output->info('Variables after clear: '.count($envWithLocal->all()));

// Reload
$envWithLocal->reload();
$output->writeln('Environment reloaded');
$output->info('Variables after reload: '.count($envWithLocal->all()));

// Clean up temp files
unlink($tempDir.'/.env');
unlink($tempDir.'/.env.local');
rmdir($tempDir);

$output->writeln('');
$output->success('✅ Environment management example completed!');
$output->writeln('');
$output->comment('💡 Use the Environment class to manage configuration in your CLI applications!');
