#!/usr/bin/env php
<?php

/**
 * File Generation System Example
 *
 * This example demonstrates the StubGenerator and FileHelper classes
 * for template-based file generation in Yalla CLI
 */

require_once __DIR__.'/../vendor/autoload.php';

use Yalla\Filesystem\FileHelper;
use Yalla\Filesystem\StubGenerator;
use Yalla\Output\Output;

$output = new Output;
$output->section('File Generation System Example');

// Create temporary directory for examples
$tempDir = sys_get_temp_dir().'/yalla_file_gen_example_'.uniqid();
mkdir($tempDir);
$output->info("Working directory: {$tempDir}");
$output->writeln('');

// ===== StubGenerator Example =====
$output->writeln($output->color('=== Stub Generator ===', Output::CYAN));
$output->writeln('');

$generator = new StubGenerator(__DIR__.'/../stubs');
$fileHelper = new FileHelper;

// Example 1: Generate a migration file
$output->writeln('1. Generating migration file from stub:');

$migrationData = [
    'namespace' => 'App\\Database\\Migrations',
    'className' => 'CreateUsersTable',
    'description' => 'Create users table',
    'date' => date('Y-m-d H:i:s'),
    'tableName' => 'users',
    'columns' => [
        ['type' => 'string', 'name' => 'name', 'nullable' => false],
        ['type' => 'string', 'name' => 'email', 'nullable' => false],
        ['type' => 'string', 'name' => 'password', 'nullable' => false],
        ['type' => 'boolean', 'name' => 'active', 'default' => 'true'],
        ['type' => 'string', 'name' => 'bio', 'nullable' => true],
    ],
    'timestamps' => true,
    'softDeletes' => true,
    'indexes' => [
        ['type' => 'unique', 'column' => 'email'],
        ['type' => 'index', 'column' => 'active'],
    ],
];

$migrationPath = $tempDir.'/migrations/'.date('Y_m_d_His').'_create_users_table.php';
$generator->generate('migration', $migrationPath, $migrationData);

$output->success('✅ Migration created: '.basename($migrationPath));
$output->writeln('');

// Example 2: Generate a command file
$output->writeln('2. Generating command file from stub:');

$commandData = [
    'namespace' => 'App\\Commands',
    'className' => 'MigrateCommand',
    'commandName' => 'migrate',
    'description' => 'Run database migrations',
    'useExitCodes' => true,
    'useDryRun' => true,
    'arguments' => [
        ['name' => 'target', 'description' => 'Target migration', 'required' => 'false'],
    ],
    'options' => [
        ['name' => 'force', 'shortcut' => "'f'", 'description' => 'Force migration in production', 'default' => 'false'],
        ['name' => 'dry-run', 'shortcut' => "'d'", 'description' => 'Run in dry-run mode', 'default' => 'false'],
        ['name' => 'seed', 'shortcut' => "'s'", 'description' => 'Run seeders after migration', 'default' => 'false'],
    ],
];

$commandPath = $tempDir.'/Commands/MigrateCommand.php';
$generator->generate('command', $commandPath, $commandData);

$output->success('✅ Command created: '.basename($commandPath));
$output->writeln('');

// Example 3: Generate from inline template
$output->writeln('3. Generating from inline template:');

$template = <<<'TEMPLATE'
# {{ title }}

**Author**: {{ author }}
**Date**: {{ date }}

## Description
{{ description }}

