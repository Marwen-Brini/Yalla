<?php

declare(strict_types=1);

namespace Yalla\Repl;

use Yalla\Output\Output;

class ReplContext
{
    private array $namespaces = [];      // Namespace aliases

    private array $shortcuts = [];       // Class shortcuts

    private array $imports = [];         // Auto-imports

    private array $variables = [];       // Shared variables

    private array $commands = [];        // REPL commands (start with :)

    private array $evaluators = [];      // Custom evaluators

    private array $formatters = [];      // Output formatters

    private array $completers = [];      // Autocomplete providers

    private array $middleware = [];      // Input/output middleware

    private ReplConfig $config;

    private $historyManager = null;      // Reference to history manager

    public function __construct(ReplConfig $config)
    {
        $this->config = $config;
        $this->loadDefaults();
    }

    private function loadDefaults(): void
    {
        // Load default shortcuts from config
        foreach ($this->config->get('shortcuts', []) as $alias => $class) {
            $this->addShortcut($alias, $class);
        }

        // Load default imports from config
        foreach ($this->config->get('imports', []) as $import) {
            if (is_array($import)) {
                $this->addImport($import['class'], $import['alias'] ?? null);
            } else {
                $this->addImport($import);
            }
        }

        // Register built-in commands
        $this->addCommand('help', [$this, 'showHelp']);
        $this->addCommand('exit', [$this, 'exitRepl']);
        $this->addCommand('clear', [$this, 'clearScreen']);
        $this->addCommand('history', [$this, 'showHistory']);
        $this->addCommand('vars', [$this, 'showVariables']);
        $this->addCommand('imports', [$this, 'showImports']);
        $this->addCommand('mode', [$this, 'setDisplayMode']);
    }

    public function addNamespace(string $alias, string $namespace): self
    {
        $this->namespaces[$alias] = rtrim($namespace, '\\');

        return $this;
    }

    public function addShortcut(string $shortcut, string $fullClass): self
    {
        $this->shortcuts[$shortcut] = $fullClass;

        return $this;
    }

    public function getShortcuts(): array
    {
        return $this->shortcuts;
    }

    public function addImport(string $class, ?string $alias = null): self
    {
        $alias = $alias ?? $this->getClassBasename($class);
        $this->imports[$alias] = $class;

        return $this;
    }

    public function getImports(): array
    {
        return $this->imports;
    }

    public function setVariable(string $name, $value): self
    {
        $this->variables[$name] = $value;

        return $this;
    }

