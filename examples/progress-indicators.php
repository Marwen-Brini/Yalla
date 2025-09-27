#!/usr/bin/env php
<?php

/**
 * Progress Indicators Examples for Yalla CLI v1.6
 *
 * This file demonstrates the progress indicators capabilities
 * including progress bars, spinners, and step indicators.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Output\Output;

$output = new Output;

$output->section('Yalla CLI v1.6 - Progress Indicators Examples');
$output->writeln('');

// =============================================================================
// PROGRESS BAR EXAMPLES
// =============================================================================

$output->section('Progress Bar Examples');

// Example 1: Basic Progress Bar
$output->info('Example 1: Basic Progress Bar');
$items = range(1, 50);
$progress = $output->createProgressBar(count($items));
$progress->start();

foreach ($items as $item) {
    usleep(50000); // Simulate work (50ms)
    $progress->advance();
}

$progress->finish();
$output->writeln('');

// Example 2: Progress Bar with Custom Width
$output->info('Example 2: Progress Bar with Custom Width');
$progress = $output->createProgressBar(30);
$progress->setBarWidth(50)->start();

for ($i = 0; $i < 30; $i++) {
    usleep(30000);
    $progress->advance();
}

$progress->finish();
$output->writeln('');

// Example 3: Verbose Progress Bar with Messages
$output->info('Example 3: Verbose Progress Bar with Messages');
$migrations = [
    '2024_01_01_create_users_table',
    '2024_01_02_add_email_verified',
    '2024_01_03_create_posts_table',
    '2024_01_04_add_user_avatar',
    '2024_01_05_create_comments_table',
];

$progress = $output->createProgressBar(count($migrations));
$progress->setFormat('verbose');
$progress->setBarWidth(40);
$progress->start();

foreach ($migrations as $migration) {
    $progress->setMessage("Migrating: $migration");
    usleep(500000); // Simulate migration (500ms)
    $progress->setMessage("✅ Migrated: $migration");
    $progress->advance();
}

$progress->finish();
$output->success('All migrations completed!');
$output->writeln('');

// Example 4: Detailed Progress with Time Estimates
$output->info('Example 4: Detailed Progress with Time Estimates');
$files = range(1, 100);
$progress = $output->createProgressBar(count($files));
$progress->setFormat('detailed');
$progress->setRedrawFrequency(10); // Only redraw every 10 items for performance
$progress->start();

foreach ($files as $file) {
    usleep(20000); // Process file (20ms)
    $progress->advance();
}

$progress->finish();
$output->writeln('');

// Example 5: Custom Format Progress Bar
$output->info('Example 5: Custom Format Progress Bar');
$progress = $output->createProgressBar(75);
$progress->setCustomFormat('Processing: {current}/{total} [{bar}] {percent}% - Memory: {memory}');
$progress->start();

for ($i = 0; $i < 75; $i++) {
    $data = str_repeat('x', 10000); // Allocate some memory
    usleep(10000);
    $progress->advance();
}

$progress->finish();
$output->writeln('');

// =============================================================================
// SPINNER EXAMPLES
// =============================================================================

$output->section('Spinner Examples');

// Example 6: Basic Spinner
$output->info('Example 6: Basic Spinner with Different Frame Sets');

$frameSets = ['dots', 'line', 'pipe', 'arrow', 'bounce', 'box'];

foreach ($frameSets as $frameSet) {
    $spinner = $output->createSpinner("Testing $frameSet spinner...", $frameSet);
    $spinner->start();

    for ($i = 0; $i < 30; $i++) {
        usleep(50000);
        $spinner->advance();
    }

    $spinner->success("$frameSet spinner complete!");
}

$output->writeln('');

// Example 7: Spinner with Dynamic Messages
$output->info('Example 7: Spinner with Dynamic Messages');
$spinner = $output->createSpinner('Analyzing database schema...');
$spinner->start();

$steps = [
    'Connecting to database...',
    'Reading table structures...',
    'Analyzing foreign keys...',
    'Checking indexes...',
    'Generating report...',
];

foreach ($steps as $step) {
    $spinner->setMessage($step);
    for ($i = 0; $i < 20; $i++) {
        usleep(50000);
        $spinner->advance();
    }
}

$spinner->success('Database analysis complete!');
$output->writeln('');

// Example 8: Spinner with Different Outcomes
$output->info('Example 8: Spinner with Different Outcomes');

$tasks = [
    ['Downloading package...', 'success', 'Package downloaded successfully'],
    ['Installing dependencies...', 'success', 'Dependencies installed'],
    ['Running tests...', 'warning', 'Tests passed with warnings'],
    ['Building assets...', 'error', 'Build failed: Missing configuration'],
];

foreach ($tasks as $task) {
    $spinner = $output->createSpinner($task[0]);
    $spinner->start();

    for ($i = 0; $i < 15; $i++) {
        usleep(50000);
        $spinner->advance();
    }

    switch ($task[1]) {
        case 'success':
            $spinner->success($task[2]);

            break;
        case 'warning':
            $spinner->warning($task[2]);

            break;
        case 'error':
            $spinner->error($task[2]);

            break;
        default:
            $spinner->info($task[2]);
    }
}

$output->writeln('');

// =============================================================================
// STEP INDICATOR EXAMPLES
// =============================================================================

$output->section('Step Indicator Examples');

// Example 9: Basic Step Indicator
$output->info('Example 9: Basic Step Indicator');
$steps = $output->steps([
    'Initialize project',
    'Install dependencies',
    'Run database migrations',
    'Compile assets',
    'Run tests',
    'Deploy application',
]);

$steps->start();

usleep(500000);
$steps->complete(0, 'Project initialized');

usleep(800000);
$steps->complete(1, 'All dependencies installed (142 packages)');

usleep(600000);
$steps->complete(2, '15 migrations executed');

usleep(400000);
$steps->complete(3, 'Assets compiled (2.3MB)');

usleep(1000000);
$steps->skip(4, 'Tests skipped in production mode');

usleep(500000);
$steps->complete(5, 'Application deployed to production');

$steps->finish();
$output->writeln('');

// Example 10: Step Indicator with Failures
$output->info('Example 10: Step Indicator with Mixed Results');
$deploySteps = $output->steps([
    'Backing up database',
    'Pulling latest code',
    'Installing dependencies',
    'Running migrations',
    'Clearing cache',
    'Restarting services',
]);

$deploySteps->start();

usleep(600000);
$deploySteps->complete(0, 'Database backed up (1.2GB)');

usleep(400000);
$deploySteps->complete(1, 'Latest code pulled from main branch');

usleep(800000);
$deploySteps->complete(2, '45 dependencies updated');

usleep(500000);
$deploySteps->fail(3, 'Migration failed: Foreign key constraint');

usleep(300000);
$deploySteps->skip(4, 'Skipped due to migration failure');

usleep(200000);
$deploySteps->skip(5, 'Skipped due to migration failure');

$deploySteps->finish();
$output->writeln('');

// Example 11: Dynamic Step Updates
$output->info('Example 11: Dynamic Step Updates');
$buildSteps = $output->steps([
    'Linting code',
    'Running unit tests',
    'Running integration tests',
    'Building Docker image',
    'Pushing to registry',
]);

$buildSteps->start();

// Simulate some steps running in parallel or out of order
usleep(300000);
$buildSteps->running(2, 'Running integration tests...');

usleep(500000);
$buildSteps->complete(0, 'Code linting passed');

usleep(700000);
$buildSteps->complete(1, '142 unit tests passed');

usleep(900000);
$buildSteps->complete(2, '28 integration tests passed');

usleep(400000);
$buildSteps->running(3, 'Building Docker image...');

usleep(1200000);
$buildSteps->complete(3, 'Docker image built (125MB)');

usleep(600000);
$buildSteps->complete(4, 'Image pushed to registry');

$buildSteps->finish();
$output->writeln('');

// =============================================================================
// COMBINED EXAMPLE: MIGRATION WITH ALL PROGRESS INDICATORS
// =============================================================================

$output->section('Combined Example: Database Migration Process');

// Step 1: Analyze database
$spinner = $output->createSpinner('Analyzing database structure...');
$spinner->start();

for ($i = 0; $i < 30; $i++) {
    usleep(50000);
    $spinner->advance();
}

$spinner->success('Database analysis complete');

// Step 2: Show migration steps
$migrationSteps = $output->steps([
    'Create backup',
    'Run migrations',
    'Rebuild indexes',
    'Update cache',
    'Verify integrity',
]);

$migrationSteps->start();

// Step 2.1: Create backup with progress bar
usleep(200000);
$output->writeln('');
$output->info('Creating database backup...');
$backupProgress = $output->createProgressBar(100);
$backupProgress->setFormat('verbose');
$backupProgress->setMessage('Backing up tables...');
$backupProgress->start();

for ($i = 0; $i < 100; $i++) {
    if ($i === 25) {
        $backupProgress->setMessage('Backing up users table...');
    }
    if ($i === 50) {
        $backupProgress->setMessage('Backing up posts table...');
    }
    if ($i === 75) {
        $backupProgress->setMessage('Backing up comments table...');
    }
    usleep(10000);
    $backupProgress->advance();
}

$backupProgress->setMessage('Backup complete');
$backupProgress->finish();
$migrationSteps->complete(0, 'Backup created (2.3GB)');

// Step 2.2: Run migrations
usleep(200000);
$output->writeln('');
$output->info('Running migrations...');
$migrations = [
    '2024_01_01_create_users_table',
    '2024_01_02_add_email_verified',
    '2024_01_03_create_posts_table',
];

$migrationProgress = $output->createProgressBar(count($migrations));
$migrationProgress->setFormat('verbose');
$migrationProgress->start();

foreach ($migrations as $migration) {
    $migrationProgress->setMessage("Running: $migration");
    usleep(500000);
    $migrationProgress->advance();
}

$migrationProgress->finish();
$migrationSteps->complete(1, sprintf('%d migrations executed', count($migrations)));

// Complete remaining steps
usleep(400000);
$migrationSteps->complete(2, 'Indexes rebuilt');

usleep(300000);
$migrationSteps->complete(3, 'Cache updated');

usleep(500000);
$migrationSteps->complete(4, 'Database integrity verified');

$migrationSteps->finish();

$output->writeln('');
$output->section('Examples Complete!');
$output->success('✅ All progress indicator examples have been demonstrated.');
$output->info('📚 Progress indicators are ready for use in your CLI applications!');
