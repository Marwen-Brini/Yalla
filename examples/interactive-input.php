#!/usr/bin/env php
<?php

/**
 * Interactive Input Examples for Yalla CLI v1.5
 *
 * This file demonstrates the interactive input capabilities
 * including confirmations, choices, and text input with validation.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Output\Output;

$output = new Output;

$output->section('Yalla CLI v1.5 - Interactive Input Examples');

// Example 1: Simple Yes/No Confirmation
$output->section('1. Simple Confirmation');
$output->info('Asking for a simple yes/no confirmation...');

if ($output->confirm('Do you want to continue?')) {
    $output->success('You chose to continue!');
} else {
    $output->warning('You chose not to continue.');
}

// Example 2: Confirmation with Default
$output->section('2. Confirmation with Default Value');
$output->info('Press Enter to use the default value...');

if ($output->confirm('Enable debug mode?', true)) {
    $output->success('Debug mode enabled (default was Yes)');
} else {
    $output->warning('Debug mode disabled');
}

// Example 3: Single Choice Selection
$output->section('3. Single Choice Selection');
$output->info('Select one option from the list...');

$environment = $output->choice(
    'Which environment are you deploying to?',
    ['development', 'staging', 'production'],
    0  // Default to 'development'
);

$output->success("You selected: $environment");

// Example 4: Multiple Choice Selection
$output->section('4. Multiple Choice Selection');
$output->info('Select multiple options (comma-separated numbers or "all")...');

$features = $output->multiChoice(
    'Which features do you want to enable?',
    ['Caching', 'Logging', 'Monitoring', 'Debugging', 'Profiling'],
    [0, 1]  // Default to Caching and Logging
);

$output->success('You selected: '.implode(', ', $features));

// Example 5: Text Input
$output->section('5. Text Input');
$output->info('Enter some text...');

$projectName = $output->ask('What is your project name?', 'my-project');
$output->success("Project name: $projectName");

// Example 6: Validated Input
$output->section('6. Input with Validation');
$output->info('Enter a value that must pass validation...');

try {
    $port = $output->askValid(
        'Enter port number (1024-65535)',
        function ($value) {
            if (! is_numeric($value)) {
                return false;
            }
            $port = (int) $value;

            return $port >= 1024 && $port <= 65535;
        },
        'Port must be a number between 1024 and 65535',
        '8080',
        3
    );
    $output->success("Port number: $port");
} catch (\RuntimeException $e) {
    $output->error('Failed to get valid port after 3 attempts');
}

// Example 7: Hidden Input (Password)
$output->section('7. Hidden Input for Passwords');
$output->info('Enter a password (input will be hidden)...');

$password = $output->askHidden('Enter database password');
if ($password) {
    $output->success('Password received (length: '.strlen($password).' characters)');
} else {
    $output->warning('No password entered');
}

// Example 8: Complex Validation Example
$output->section('8. Complex Validation - Email Address');

try {
    $email = $output->askValid(
        'Enter your email address',
        function ($value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        },
        'Please enter a valid email address',
        null,
        3
    );
    $output->success("Email address: $email");
} catch (\RuntimeException $e) {
    $output->error('Failed to get valid email');
}

// Example 9: Migration-style Confirmation
$output->section('9. Migration-style Dangerous Operation');

$output->warning('⚠️  WARNING: This operation will delete all data!');
$output->writeln('This action cannot be undone.');
$output->writeln('');

if ($output->confirm('Are you absolutely sure you want to proceed?', false)) {
    $output->info('Proceeding with operation...');

    // Double confirmation for extra safety
    if ($output->confirm('This is your last chance. Really delete everything?', false)) {
        $output->error('All data would be deleted (simulated)');
    } else {
        $output->info('Operation cancelled at second confirmation');
    }
} else {
    $output->success('Operation cancelled - data is safe');
}

// Example 10: Non-interactive Mode
$output->section('10. Non-interactive Mode');
$output->info('Switching to non-interactive mode...');

$output->setInteractive(false);
$output->writeln('In non-interactive mode, defaults are used automatically');

$result = $output->confirm('This will use default (false)', false);
$output->writeln('Result: '.($result ? 'true' : 'false'));

$choice = $output->choice('This will use default', ['A', 'B', 'C'], 1);
$output->writeln('Choice: '.$choice);

$text = $output->ask('This will use default', 'default-value');
$output->writeln('Text: '.$text);

// Re-enable interactive mode
$output->setInteractive(true);

// Example 11: Choice with Custom Display
$output->section('11. Database Migration Selection');

$migrations = [
    '2024_01_01_create_users_table',
    '2024_01_02_add_email_verified',
    '2024_01_03_create_posts_table',
    '2024_01_04_add_user_avatar',
    '2024_01_05_create_comments_table',
];

$selectedMigration = $output->choice(
    'Select a migration to rollback to',
    $migrations,
    0
);

$output->success("Rolling back to: $selectedMigration");

// Example 12: Feature Toggle Selection
$output->section('12. Feature Toggle Configuration');

$availableFeatures = [
    'api_versioning' => 'API Versioning',
    'rate_limiting' => 'Rate Limiting',
    'cache_warming' => 'Cache Warming',
    'query_optimization' => 'Query Optimization',
    'debug_toolbar' => 'Debug Toolbar',
    'profiling' => 'Performance Profiling',
];

$selected = $output->multiChoice(
    'Select features to enable in your application',
    array_values($availableFeatures),
    [0, 1]  // Default to first two
);

$output->success('Enabled features:');
foreach ($selected as $feature) {
    $output->writeln('  ✓ '.$feature);
}

// Example 13: Chaining Interactive Methods
$output->section('13. Complete Configuration Wizard');

$output->info('Starting configuration wizard...');

// Step 1: Environment
$env = $output->choice(
    'Select environment',
    ['development', 'staging', 'production'],
    0
);

// Step 2: Features based on environment
if ($env === 'production') {
    $output->warning('Production environment selected - some options will be restricted');

    if (! $output->confirm('Enable debug mode in production?', false)) {
        $output->info('Debug mode disabled (recommended for production)');
    }
} else {
    if ($output->confirm('Enable debug mode?', true)) {
        $output->info('Debug mode enabled');
    }
}

// Step 3: Database configuration
$dbName = $output->ask('Database name', 'app_db');
$dbHost = $output->ask('Database host', 'localhost');
$dbPort = $output->askValid(
    'Database port',
    fn ($v) => is_numeric($v) && $v > 0 && $v <= 65535,
    'Invalid port number',
    '3306'
);

$output->success('Configuration complete!');
$output->table(
    ['Setting', 'Value'],
    [
        ['Environment', $env],
        ['Database Name', $dbName],
        ['Database Host', $dbHost],
        ['Database Port', $dbPort],
    ]
);

$output->section('Examples Complete!');
$output->success('✅ All interactive input examples have been demonstrated.');
$output->info('📚 The interactive input system is ready for use in migration commands!');
