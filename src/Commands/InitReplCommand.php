<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

class InitReplCommand extends Command
{
    public function __construct()
    {
        $this->name = 'init:repl';
        $this->description = 'Initialize REPL configuration file';

        $this->addOption('force', 'f', 'Overwrite existing config file', false);
    }

    public function execute(array $input, Output $output): int
    {
        $configFile = 'repl.config.php';

        if (file_exists($configFile) && ! $this->getOption($input, 'force')) {
            $output->error("Config file '$configFile' already exists. Use --force to overwrite.");

            return 1;
        }

        $config = $this->getDefaultConfig();

        // @codeCoverageIgnoreStart
        if (! $this->writeConfigFile($configFile, $config)) {
            $output->error("Failed to write config file '$configFile'");

            return 1;
        }
        // @codeCoverageIgnoreEnd

        $output->success("Created '$configFile' successfully!");
        $output->writeln('');
        $output->info('You can now run: ./vendor/bin/yalla repl');
        $output->writeln('');
        $output->dim('Customize the config file to add:');
        $output->dim('  - Custom extensions for your project');
        $output->dim('  - Shortcuts to frequently used classes');
        $output->dim('  - Auto-imports for common namespaces');

        return 0;
    }

    private function getDefaultConfig(): string
    {
        return <<<'PHP'
<?php

/**
 * REPL Configuration File
 * 
 * This file configures the Yalla REPL for your project.
 * Customize it to add project-specific shortcuts, imports, and extensions.
 */

return [
    // Register custom REPL extensions
    'extensions' => [
        // Example: \App\Repl\MyCustomExtension::class,
    ],
    
    // Bootstrap files to load before starting REPL
    'bootstrap' => [
        'file' => __DIR__ . '/vendor/autoload.php',
        'files' => [
            // Additional files to require
            // Example: __DIR__ . '/bootstrap/app.php',
        ]
    ],
    
    // Class shortcuts for quick access
    'shortcuts' => [
        // Example shortcuts (uncomment and customize):
        // 'User' => \App\Models\User::class,
        // 'DB' => \Illuminate\Support\Facades\DB::class,
        // 'Carbon' => \Carbon\Carbon::class,
    ],
    
    // Auto-import these classes/namespaces
    'imports' => [
        // Example imports (uncomment and customize):
        // \Carbon\Carbon::class,
        // ['class' => \Illuminate\Support\Str::class, 'alias' => 'Str'],
        // ['class' => \Illuminate\Support\Arr::class, 'alias' => 'Arr'],
    ],
    
    // Pre-defined variables available in REPL
    'variables' => [
        // Example variables (uncomment and customize):
        // 'project' => 'My Project',
        // 'version' => '1.0.0',
    ],
    
    // Display settings
    'display' => [
        'prompt' => '[{counter}] > ',
        'welcome' => true,
        'colors' => true,
        'performance' => false,  // Show execution time
        'stacktrace' => false,   // Show full stack traces on errors
    ],
    
    // Command history settings
    'history' => [
        'enabled' => true,
        'file' => $_SERVER['HOME'] . '/.yalla_repl_history',
        'max_entries' => 1000,
        'ignore_duplicates' => true,
    ],
    
    // Autocomplete settings
    'autocomplete' => [
        'enabled' => true,
        'max_suggestions' => 20,
    ],
];
PHP;
    }

    private function writeConfigFile(string $path, string $content): bool
    {
        return file_put_contents($path, $content) !== false;
    }
}
