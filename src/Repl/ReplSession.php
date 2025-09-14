<?php

declare(strict_types=1);

namespace Yalla\Repl;

use Yalla\Output\Output;
use Yalla\Repl\History\HistoryManager;
use Yalla\Repl\Input\InputReader;

/**
 * @codeCoverageIgnore
 */
class ReplSession
{
    private ReplContext $context;

    private Output $output;

    private ReplConfig $config;

    private ?HistoryManager $history = null;

    private ?InputReader $inputReader = null;

    private array $localVariables = [];

    private bool $running = true;

    private int $commandCounter = 0;

    public function __construct(ReplContext $context, Output $output, ReplConfig $config)
    {
        $this->context = $context;
        $this->output = $output;
        $this->config = $config;

        // Initialize components
        if (class_exists(HistoryManager::class)) {
            $this->history = new HistoryManager($config);
            // Set history manager in context so commands can access it
            $context->setHistoryManager($this->history);
        }

        if (class_exists(InputReader::class)) {
            $this->inputReader = new InputReader($context, $this->history);
        }

        // Set up signal handlers for graceful exit
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'handleInterrupt']);
            pcntl_signal(SIGTERM, [$this, 'handleTerminate']);
        }
    }

    public function run(): int
    {
        $this->displayWelcome();
        $this->displayHelp();

        while ($this->running) {
            try {
                $input = $this->readInput();

                if ($input === null) {
                    // EOF (Ctrl+D)
                    break;
                }

                if (empty(trim($input))) {
                    continue;
                }

                // Add to history
                if ($this->history) {
                    $this->history->add($input);
                }

                // Process the input
                $this->processInput($input);

            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }

        $this->displayGoodbye();

        return 0;
    }

    private function displayWelcome(): void
    {
        if ($this->config->get('display.welcome', true)) {
            $this->output->writeln('');
            if (method_exists($this->output, 'box')) {
                $this->output->box('Yalla REPL v1.3.0', Output::CYAN);
            } else {
                $this->output->writeln($this->output->color('╔══════════════════════╗', Output::CYAN));
                $this->output->writeln($this->output->color('║  Yalla REPL v1.3.0   ║', Output::CYAN));
                $this->output->writeln($this->output->color('╚══════════════════════╝', Output::CYAN));
            }
            $this->output->writeln('');
        }
    }

    private function displayHelp(): void
    {
        if ($this->config->get('display.show_help', true)) {
            if (method_exists($this->output, 'dim')) {
                $this->output->dim('Type :help for available commands, :exit to quit');
            } else {
                $this->output->writeln($this->output->color('Type :help for available commands, :exit to quit', Output::DIM));
            }
            $this->output->writeln('');
        }
    }

    private function displayGoodbye(): void
    {
        if ($this->config->get('display.goodbye', true)) {
            $this->output->writeln('');
            $this->output->info('Goodbye!');
        }
    }

    private function readInput(): ?string
    {
        $this->commandCounter++;

        // Build prompt
        $prompt = $this->buildPrompt();

        // Read input with autocompletion if available
        if ($this->inputReader) {
            return $this->inputReader->readline($prompt);
        }

        // Fallback to standard input
        echo $prompt;
        $input = fgets(STDIN);

        if ($input === false) {
            return null;
        }

        return rtrim($input, "\n\r");
    }

    private function buildPrompt(): string
    {
        $promptTemplate = $this->config->get('display.prompt', '>>> ');

        // Replace variables in prompt
        $prompt = str_replace(
            ['{counter}', '{cwd}', '{time}'],
            [$this->commandCounter, basename(getcwd()), date('H:i:s')],
            $promptTemplate
        );

        // Add color if enabled
        if ($this->config->get('display.colors', true)) {
            $prompt = $this->output->color($prompt, Output::GREEN);
        }

        return $prompt;
    }

    private function processInput(string $input): void
    {
        // Check for REPL commands (start with :)
        if (str_starts_with($input, ':')) {
            $this->executeReplCommand($input);

            return;
        }

        // Check for variable assignment
        if ($this->isVariableAssignment($input)) {
            $this->executeVariableAssignment($input);

            return;
        }

        // Regular code evaluation
        $this->evaluateCode($input);
    }

    private function executeReplCommand(string $input): void
    {
        $parts = explode(' ', substr($input, 1), 2);
        $commandName = $parts[0];
        $args = $parts[1] ?? '';

        $command = $this->context->getCommand($commandName);

        if (! $command) {
            $this->output->error("Unknown command: :$commandName");
            $this->suggestCommands($commandName);

            return;
        }

        // Execute command
        $result = $command($args, $this->output, $this->context);

        if ($result === false) {
            $this->running = false;
        }
    }

    private function suggestCommands(string $input): void
    {
        $commands = $this->context->getCommands();
        $suggestions = [];

        foreach ($commands as $command) {
            $similarity = similar_text($input, $command, $percent);
            if ($percent > 50) {
                $suggestions[] = $command;
            }
        }

        if (! empty($suggestions)) {
            if (method_exists($this->output, 'dim')) {
                $this->output->dim('Did you mean: :'.implode(', :', $suggestions).'?');
            } else {
                $this->output->writeln($this->output->color('Did you mean: :'.implode(', :', $suggestions).'?', Output::DIM));
            }
        }
    }

    private function isVariableAssignment(string $input): bool
    {
        // Check for $var = expression pattern
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*\s*=/', $input) === 1;
    }

    private function executeVariableAssignment(string $input): void
    {
        // Parse variable name
        preg_match('/^\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)/', $input, $matches);

        if (empty($matches)) {
            $this->output->error('Invalid variable assignment');

            return;
        }

        $varName = $matches[1];
        $expression = $matches[2];

        // Evaluate the expression
        $value = $this->evaluateExpression($expression);

        // Store in local variables
        $this->localVariables[$varName] = $value;

        // Also store in context for sharing
        $this->context->setVariable($varName, $value);

        // Display result
        $this->output->write($this->output->color("$$varName = ", Output::DIM));
        $this->displayResult($value);
    }

    private function evaluateCode(string $code): void
    {
        try {
            // Preprocess code
            $code = $this->context->processInput($code);

            // Track performance
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            // Try custom evaluators first
            $result = null;
            $evaluated = false;

            foreach ($this->context->getEvaluators() as $evaluatorData) {
                $evaluator = $evaluatorData['evaluator'];
                if ($evaluator($code, $result, $this->context)) {
                    $evaluated = true;

                    break;
                }
            }

            // Fall back to PHP evaluation
            if (! $evaluated) {
                $result = $this->evaluateExpression($code);
            }

            // Calculate metrics
            $executionTime = (microtime(true) - $startTime) * 1000;
            $memoryUsed = memory_get_usage() - $startMemory;

            // Process output through middleware
            $result = $this->context->processOutput($result);

            // Display result
            $this->displayResult($result);

            // Show performance metrics if enabled
            if ($this->config->get('display.performance', false)) {
                $this->displayMetrics($executionTime, $memoryUsed);
            }

        } catch (\ParseError $e) {
            $this->output->error('Parse Error: '.$e->getMessage());
            $this->showErrorContext($code, $e->getLine());
        } catch (\Error $e) {
            $this->output->error('Error: '.$e->getMessage());
            if ($this->config->get('display.stacktrace', false)) {
                if (method_exists($this->output, 'dim')) {
                    $this->output->dim($e->getTraceAsString());
                } else {
                    $this->output->writeln($this->output->color($e->getTraceAsString(), Output::DIM));
                }
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function evaluateExpression(string $code)
    {
        // Make local variables available
        extract($this->localVariables);

        // Make context variables available
        extract($this->context->getVariables());

        // Auto-import classes
        foreach ($this->context->getImports() as $alias => $class) {
            if (! class_exists($alias)) {
                class_alias($class, $alias);
            }
        }

        // Strip trailing semicolons to avoid syntax errors when wrapping
        // This allows users to naturally type commands with semicolons
        $code = rtrim($code, '; ');

        // Wrap code to capture return value
        $wrappedCode = "return ($code);";

        // Evaluate
        $result = eval($wrappedCode);

        // Capture any new variables
        $definedVars = get_defined_vars();
        foreach ($definedVars as $name => $value) {
            if (! in_array($name, ['code', 'wrappedCode', 'result']) &&
                ! isset($this->localVariables[$name])) {
                $this->localVariables[$name] = $value;
            }
        }

        return $result;
    }

    private function displayResult($result): void
    {
        // Check for custom formatter
        $formatter = $this->context->getFormatter($result);

        if ($formatter) {
            $formatter($result, $this->output);

            return;
        }

        // Get display mode from config
        $displayMode = $this->config->get('display.mode', 'compact');

        // Handle different display modes
        switch ($displayMode) {
            case 'json':
                $this->displayResultAsJson($result);

                break;
            case 'dump':
                $this->displayResultAsDump($result);

                break;
            case 'verbose':
                $this->displayResultAsVerbose($result);

                break;
            case 'compact':
            default:
                $this->displayResultAsCompact($result);

                break;
        }
    }

    private function displayResultAsCompact($result): void
    {
        // Default formatting based on type (current implementation)
        switch (gettype($result)) {
            case 'NULL':
                if (method_exists($this->output, 'dim')) {
                    $this->output->dim('null');
                } else {
                    $this->output->writeln($this->output->color('null', Output::DIM));
                }

                break;

            case 'boolean':
                $this->output->writeln(
                    $result
                        ? $this->output->color('true', Output::GREEN)
                        : $this->output->color('false', Output::RED)
                );

                break;

            case 'integer':
            case 'double':
                $this->output->writeln($this->output->color((string) $result, Output::YELLOW));

                break;

            case 'string':
                $this->output->writeln($this->output->color('"'.$result.'"', Output::GREEN));

                break;

            case 'array':
                $this->displayArray($result);

                break;

            case 'object':
                $this->displayObject($result);

                break;

            default:
                var_dump($result);
        }
    }

    private function displayResultAsJson($result): void
    {
        try {
            $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new \Exception('JSON encoding failed: '.json_last_error_msg());
            }
            $this->output->writeln($this->output->color($json, Output::CYAN));
        } catch (\Exception $e) {
            $this->output->error('Cannot display as JSON: '.$e->getMessage());
            $this->displayResultAsCompact($result);
        }
    }

    private function displayResultAsDump($result): void
    {
        ob_start();
        var_dump($result);
        $dump = ob_get_clean();
        $this->output->writeln($dump);
    }

    private function displayResultAsVerbose($result): void
    {
        switch (gettype($result)) {
            case 'object':
                $this->displayObjectVerbose($result);

                break;
            case 'array':
                $this->displayArrayVerbose($result);

                break;
            default:
                // For scalars, use compact mode
                $this->displayResultAsCompact($result);
        }
    }

    private function displayArray(array $array): void
    {
        if (empty($array)) {
            $this->output->writeln('[]');

            return;
        }

        // Check if it's associative
        $isAssoc = array_keys($array) !== range(0, count($array) - 1);

        // For small arrays, display inline
        if (count($array) <= 3 && ! $isAssoc) {
            $this->output->writeln('['.implode(', ', array_map([$this, 'formatValue'], $array)).']');

            return;
        }

        // For larger arrays, display as table or list
        if ($this->isTableArray($array)) {
            $this->displayArrayAsTable($array);
        } else {
            $this->displayArrayAsList($array);
        }
    }

    private function isTableArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        $first = reset($array);

        // Only try table display for arrays of arrays
        // Objects are better displayed as a list since they might have protected/private properties
        if (! is_array($first)) {
            return false;
        }

        // Check if all elements are arrays with same structure
        $firstKeys = array_keys($first);

        foreach ($array as $item) {
            if (! is_array($item)) {
                return false;
            }
            if (array_keys($item) !== $firstKeys) {
                return false;
            }
        }

        return true;
    }

    private function displayArrayAsTable(array $array): void
    {
        $first = reset($array);

        // This method now only handles arrays of arrays (checked by isTableArray)
        $headers = array_keys($first);

        $rows = [];
        foreach ($array as $item) {
            $row = [];
            foreach ($headers as $header) {
                $value = $item[$header] ?? '';
                $row[] = $this->formatValue($value);
            }
            $rows[] = $row;
        }

        $this->output->table($headers, $rows);
    }

    private function displayArrayAsList(array $array): void
    {
        $this->output->writeln('[');

        $index = 0;
        foreach ($array as $key => $value) {
            $prefix = '  ';

            if (array_keys($array) !== range(0, count($array) - 1)) {
                // Associative array
                $prefix .= $this->output->color("'$key'", Output::CYAN).' => ';
            } else {
                // Indexed array
                $prefix .= $this->output->color("$index", Output::DIM).' => ';
            }

            $this->output->write($prefix);

            if (is_array($value) || is_object($value)) {
                $this->output->writeln($this->formatValue($value));
            } else {
                $this->output->writeln($this->formatValue($value));
            }

            $index++;
        }

        $this->output->writeln(']');
    }

    private function displayObject($object): void
    {
        $class = get_class($object);
        $this->output->writeln($this->output->color($class, Output::CYAN).' {');

        // Get properties
        $reflection = new \ReflectionObject($object);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $name = $property->getName();
            $value = $property->getValue($object);

            $visibility = '';
            if ($property->isPrivate()) {
                $visibility = $this->output->color('private', Output::RED).' ';
            } elseif ($property->isProtected()) {
                $visibility = $this->output->color('protected', Output::YELLOW).' ';
            } else {
                $visibility = $this->output->color('public', Output::GREEN).' ';
            }

            $this->output->writeln('  '.$visibility.'$'.$name.' = '.$this->formatValue($value));
        }

        $this->output->writeln('}');
    }

    private function displayObjectVerbose($object): void
    {
        $class = get_class($object);
        $reflection = new \ReflectionObject($object);

        // Display class information
        $this->output->writeln($this->output->color('═══ Object Details ═══', Output::CYAN));
        $this->output->writeln('Class: '.$this->output->color($class, Output::YELLOW));

        // Display parent classes
        $parent = $reflection->getParentClass();
        if ($parent) {
            $this->output->writeln('Parent: '.$this->output->color($parent->getName(), Output::DIM));
        }

        // Display interfaces
        $interfaces = $reflection->getInterfaceNames();
        if (! empty($interfaces)) {
            $this->output->writeln('Interfaces: '.$this->output->color(implode(', ', $interfaces), Output::DIM));
        }

        // Display all properties (including inherited)
        $this->output->writeln($this->output->color('Properties:', Output::CYAN));
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $name = $property->getName();

            try {
                $value = $property->getValue($object);
            } catch (\Exception $e) {
                $value = '<inaccessible>';
            }

            $visibility = '';
            if ($property->isPrivate()) {
                $visibility = $this->output->color('private', Output::RED);
            } elseif ($property->isProtected()) {
                $visibility = $this->output->color('protected', Output::YELLOW);
            } else {
                $visibility = $this->output->color('public', Output::GREEN);
            }

            $static = $property->isStatic() ? ' static' : '';
            $declaring = $property->getDeclaringClass()->getName();

            $this->output->writeln(sprintf(
                '  %s%s $%s = %s %s',
                $visibility,
                $static,
                $name,
                $this->formatValue($value),
                $declaring !== $class ? $this->output->color("(from $declaring)", Output::DIM) : ''
            ));
        }

        // Display methods summary
        $methods = $reflection->getMethods();
        $publicMethods = array_filter($methods, fn ($m) => $m->isPublic() && ! $m->isConstructor() && ! $m->isDestructor());

        if (! empty($publicMethods)) {
            $this->output->writeln($this->output->color('Public Methods:', Output::CYAN));
            foreach (array_slice($publicMethods, 0, 10) as $method) {
                $params = array_map(fn ($p) => '$'.$p->getName(), $method->getParameters());
                $this->output->writeln('  - '.$method->getName().'('.implode(', ', $params).')');
            }

            if (count($publicMethods) > 10) {
                $this->output->writeln($this->output->color('  ... and '.(count($publicMethods) - 10).' more', Output::DIM));
            }
        }
    }

    private function displayArrayVerbose(array $array): void
    {
        $this->output->writeln($this->output->color('═══ Array Details ═══', Output::CYAN));
        $this->output->writeln('Type: '.(array_keys($array) === range(0, count($array) - 1) ? 'Indexed' : 'Associative'));
        $this->output->writeln('Count: '.count($array));

        if (! empty($array)) {
            $this->output->writeln($this->output->color('Contents:', Output::CYAN));

            $index = 0;
            foreach ($array as $key => $value) {
                if ($index >= 20) {
                    $this->output->writeln($this->output->color('  ... and '.(count($array) - 20).' more items', Output::DIM));

                    break;
                }

                $keyStr = is_string($key) ? "'$key'" : (string) $key;
                $this->output->writeln('  ['.$this->output->color($keyStr, Output::YELLOW).'] => '.$this->formatValueVerbose($value));
                $index++;
            }
        } else {
            $this->output->writeln($this->output->color('  <empty>', Output::DIM));
        }
    }

    private function formatValueVerbose($value): string
    {
        switch (gettype($value)) {
            case 'object':
                $class = get_class($value);
                $reflection = new \ReflectionObject($value);
                $propCount = count($reflection->getProperties());
                $methodCount = count($reflection->getMethods());

                return $this->output->color($class, Output::CYAN).
                       " {$propCount} props, {$methodCount} methods}";

            case 'array':
                $count = count($value);
                $type = array_keys($value) === range(0, $count - 1) ? 'indexed' : 'assoc';

                return $this->output->color("array($count, $type)", Output::MAGENTA);

            default:
                return $this->formatValue($value);
        }
    }

    private function formatValue($value): string
    {
        switch (gettype($value)) {
            case 'NULL':
                return $this->output->color('null', Output::DIM);
            case 'boolean':
                return $value
                    ? $this->output->color('true', Output::GREEN)
                    : $this->output->color('false', Output::RED);
            case 'integer':
            case 'double':
                return $this->output->color((string) $value, Output::YELLOW);
            case 'string':
                // Truncate long strings
                if (strlen($value) > 50) {
                    $value = substr($value, 0, 47).'...';
                }

                return $this->output->color('"'.$value.'"', Output::GREEN);
            case 'array':
                return 'array('.count($value).')';
            case 'object':
                $class = get_class($value);
                $lastSlash = strrchr($class, '\\');
                $shortClass = $lastSlash !== false ? substr($lastSlash, 1) : $class;

                // Try to get object info using reflection for better display
                try {
                    $reflection = new \ReflectionObject($value);

                    // Check for __toString method
                    if ($reflection->hasMethod('__toString')) {
                        $string = (string) $value;
                        if (strlen($string) > 30) {
                            $string = substr($string, 0, 27).'...';
                        }

                        return $this->output->color($shortClass.' "'.$string.'"', Output::CYAN);
                    }

                    // Try to get any identifying property (id, ID, name, title, etc.)
                    $publicProps = get_object_vars($value);
                    if (! empty($publicProps)) {
                        // Just show first few public properties
                        $info = [];
                        $count = 0;
                        foreach ($publicProps as $key => $val) {
                            if ($count++ >= 2) {
                                break;
                            } // Show max 2 properties
                            if (is_scalar($val)) {
                                $info[] = $key.': '.(is_string($val) ? '"'.substr($val, 0, 20).'"' : $val);
                            }
                        }
                        if (! empty($info)) {
                            return $this->output->color($shortClass.' {'.implode(', ', $info).'}', Output::CYAN);
                        }
                    }
                } catch (\Exception $e) {
                    // Fall back to simple class name
                }

                return $this->output->color($shortClass.' object', Output::CYAN);
            default:
                return (string) $value;
        }
    }

    private function displayMetrics(float $time, int $memory): void
    {
        $timeColor = Output::GREEN;
        if ($time > 100) {
            $timeColor = Output::YELLOW;
        }
        if ($time > 500) {
            $timeColor = Output::RED;
        }

        $metrics = sprintf(
            '⏱️  %s | 💾 %s',
            $this->output->color(sprintf('%.2fms', $time), $timeColor),
            $this->formatBytes($memory)
        );

        if (method_exists($this->output, 'dim')) {
            $this->output->dim($metrics);
        } else {
            $this->output->writeln($this->output->color($metrics, Output::DIM));
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $bytes, $units[$unitIndex]);
    }

    private function showErrorContext(string $code, int $line): void
    {
        $lines = explode("\n", $code);
        $totalLines = count($lines);

        // Show 2 lines before and after the error
        $start = max(0, $line - 3);
        $end = min($totalLines, $line + 2);

        $this->output->writeln('');
        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $lineCode = $lines[$i] ?? '';

            if ($lineNum === $line) {
                $this->output->writeln(
                    $this->output->color(" > $lineNum | ", Output::RED).$lineCode
                );
            } else {
                $this->output->writeln(
                    $this->output->color("   $lineNum | ", Output::DIM).$lineCode
                );
            }
        }
        $this->output->writeln('');
    }

    private function handleException(\Exception $e): void
    {
        $this->output->error(get_class($e).': '.$e->getMessage());

        if ($this->config->get('display.stacktrace', false)) {
            if (method_exists($this->output, 'dim')) {
                $this->output->dim($e->getTraceAsString());
            } else {
                $this->output->writeln($this->output->color($e->getTraceAsString(), Output::DIM));
            }
        } else {
            if (method_exists($this->output, 'dim')) {
                $this->output->dim('Use :config display.stacktrace true to see full trace');
            } else {
                $this->output->writeln($this->output->color('Use :config display.stacktrace true to see full trace', Output::DIM));
            }
        }
    }

    public function handleInterrupt(): void
    {
        $this->output->writeln('');
        $this->output->warning('Interrupted. Type :exit to quit.');
    }

    public function handleTerminate(): void
    {
        $this->running = false;
    }
}