## Features
{{#each features}}
- {{ this }}
{{/each}}

{{#if notes}}
## Notes
{{ notes }}
{{/if}}

---
Generated with Yalla CLI
TEMPLATE;

$readmeContent = $generator->renderString($template, [
    'title' => 'Project README',
    'author' => 'Yalla CLI',
    'date' => date('Y-m-d'),
    'description' => 'This project demonstrates the file generation capabilities of Yalla CLI.',
    'features' => [
        'Template-based file generation',
        'Variable replacement',
        'Conditional blocks',
        'Loop support',
        'Multiple file formats',
    ],
    'notes' => 'This file was generated from an inline template.',
]);

$readmePath = $tempDir.'/README.md';
file_put_contents($readmePath, $readmeContent);

$output->success('✅ README created: '.basename($readmePath));
$output->writeln('');

// ===== FileHelper Example =====
$output->writeln($output->color('=== File Helper Utilities ===', Output::CYAN));
$output->writeln('');

// Example 4: Generate unique filenames
$output->writeln('4. Generating unique filenames:');

$patterns = [
    'report_{timestamp}.txt',
    'backup_{date}_{counter}.sql',
    'log_{unique}.txt',
    'user_{id}_{timestamp}.json',
];

foreach ($patterns as $pattern) {
    $replacements = ['id' => '12345'];
    $uniquePath = $fileHelper->uniqueFilename($tempDir, $pattern, $replacements);
    $output->info("  Pattern: {$pattern}");
    $output->writeln('  Result:  '.basename($uniquePath));

    // Create the file so next iteration generates a different name
    touch($uniquePath);
}
$output->writeln('');

// Example 5: Safe file writing with backup
$output->writeln('5. Safe file writing with backup:');

$configPath = $tempDir.'/config.json';
$originalContent = json_encode(['version' => '1.0', 'debug' => false], JSON_PRETTY_PRINT);
file_put_contents($configPath, $originalContent);

$output->info('Original file created: config.json');

// Update with backup
$newContent = json_encode(['version' => '2.0', 'debug' => true, 'features' => ['logging', 'caching']], JSON_PRETTY_PRINT);
$fileHelper->safeWrite($configPath, $newContent, true);

$output->success('File updated with backup');

// List backup files
$backups = glob($tempDir.'/.config.json.backup.*');
if ($backups) {
    $output->info('Backup created: '.basename($backups[0]));
}
$output->writeln('');

// Example 6: Find files with pattern
$output->writeln('6. Finding files with patterns:');

// Create some test files
$testFiles = [
    'test_file1.php',
    'test_file2.php',
    'other_file.txt',
    'test_data.json',
    'subdir/test_file3.php',
    'subdir/nested/test_file4.php',
];

foreach ($testFiles as $file) {
    $fullPath = $tempDir.'/'.$file;
    $fileHelper->ensureDirectoryExists(dirname($fullPath));
    touch($fullPath);
}

$patterns = ['*.php', 'test_*', '*.json'];

foreach ($patterns as $pattern) {
    $found = $fileHelper->findFiles($tempDir, $pattern, true);
    $output->info("Pattern '{$pattern}': Found ".count($found).' files');
    foreach ($found as $file) {
        $output->writeln('  - '.str_replace($tempDir.'/', '', $file));
    }
}
$output->writeln('');

// Example 7: Relative paths
$output->writeln('7. Computing relative paths:');

$pathPairs = [
    ['/var/www/project', '/var/www/project/src/Model.php'],
    ['/home/user/app/src', '/home/user/app/tests'],
    ['/projects/app1', '/projects/app2'],
];

foreach ($pathPairs as [$from, $to]) {
    $relative = $fileHelper->relativePath($from, $to);
    $output->info("From: {$from}");
    $output->writeln("To:   {$to}");
    $output->writeln("Relative: {$relative}");
    $output->writeln('');
}

// Example 8: Human-readable file sizes
$output->writeln('8. Human-readable file sizes:');

// Create files of different sizes
$sizeTestFiles = [
    ['size' => 512, 'name' => 'small.txt'],
    ['size' => 1024 * 50, 'name' => 'medium.txt'],
    ['size' => 1024 * 1024 * 2.5, 'name' => 'large.txt'],
];

foreach ($sizeTestFiles as $fileInfo) {
    $path = $tempDir.'/'.$fileInfo['name'];
    file_put_contents($path, str_repeat('X', $fileInfo['size']));
    $humanSize = $fileHelper->humanFilesize($path);
    $output->info("{$fileInfo['name']}: {$humanSize}");
}
$output->writeln('');

// Example 9: Complex template with conditionals and loops
$output->writeln('9. Complex template generation:');

// Register custom stub inline
$complexTemplate = <<<'STUB'
<?php

class {{ className }}
{
    {{#if properties}}
    // Properties
    {{#each properties}}
    private {{ type }} ${{ name }};
    {{/each}}
    {{/if}}

    {{#if constructor}}
    public function __construct(
        {{#each properties}}
        {{ type }} ${{ name }}{{#unless @last}},{{/unless}}
        {{/each}}
    ) {
        {{#each properties}}
        $this->{{ name }} = ${{ name }};
        {{/each}}
    }
    {{/if}}

    {{#each properties}}
    public function get{{ Name }}(): {{ type }}
    {
        return $this->{{ name }};
    }

    public function set{{ Name }}({{ type }} $value): void
    {
        $this->{{ name }} = $value;
    }

    {{/each}}
}
STUB;

// Save template to temp file
$tempStubPath = $tempDir.'/class.stub';
file_put_contents($tempStubPath, $complexTemplate);

$generator->registerStub('class', $tempStubPath);

$classData = [
    'className' => 'User',
    'properties' => [
        ['type' => 'int', 'name' => 'id', 'Name' => 'Id'],
        ['type' => 'string', 'name' => 'name', 'Name' => 'Name'],
        ['type' => 'string', 'name' => 'email', 'Name' => 'Email'],
        ['type' => 'bool', 'name' => 'active', 'Name' => 'Active'],
    ],
    'constructor' => true,
];

$classPath = $tempDir.'/User.php';
$generator->generate('class', $classPath, $classData);

$output->success('✅ Class generated with getters/setters');
$output->writeln('');

// Show generated class content
$output->writeln('Generated class preview:');
$output->writeln($output->color('---', Output::GRAY));
$lines = array_slice(file($classPath), 0, 20);
foreach ($lines as $line) {
    $output->write($output->color(rtrim($line), Output::GRAY)."\n");
}
$output->writeln($output->color('... (truncated)', Output::GRAY));
$output->writeln('');

// Example 10: Directory operations
$output->writeln('10. Directory operations:');

// Copy directory
$sourceDir = $tempDir.'/source';
$destDir = $tempDir.'/destination';

$fileHelper->ensureDirectoryExists($sourceDir.'/subdir');
file_put_contents($sourceDir.'/file1.txt', 'Content 1');
file_put_contents($sourceDir.'/subdir/file2.txt', 'Content 2');

$fileHelper->copyDirectory($sourceDir, $destDir);
$output->success("Directory copied from 'source' to 'destination'");

// List copied files
$copiedFiles = $fileHelper->findFiles($destDir, '*', true);
$output->info('Copied files:');
foreach ($copiedFiles as $file) {
    $output->writeln('  - '.str_replace($tempDir.'/', '', $file));
}
$output->writeln('');

// ===== Summary =====
$output->section('Summary');

// Count generated files
$allFiles = $fileHelper->findFiles($tempDir, '*', true);
$output->success('✅ Generated '.count($allFiles).' files in this example');

$output->writeln('');
$output->writeln('File Generation System features demonstrated:');
$output->writeln('  • Template-based file generation from stubs');
$output->writeln('  • Variable replacement with multiple formats');
$output->writeln('  • Conditional blocks ({{#if}}) support');
$output->writeln('  • Loop blocks ({{#each}}) support');
$output->writeln('  • Unique filename generation');
$output->writeln('  • Safe file writing with atomic operations');
$output->writeln('  • File pattern searching');
$output->writeln('  • Relative path calculation');
$output->writeln('  • Human-readable file sizes');
$output->writeln('  • Directory operations (copy, delete)');

// Cleanup
$output->writeln('');
$output->writeln('Cleaning up temporary files...');
$fileHelper->deleteDirectory($tempDir);
$output->success('✅ Cleanup complete!');

$output->writeln('');
$output->comment('💡 Use StubGenerator and FileHelper to automate file generation in your CLI tools!');
