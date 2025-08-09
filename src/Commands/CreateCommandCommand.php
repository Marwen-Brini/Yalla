<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

class CreateCommandCommand extends Command
{
    public function __construct()
    {
        $this->name = 'create:command';
        $this->description = 'Create a new command class';

        $this->addArgument('name', 'The name of the command (e.g., serve, deploy, make:model)', true);
        $this->addOption('class', 'c', 'Custom class name (default: generates from command name)', null);
        $this->addOption('dir', 'd', 'Directory to create the command in (default: src/Commands)', 'src/Commands');
        $this->addOption('force', 'f', 'Overwrite if file exists', false);
    }

    public function execute(array $input, Output $output): int
    {
        $commandName = $this->getArgument($input, 'name');
        $className = $this->getOption($input, 'class') ?: $this->generateClassName($commandName);
        $directory = $this->getOption($input, 'dir', 'src/Commands');
        $force = $this->getOption($input, 'force', false);

        // Ensure class name ends with Command
        if (! str_ends_with($className, 'Command')) {
            $className .= 'Command';
        }

        // Generate file path
        $filePath = $this->getProjectRoot().'/'.$directory.'/'.$className.'.php';

        // Check if file exists
        if ($this->fileExists($filePath) && ! $force) {
            $output->error("File already exists: $filePath");
            $output->info('Use --force to overwrite');

            return 1;
        }

        // Ensure directory exists
        $dir = dirname($filePath);
        if (! $this->createDirectory($dir)) {
            $output->error("Failed to create directory: $dir");

            return 1;
        }

        // Generate namespace from directory
        $namespace = $this->generateNamespace($directory);

        // Generate command class content
        $content = $this->generateCommandClass($className, $commandName, $namespace);

        // Write file
        if (! $this->writeFile($filePath, $content)) {
            $output->error("Failed to write file: $filePath");

            return 1;
        }

        $output->success("Command created successfully: $filePath");
        $output->writeln('');
        $output->info('Next steps:');
        $output->writeln("1. Edit the command class: $className");
        $output->writeln('2. Register it in your application:');
        $output->writeln('');
        $output->writeln($output->color("   \$app->register(new \\$namespace\\$className());", Output::CYAN));
        $output->writeln('');
        $output->writeln('3. Run your command:');
        $output->writeln('');
        $output->writeln($output->color("   ./bin/yalla $commandName", Output::CYAN));

        return 0;
    }

    private function generateClassName(string $commandName): string
    {
        // Convert command name to class name
        // serve -> ServeCommand
        // make:model -> MakeModelCommand
        // create-user -> CreateUserCommand

        $parts = preg_split('/[:_-]/', $commandName);
        $className = '';

        foreach ($parts as $part) {
            $className .= ucfirst(strtolower($part));
        }

        return $className.'Command';
    }

    private function generateNamespace(string $directory): string
    {
        // Convert directory to namespace
        // src/Commands -> YourApp\Commands
        // src/Commands/Make -> YourApp\Commands\Make

        $parts = explode('/', $directory);

        // Remove 'src' if it's the first part
        if ($parts[0] === 'src') {
            array_shift($parts);
        }

        // Try to detect the root namespace from composer.json
        $rootNamespace = $this->detectRootNamespace();

        if (empty($parts)) {
            return $rootNamespace;
        }

        return $rootNamespace.'\\'.implode('\\', $parts);
    }

    private function detectRootNamespace(): string
    {
        $composerPath = $this->getProjectRoot().'/composer.json';

        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);

            if (isset($composer['autoload']['psr-4'])) {
                foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                    // Get the first PSR-4 namespace
                    return rtrim($namespace, '\\');
                }
            }
        }

        // Default fallback
        return 'App';
    }

    private function getProjectRoot(): string
    {
        // Try to find project root by looking for composer.json
        $dir = getcwd();

        while ($dir !== '/') {
            if (file_exists($dir.'/composer.json')) {
                return $dir;
            }
            $dir = dirname($dir);
        }

        // Fallback to current directory
        return getcwd();
    }

    private function generateCommandClass(string $className, string $commandName, string $namespace): string
    {
        $template = <<<'PHP'
<?php

declare(strict_types=1);

namespace %NAMESPACE%;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class %CLASS_NAME% extends Command
{
    public function __construct()
    {
        $this->name = '%COMMAND_NAME%';
        $this->description = 'Description of your command';
        
        // Define arguments (positional parameters)
        // $this->addArgument('name', 'Description of the argument', true); // required
        // $this->addArgument('optional', 'Optional argument description', false); // optional
        
        // Define options (flags and named parameters)
        // $this->addOption('force', 'f', 'Force the operation', false);
        // $this->addOption('output', 'o', 'Output format', 'json');
    }
    
    public function execute(array $input, Output $output): int
    {
        // Get arguments and options
        // $name = $this->getArgument($input, 'name');
        // $force = $this->getOption($input, 'force', false);
        
        $output->info('Executing %COMMAND_NAME% command...');
        
        // Your command logic here
        
        $output->success('%COMMAND_NAME% completed successfully!');
        
        return 0; // Return 0 for success, non-zero for error
    }
}
PHP;

        return str_replace(
            ['%NAMESPACE%', '%CLASS_NAME%', '%COMMAND_NAME%'],
            [$namespace, $className, $commandName],
            $template
        );
    }

    /**
     * Check if a file exists (extracted for testability)
     */
    protected function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Create a directory (extracted for testability)
     */
    protected function createDirectory(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        return mkdir($dir, 0755, true);
    }

    /**
     * Write content to a file (extracted for testability)
     */
    protected function writeFile(string $path, string $content): bool
    {
        return file_put_contents($path, $content) !== false;
    }
}
