<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

class InitCommand extends Command
{
    public function __construct()
    {
        $this->name = 'init';
        $this->description = 'Initialize Yalla CLI in your project';
        
        $this->addOption('force', 'f', 'Overwrite existing files', false);
        $this->addOption('name', null, 'Application name', 'My CLI App');
        $this->addOption('bin', null, 'Binary name', 'cli');
    }
    
    public function execute(array $input, Output $output): int
    {
        $force = (bool) $this->getOption($input, 'force', false);
        $appName = $this->getOption($input, 'name', 'My CLI App');
        $binName = $this->getOption($input, 'bin', 'cli');
        
        $output->section('Initializing Yalla CLI');
        
        // Create CLI entry point
        $this->createCliEntryPoint($binName, $appName, $force, $output);
        
        // Create configuration file
        $this->createConfigFile($force, $output);
        
        // Create Commands directory
        $this->createCommandsDirectory($output);
        
        // Create example command
        $this->createExampleCommand($force, $output);
        
        $output->writeln('');
        $output->success('Yalla CLI initialized successfully!');
        $output->writeln('');
        $output->info('Next steps:');
        $output->writeln('  1. Make your CLI executable: chmod +x ' . $binName);
        $output->writeln('  2. Run your CLI: ./' . $binName);
        $output->writeln('  3. Create new commands: ./' . $binName . ' make:command MyCommand');
        $output->writeln('  4. Register commands in yalla.config.php');
        
        return 0;
    }
    
    private function createCliEntryPoint(string $binName, string $appName, bool $force, Output $output): void
    {
        if (file_exists($binName) && !$force) {
            $output->warning("File '$binName' already exists. Use --force to overwrite.");
            return;
        }
        
        $content = <<<PHP
#!/usr/bin/env php
<?php

/**
 * {$appName} CLI Application
 * 
 * This is the entry point for your CLI application powered by Yalla.
 */

require __DIR__ . '/vendor/autoload.php';

use Yalla\\Application;

// Load configuration
\$config = require __DIR__ . '/yalla.config.php';

// Create application
\$app = new Application(\$config['name'] ?? '{$appName}', \$config['version'] ?? '1.0.0');

// Register custom commands
foreach (\$config['commands'] ?? [] as \$commandClass) {
    if (class_exists(\$commandClass)) {
        \$app->register(new \$commandClass());
    }
}

// Run the application
exit(\$app->run(\$argv ?? []));
PHP;
        
        file_put_contents($binName, $content);
        chmod($binName, 0755);
        $output->success("Created CLI entry point: $binName");
    }
    
    private function createConfigFile(bool $force, Output $output): void
    {
        $configFile = 'yalla.config.php';
        
        if (file_exists($configFile) && !$force) {
            $output->warning("File '$configFile' already exists. Use --force to overwrite.");
            return;
        }
        
        $namespace = $this->detectNamespace();
        
        $content = <<<PHP
<?php

/**
 * Yalla CLI Configuration
 * 
 * Register your custom commands and configure your CLI application.
 */

return [
    // Application name and version
    'name' => 'My CLI App',
    'version' => '1.0.0',
    
    // Register your custom commands here
    'commands' => [
        // {$namespace}\\Commands\\ExampleCommand::class,
        // Add more commands as you create them
    ],
    
    // Command namespace (for make:command)
    'command_namespace' => '{$namespace}\\Commands',
    
    // Command directory (for make:command)
    'command_directory' => 'src/Commands',
];
PHP;
        
        file_put_contents($configFile, $content);
        $output->success("Created configuration file: $configFile");
    }
    
    /**
     * @codeCoverageIgnore Directory creation with safeguards - tested via integration
     */
    private function createCommandsDirectory(Output $output): void
    {
        // Only create in current working directory, not in vendor
        $cwd = getcwd();
        
        // Don't create if we're in the Yalla package directory itself
        if (strpos($cwd, 'vendor/marwen-brini/yalla') !== false || 
            strpos($cwd, 'packages/php/yalla') !== false) {
            $output->warning("Skipping directory creation - running from package directory");
            return;
        }
        
        $directories = ['src', 'src/Commands'];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $output->success("Created directory: $dir");
            } else {
                $output->dim("Directory already exists: $dir");
            }
        }
    }
    
    /**
     * @codeCoverageIgnore Example command creation with safeguards - tested via integration
     */
    private function createExampleCommand(bool $force, Output $output): void
    {
        // Don't create example command if we're in the Yalla package itself
        $cwd = getcwd();
        if (strpos($cwd, 'vendor/marwen-brini/yalla') !== false || 
            strpos($cwd, 'packages/php/yalla') !== false) {
            $output->warning("Skipping example command - running from package directory");
            return;
        }
        
        $namespace = $this->detectNamespace();
        $commandFile = 'src/Commands/ExampleCommand.php';
        
        if (file_exists($commandFile) && !$force) {
            $output->warning("File '$commandFile' already exists. Use --force to overwrite.");
            return;
        }
        
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Commands;

use Yalla\\Commands\\Command;
use Yalla\\Output\\Output;

class ExampleCommand extends Command
{
    public function __construct()
    {
        \$this->name = 'example';
        \$this->description = 'An example command';
        
        \$this->addArgument('name', 'Your name', 'World');
        \$this->addOption('greeting', 'g', 'Custom greeting', 'Hello');
    }
    
    public function execute(array \$input, Output \$output): int
    {
        \$name = \$this->getArgument(\$input, 'name');
        \$greeting = \$this->getOption(\$input, 'greeting');
        
        \$output->success("\$greeting, \$name!");
        \$output->writeln('');
        \$output->info('This is an example command.');
        \$output->dim('Customize it or create your own commands.');
        
        return 0;
    }
}
PHP;
        
        file_put_contents($commandFile, $content);
        $output->success("Created example command: $commandFile");
    }
    
    /**
     * @codeCoverageIgnore Namespace detection from composer.json
     */
    private function detectNamespace(): string
    {
        if (file_exists('composer.json')) {
            $composer = json_decode(file_get_contents('composer.json'), true);
            
            // Check PSR-4 autoload
            if (isset($composer['autoload']['psr-4'])) {
                foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                    if ($path === 'src/' || $path === 'src') {
                        return rtrim($namespace, '\\');
                    }
                }
                
                // Get first namespace
                $namespaces = array_keys($composer['autoload']['psr-4']);
                if (!empty($namespaces)) {
                    return rtrim($namespaces[0], '\\');
                }
            }
        }
        
        return 'App';
    }
}