#!/usr/bin/env php
<?php

/**
 * Enhanced Output Methods Example
 *
 * This example demonstrates the new semantic output methods, verbosity levels,
 * timestamps, and other enhanced output features in Yalla CLI
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Output\Output;

$output = new Output;

// ===== Semantic Output Methods with Icons =====
$output->section('Semantic Output Methods');

$output->success('Operation completed successfully!');
$output->error('An error occurred during processing');
$output->warning('This action cannot be undone');
$output->info('Processing 1000 records...');
$output->debug('Debug: Variable $x = 42');
$output->comment('Tip: Use --force to skip confirmations');
$output->question('What is your name?');
$output->note('Remember to commit your changes');
$output->caution('High memory usage detected');

// ===== Verbosity Levels =====
$output->section('Verbosity Levels');

// Normal verbosity (default)
$output->writeln('This message always appears (normal verbosity)');

// Verbose mode
$output->setVerbosity(Output::VERBOSITY_VERBOSE);
$output->verbose('This only appears in verbose mode (-v)');
$output->writeln('Normal messages still appear');

// Debug mode
$output->setVerbosity(Output::VERBOSITY_DEBUG);
$output->debug('Debug information (appears with -vv)');
$output->verbose('Verbose messages also appear in debug mode');

// SQL query logging (debug mode only)
$query = 'SELECT * FROM users WHERE status = ? AND created_at > ?';
$bindings = ['active', '2024-01-01'];
$output->sql($query, $bindings);

// Trace mode
$output->setVerbosity(Output::VERBOSITY_TRACE);
$output->trace('Detailed trace information (appears with -vvv)');

// Reset to normal
$output->setVerbosity(Output::VERBOSITY_NORMAL);

// ===== Timestamped Output =====
$output->section('Timestamped Output');

$output->writeln('Without timestamps:');
$output->info('Starting migration process');
$output->success('Migration completed');

$output->writeln('');
$output->writeln('With timestamps:');
$output->withTimestamps(true);
$output->info('Starting backup process');
sleep(1);
$output->success('Backup completed');

// Custom timestamp format
$output->setTimestampFormat('H:i:s.v'); // Include milliseconds
$output->info('High-precision timestamp');

// Disable timestamps
$output->withTimestamps(false);

// ===== Grouped Output =====
$output->section('Grouped Output');

$output->group('Database Operations', function (Output $output) {
    $output->info('Connecting to database...');
    $output->success('Connected to MySQL');
    $output->info('Running migrations...');
    $output->success('15 migrations executed');
    $output->info('Seeding database...');
    $output->success('Database seeded with test data');
});

$output->group('Cache Operations', function (Output $output) {
    $output->info('Clearing application cache...');
    $output->success('Application cache cleared');
    $output->info('Warming up cache...');
    $output->success('Cache warmed up');
});

// ===== Output Sections (Updateable Content) =====
$output->section('Updateable Sections');

$section = $output->createSection('Progress Status');

// Simulate progress updates
$tasks = ['Initializing', 'Loading data', 'Processing', 'Saving results', 'Cleaning up'];

foreach ($tasks as $i => $task) {
    $section->overwrite("Current task: {$task}");
    usleep(500000); // 0.5 second delay
}

$section->overwrite('✅ All tasks completed!');

// ===== Conditional Output Based on Verbosity =====
$output->section('Conditional Output Example');

function performOperation(Output $output): void
{
    $output->info('Starting operation...');

    $output->verbose('Loading configuration files');
    $output->verbose('  - config/app.php');
    $output->verbose('  - config/database.php');

    $output->debug('Configuration values:');
    $output->debug('  APP_ENV=production');
    $output->debug('  DB_CONNECTION=mysql');

    $output->trace('Stack trace:');
    $output->trace('  at performOperation() in example.php:145');
    $output->trace('  at main() in example.php:200');

    $output->success('Operation completed!');
}

// Run with different verbosity levels
$verbosityLevels = [
    Output::VERBOSITY_QUIET => 'QUIET',
    Output::VERBOSITY_NORMAL => 'NORMAL',
    Output::VERBOSITY_VERBOSE => 'VERBOSE (-v)',
    Output::VERBOSITY_DEBUG => 'DEBUG (-vv)',
    Output::VERBOSITY_TRACE => 'TRACE (-vvv)',
];

foreach ($verbosityLevels as $level => $name) {
    $output->writeln($output->color("Running with {$name} verbosity:", Output::YELLOW));
    $output->setVerbosity($level);
    performOperation($output);
    $output->writeln('');
}

// Reset to normal
$output->setVerbosity(Output::VERBOSITY_NORMAL);

// ===== Method Chaining =====
$output->section('Method Chaining');

$output
    ->setVerbosity(Output::VERBOSITY_DEBUG)
    ->withTimestamps(true)
    ->setTimestampFormat('Y-m-d H:i:s');

$output->info('This message has timestamp and appears in debug mode');
$output->debug('This debug message also has a timestamp');

// Reset
$output->setVerbosity(Output::VERBOSITY_NORMAL)->withTimestamps(false);

// ===== Practical Example: Migration Command Output =====
$output->section('Practical Example: Migration Output');

// Simulate a migration command with enhanced output
class MigrationSimulator
{
    private Output $output;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    public function run(bool $verbose = false): void
    {
        if ($verbose) {
            $this->output->setVerbosity(Output::VERBOSITY_VERBOSE);
        }

        $this->output->info('Starting database migration...');

        $migrations = [
            '2024_01_01_create_users_table',
            '2024_01_02_add_email_verified_column',
            '2024_01_03_create_posts_table',
            '2024_01_04_add_foreign_keys',
        ];

        foreach ($migrations as $i => $migration) {
            $this->output->verbose("Processing migration: {$migration}");

            // Simulate SQL execution (debug mode only)
            if ($i === 0) {
                $this->output->sql(
                    'CREATE TABLE users (id INT PRIMARY KEY, name VARCHAR(255))',
                    []
                );
            }

            usleep(200000); // Simulate work
            $this->output->success("✓ {$migration}");
        }

        $this->output->writeln('');
        $this->output->success('All migrations completed successfully!');
        $this->output->note('Database schema is now up to date');
    }
}

$simulator = new MigrationSimulator($output);

$output->writeln('Running migrations in normal mode:');
$simulator->run(false);

$output->writeln('');
$output->writeln('Running migrations in verbose mode:');
$simulator->run(true);

// ===== Summary =====
$output->section('Summary');

$output->success('Enhanced output methods provide:');
$output->writeln('  • Semantic methods with icons for better visual feedback');
$output->writeln('  • Verbosity levels for controlling output detail');
$output->writeln('  • Timestamp support for logging and debugging');
$output->writeln('  • SQL query interpolation for database debugging');
$output->writeln('  • Grouped and sectioned output for organization');
$output->writeln('  • Updateable sections for dynamic content');
$output->writeln('  • Method chaining for fluent configuration');

$output->writeln('');
$output->comment('Use these features to create more informative and user-friendly CLI applications!');
