#!/usr/bin/env php
<?php

/**
 * Example: Command Signature Parser
 *
 * This example demonstrates how to use Laravel-style command signatures
 * to define command arguments and options in a concise way.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Application;
use Yalla\Commands\Command;
use Yalla\Commands\Traits\HasSignature;
use Yalla\Output\Output;

// Example 1: Simple command with signature
class GreetCommand extends Command
{
    use HasSignature;

    protected string $signature = 'greet {name} {--loud}';

    protected string $description = 'Greet someone by name';

    public function __construct()
    {
        $this->configureUsingSignature();
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $loud = $this->getOption($input, 'loud', false);

        $message = "Hello, {$name}!";

        if ($loud) {
            $message = strtoupper($message);
        }

        $output->success($message);

        return 0;
    }
}

// Example 2: Command with optional arguments and default values
class MakeFileCommand extends Command
{
    use HasSignature;

    protected string $signature = 'make:file {name} {extension?php} {--path=./} {--template=default}';

    protected string $description = 'Create a new file';

    public function __construct()
    {
        $this->configureUsingSignature();
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $extension = $this->getArgument($input, 'extension', 'php');
        $path = $this->getOption($input, 'path', './');
        $template = $this->getOption($input, 'template', 'default');

        $output->info("Creating file: {$path}{$name}.{$extension}");
        $output->info("Using template: {$template}");

        // Simulate file creation
        $filename = rtrim($path, '/')."/{$name}.{$extension}";

        $content = "<?php\n// Generated file using template: {$template}\n";

        if (file_put_contents($filename, $content)) {
            $output->success("File created successfully: {$filename}");

            return 0;
        }

        $output->error('Failed to create file');

        return 1;
    }
}

// Example 3: Complex signature with multiple options
class DatabaseMigrationCommand extends Command
{
    use HasSignature;

    protected string $signature = 'db:migrate
        {name : Migration name}
        {table? : Database table name}
        {--create : Create a new table}
        {--alter : Alter an existing table}
        {--drop : Drop a table}
        {--path=database/migrations : Migration path}
        {--dry-run : Show what would be done}';

    protected string $description = 'Create or run database migrations';

    protected array $argumentMetadata = [];

    protected array $optionMetadata = [];

    public function __construct()
    {
        $this->configureUsingSignature();
    }

    public function execute(array $input, Output $output): int
    {
        $name = $this->getArgument($input, 'name');
        $table = $this->getArgument($input, 'table');
        $create = $this->getOption($input, 'create', false);
        $alter = $this->getOption($input, 'alter', false);
        $drop = $this->getOption($input, 'drop', false);
        $path = $this->getOption($input, 'path', 'database/migrations');
        $dryRun = $this->getOption($input, 'dry-run', false);

        if ($dryRun) {
            $output->warning('DRY RUN MODE - No actual changes will be made');
        }

        $output->info("Migration: {$name}");

        if ($table) {
            $output->info("Table: {$table}");
        }

        $operation = 'update';
        if ($create) {
            $operation = 'create';
        }
        if ($alter) {
            $operation = 'alter';
        }
        if ($drop) {
            $operation = 'drop';
        }

        $output->info("Operation: {$operation}");
        $output->info("Path: {$path}");

        if (! $dryRun) {
            // Simulate migration creation
            $timestamp = date('Y_m_d_His');
            $filename = "{$path}/{$timestamp}_{$name}.php";
            $output->success("Migration would be created: {$filename}");
        }

        return 0;
    }
}

// Example 4: Command with array arguments
class ProcessFilesCommand extends Command
{
    use HasSignature;

    protected string $signature = 'process:files {files*} {--format=json} {--output=} {--verbose}';

    protected string $description = 'Process multiple files';

    protected array $argumentMetadata = [];

    public function __construct()
    {
        $this->configureUsingSignature();
    }

    public function execute(array $input, Output $output): int
    {
        // Array arguments are handled specially
        $files = $input['arguments'] ?? [];
        $format = $this->getOption($input, 'format', 'json');
        $outputFile = $this->getOption($input, 'output');
        $verbose = $this->getOption($input, 'verbose', false);

        if (empty($files)) {
            $output->error('No files specified');

            return 1;
        }

        $output->info('Processing '.count($files)." files in {$format} format");

        foreach ($files as $file) {
            if ($verbose) {
                $output->writeln("  - Processing: {$file}");
            }
        }

        if ($outputFile) {
            $output->success("Results would be saved to: {$outputFile}");
        }

        return 0;
    }
}

// Create and run the application
$app = new Application('Signature Parser Example', '1.0.0');

// Register commands
$app->register(new GreetCommand);
$app->register(new MakeFileCommand);
$app->register(new DatabaseMigrationCommand);
$app->register(new ProcessFilesCommand);

// Run the application
exit($app->run());
