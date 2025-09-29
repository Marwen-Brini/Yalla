#!/usr/bin/env php
<?php

/**
 * Example: Async Command Support
 *
 * This example demonstrates how to create and execute asynchronous commands
 * for long-running operations.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Application;
use Yalla\Commands\AsyncCommandInterface;
use Yalla\Commands\Command;
use Yalla\Commands\Traits\SupportsAsync;
use Yalla\Output\Output;
use Yalla\Process\AsyncExecutor;

// Example 1: Simple async command
class DataProcessorCommand extends Command implements AsyncCommandInterface
{
    use SupportsAsync;

    protected string $name = 'process:data';

    protected string $description = 'Process large dataset asynchronously';

    protected int $asyncTimeout = 60; // 1 minute timeout

    public function __construct()
    {
        $this->configureAsyncOptions();
        $this->addArgument('file', 'Data file to process', true);
        $this->addOption('batch-size', 'b', 'Number of records per batch', 100);
    }

    public function execute(array $input, Output $output): int
    {
        $file = $this->getArgument($input, 'file');
        $batchSize = (int) $this->getOption($input, 'batch-size', 100);

        $output->info("Processing file: {$file}");
        $output->info("Batch size: {$batchSize}");

        // Simulate long-running process
        $totalRecords = 1000;
        $processed = 0;

        $output->writeln('');
        $output->progress()->start($totalRecords);

        while ($processed < $totalRecords) {
            // Simulate processing batch
            usleep(10000); // 10ms per batch
            $processed += $batchSize;

            if ($processed > $totalRecords) {
                $processed = $totalRecords;
            }

            $output->progress()->advance($batchSize);
        }

        $output->progress()->finish();
        $output->success("Processed {$totalRecords} records successfully");

        return 0;
    }
}

// Example 2: Async command with progress reporting
class BackupCommand extends Command implements AsyncCommandInterface
{
    use SupportsAsync;

    protected string $name = 'backup:create';

    protected string $description = 'Create backup of application data';

    protected bool $runAsync = true; // Always run async by default

    protected int $asyncTimeout = 300; // 5 minutes

    public function __construct()
    {
        $this->configureAsyncOptions();
        $this->addOption('compress', 'c', 'Compress backup', false);
        $this->addOption('encrypt', 'e', 'Encrypt backup', false);
    }

    public function execute(array $input, Output $output): int
    {
        $compress = $this->getOption($input, 'compress', false);
        $encrypt = $this->getOption($input, 'encrypt', false);

        $output->info('Starting backup process...');

        $steps = [
            'Collecting database data' => 2,
            'Collecting file system data' => 3,
            'Creating archive' => 2,
        ];

        if ($compress) {
            $steps['Compressing backup'] = 2;
        }

        if ($encrypt) {
            $steps['Encrypting backup'] = 1;
        }

        $totalSteps = array_sum($steps);
        $output->progress()->start($totalSteps);

        foreach ($steps as $step => $duration) {
            $output->writeln("\n{$step}...");

            // Simulate work
            for ($i = 0; $i < $duration; $i++) {
                sleep(1);
                $output->progress()->advance();
            }
        }

        $output->progress()->finish();
        $output->success("\nBackup completed successfully!");

        return 0;
    }
}

// Example 3: Parallel async operations
class ParallelTasksCommand extends Command implements AsyncCommandInterface
{
    use SupportsAsync;

    protected string $name = 'parallel:run';

    protected string $description = 'Run multiple tasks in parallel';

    public function __construct()
    {
        $this->configureAsyncOptions();
    }

    public function execute(array $input, Output $output): int
    {
        $output->info('Starting parallel tasks...');
        $output->writeln('');

        // Define tasks to run in parallel
        $tasks = [
            'api_fetch' => function () {
                // Simulate API call
                sleep(2);

                return ['status' => 'success', 'records' => 150];
            },
            'database_query' => function () {
                // Simulate database query
                sleep(1);

                return ['status' => 'success', 'rows' => 500];
            },
            'file_processing' => function () {
                // Simulate file processing
                sleep(3);

                return ['status' => 'success', 'files' => 25];
            },
            'cache_warmup' => function () {
                // Simulate cache warmup
                usleep(500000); // 0.5 seconds

                return ['status' => 'success', 'entries' => 1000];
            },
        ];

        $output->writeln('Running '.count($tasks).' tasks in parallel:');
        foreach (array_keys($tasks) as $taskName) {
            $output->writeln("  - {$taskName}");
        }
        $output->writeln('');

        // Run all tasks in parallel
        try {
            $results = $this->runParallel($tasks, $output);

            $output->success('All tasks completed successfully!');
            $output->writeln('');

            // Display results
            $output->info('Results:');
            foreach ($results as $taskName => $result) {
                $output->writeln("  {$taskName}:");
                foreach ($result as $key => $value) {
                    $output->writeln("    - {$key}: {$value}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $output->error('Parallel execution failed: '.$e->getMessage());

            return 1;
        }
    }
}

// Example 4: Async command with custom completion handling
class ReportGeneratorCommand extends Command implements AsyncCommandInterface
{
    use SupportsAsync;

    protected string $name = 'report:generate';

    protected string $description = 'Generate reports asynchronously';

    protected int $asyncTimeout = 120;

    public function __construct()
    {
        $this->configureAsyncOptions();
        $this->addArgument('type', 'Report type (daily, weekly, monthly)', true);
        $this->addOption('email', null, 'Email report when complete', null);
    }

    public function execute(array $input, Output $output): int
    {
        $type = $this->getArgument($input, 'type');
        $email = $this->getOption($input, 'email');

        $output->info("Generating {$type} report...");

        // Simulate report generation
        $sections = ['Sales', 'Inventory', 'Customer Analytics', 'Performance Metrics'];

        foreach ($sections as $section) {
            $output->writeln("  Processing {$section}...");
            sleep(1); // Simulate processing
        }

        $reportFile = "report_{$type}_".date('Y-m-d').'.pdf';
        $output->success("Report generated: {$reportFile}");

        if ($email) {
            $output->info("Sending report to: {$email}");
            // Simulate email sending
            sleep(1);
            $output->success('Report sent successfully!');
        }

        return 0;
    }

    public function handleAsyncCompletion($result, Output $output): int
    {
        $output->writeln('');
        $output->info('Report generation completed!');
        $output->info('Check the reports directory for the generated file.');

        return parent::handleAsyncCompletion($result, $output);
    }

    public function handleAsyncError(\Throwable $exception, Output $output): int
    {
        $output->writeln('');
        $output->error('Report generation failed!');

        // Custom error handling
        if (strpos($exception->getMessage(), 'timeout') !== false) {
            $output->warning('The report generation took too long. Try with a smaller date range.');
        }

        return parent::handleAsyncError($exception, $output);
    }
}

// Example 5: Using AsyncExecutor directly
class BatchJobCommand extends Command
{
    protected string $name = 'batch:run';

    protected string $description = 'Run batch jobs using AsyncExecutor';

    public function execute(array $input, Output $output): int
    {
        $output->info('Starting batch job execution...');

        $executor = new AsyncExecutor;
        $executor->setMaxConcurrent(3); // Limit to 3 concurrent commands

        // Create async commands
        $commands = [
            new DataProcessorCommand,
            new BackupCommand,
            new ReportGeneratorCommand,
        ];

        // Prepare inputs for each command
        $commandInputs = [
            ['arguments' => ['data.csv'], 'options' => ['batch-size' => 50]],
            ['options' => ['compress' => true]],
            ['arguments' => ['daily'], 'options' => []],
        ];

        $output->writeln('');
        $output->info('Executing '.count($commands).' commands (max 3 concurrent)...');

        // Execute all commands
        $promises = [];
        foreach ($commands as $index => $command) {
            if ($command instanceof AsyncCommandInterface) {
                $promise = $executor->execute($command, $commandInputs[$index], $output);
                $promises[] = $promise;

                $output->writeln('  - Started: '.$command->getName());
            }
        }

        $output->writeln('');
        $output->info('Waiting for all commands to complete...');

        // Wait for all to complete
        $allResults = $executor->waitAll();

        $output->success('All batch jobs completed!');
        $output->info('Active jobs remaining: '.$executor->getRunningCount());

        return 0;
    }
}

// Create and run the application
$app = new Application('Async Commands Example', '1.0.0');

// Register commands
$app->register(new DataProcessorCommand);
$app->register(new BackupCommand);
$app->register(new ParallelTasksCommand);
$app->register(new ReportGeneratorCommand);
$app->register(new BatchJobCommand);

// Run the application
exit($app->run());