    public function getVariable(string $name)
    {
        return $this->variables[$name] ?? null;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function addCommand(string $name, callable $handler): self
    {
        $this->commands[$name] = $handler;

        return $this;
    }

    public function getCommand(string $name): ?callable
    {
        return $this->commands[$name] ?? null;
    }

    public function getCommands(): array
    {
        return array_keys($this->commands);
    }

    public function addEvaluator(string $name, callable $evaluator, int $priority = 0): self
    {
        $this->evaluators[] = [
            'name' => $name,
            'evaluator' => $evaluator,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->evaluators, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return $this;
    }

    public function getEvaluators(): array
    {
        return $this->evaluators;
    }

    public function addFormatter(string $type, callable $formatter): self
    {
        $this->formatters[$type] = $formatter;

        return $this;
    }

    public function getFormatter($value): ?callable
    {
        // Check exact type match
        $type = is_object($value) ? get_class($value) : gettype($value);
        if (isset($this->formatters[$type])) {
            return $this->formatters[$type];
        }

        // Check parent classes and interfaces
        if (is_object($value)) {
            foreach ($this->formatters as $formatterType => $formatter) {
                if ($value instanceof $formatterType) {
                    return $formatter;
                }
            }
        }

        return null;
    }

    public function addCompleter(string $name, callable $completer): self
    {
        $this->completers[$name] = $completer;

        return $this;
    }

    public function getCompleters(): array
    {
        return $this->completers;
    }

    public function addMiddleware(string $type, callable $middleware): self
    {
        if (! in_array($type, ['input', 'output'])) {
            throw new \InvalidArgumentException("Invalid middleware type: $type");
        }

        $this->middleware[$type][] = $middleware;

        return $this;
    }

    public function processInput(string $input): string
    {
        // Apply input middleware
        foreach ($this->middleware['input'] ?? [] as $middleware) {
            $input = $middleware($input, $this);
        }

        // Apply shortcuts
        foreach ($this->shortcuts as $shortcut => $fullClass) {
            // Match Class:: or new Class or Class::class
            $patterns = [
                '/\b'.preg_quote($shortcut).'::/i',
                '/\bnew\s+'.preg_quote($shortcut).'\b/i',
                '/\b'.preg_quote($shortcut).'::class\b/i',
            ];

            $replacements = [
                $fullClass.'::',
                'new '.$fullClass,
                $fullClass.'::class',
            ];

            $input = preg_replace($patterns, $replacements, $input);
        }

        // Apply namespace aliases
        foreach ($this->namespaces as $alias => $namespace) {
            $pattern = '/\b'.preg_quote($alias).'\\\\/';
            $input = preg_replace($pattern, $namespace.'\\', $input);
        }

        return $input;
    }

    public function processOutput($output)
    {
        // Apply output middleware
        foreach ($this->middleware['output'] ?? [] as $middleware) {
            $output = $middleware($output, $this);
        }

        return $output;
    }

    private function getClassBasename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }

    public function getConfig(): ReplConfig
    {
        return $this->config;
    }

    public function setHistoryManager($historyManager): void
    {
        $this->historyManager = $historyManager;
    }

    public function getHistoryManager()
    {
        return $this->historyManager;
    }

    // Built-in command handlers
    /**
     * @codeCoverageIgnore Interactive REPL command - cannot be tested
     */
    public function showHelp($args, $output, $context): void
    {
        $output->section('Available Commands');
        $commands = $this->getCommands();
        sort($commands);

        foreach ($commands as $command) {
            $output->writeln('  :'.$command);
        }

        $output->writeln('');
        $output->dim('Type :exit to quit the REPL');
    }

    /**
     * @codeCoverageIgnore Interactive REPL command - cannot be tested
     */
    public function exitRepl($args, $output, $context): bool
    {
        return false; // Signal to exit
    }

    /**
     * @codeCoverageIgnore Interactive REPL command - cannot be tested
     */
    public function clearScreen($args, $output, $context): void
    {
        // Clear screen using ANSI escape codes
        echo "\033[2J\033[;H";
    }

    /**
     * @codeCoverageIgnore Interactive REPL command - cannot be tested
     */
    public function showHistory($args, $output, $context): void
    {
        $output->section('Command History');

        $historyManager = $this->getHistoryManager();
        if (! $historyManager) {
            $output->dim('History is not available');

            return;
        }

        $history = $historyManager->getAll();
        if (empty($history)) {
            $output->dim('No commands in history');

            return;
        }

        foreach ($history as $index => $command) {
            $output->writeln(sprintf('  %3d  %s', $index + 1, $command));
        }
    }

    /**
     * @codeCoverageIgnore Interactive REPL command - cannot be tested
     */
    public function showVariables($args, $output, $context): void
    {
        $output->section('Variables');

        if (empty($this->variables)) {
            $output->dim('No variables defined');

            return;
        }

        foreach ($this->variables as $name => $value) {
            $type = is_object($value) ? get_class($value) : gettype($value);
            $output->writeln('  $'.$name.' : '.$type);
        }
    }

    /**
     * @codeCoverageIgnore Interactive REPL command - cannot be tested
     */
    public function showImports($args, $output, $context): void
    {
        $output->section('Imports');

        if (empty($this->imports)) {
            $output->dim('No imports defined');

            return;
        }

        foreach ($this->imports as $alias => $class) {
            $output->writeln('  '.$alias.' => '.$class);
        }
    }

    /**
     * @codeCoverageIgnore Interactive REPL command - cannot be tested
     */
    public function setDisplayMode($args, $output, $context): void
    {
        $validModes = ['compact', 'verbose', 'json', 'dump'];
        $currentMode = $this->config->get('display.mode', 'compact');
        
        // If no argument provided, show current mode and available modes
        if (empty($args)) {
            $output->section('Display Mode');
            $output->writeln('Current mode: ' . $output->color($currentMode, Output::CYAN));
            $output->writeln('');
            $output->writeln('Available modes:');
            foreach ($validModes as $mode) {
                $description = match($mode) {
                    'compact' => 'Default concise output',
                    'verbose' => 'Detailed object and array information',
                    'json' => 'JSON representation',
                    'dump' => 'PHP var_dump() style',
                    default => ''
                };
                $output->writeln('  ' . $output->color($mode, Output::YELLOW) . ' - ' . $description);
            }
            $output->writeln('');
            $output->dim('Usage: :mode <mode>');
            return;
        }
        
        $newMode = trim($args);
        
        // Validate the mode
        if (!in_array($newMode, $validModes)) {
            $output->error("Invalid mode: $newMode");
            $output->writeln('Valid modes: ' . implode(', ', $validModes));
            return;
        }
        
        // Update the configuration
        $this->config->set('display.mode', $newMode);
        
        $output->success("Display mode changed to: $newMode");
    }
}
