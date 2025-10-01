<?php

declare(strict_types=1);

namespace Yalla\Filesystem;

use RuntimeException;

/**
 * Stub template generator for creating files from templates
 *
 * Supports variable replacement, conditionals, and loops in templates
 */
class StubGenerator
{
    /**
     * Registered stub templates
     */
    protected array $stubs = [];

    /**
     * Default stub directory path
     */
    protected string $stubPath;

    /**
     * Create a new StubGenerator instance
     *
     * @param  string|null  $stubPath  Default directory for stub files
     */
    public function __construct(?string $stubPath = null)
    {
        $this->stubPath = $stubPath ?? __DIR__.'/stubs';
    }

    /**
     * Register a stub template
     *
     * @param  string  $name  Stub identifier
     * @param  string  $path  Path to stub file
     */
    public function registerStub(string $name, string $path): void
    {
        $this->stubs[$name] = $path;
    }

    /**
     * Register all stub files in a directory
     *
     * @param  string  $directory  Directory containing stub files
     */
    public function registerStubDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            throw new RuntimeException("Stub directory not found: {$directory}");
        }

        $files = glob($directory.'/*.stub');

        // @codeCoverageIgnoreStart
        if ($files === false) {
            return;
        }
        // @codeCoverageIgnoreEnd

        foreach ($files as $file) {
            $name = basename($file, '.stub');
            $this->registerStub($name, $file);
        }
    }

    /**
     * Generate file from stub template
     *
     * @param  string  $stub  Stub name or path
     * @param  string  $outputPath  Output file path
     * @param  array  $replacements  Variable replacements
     * @return bool Success status
     * @throws RuntimeException If stub not found or write fails
     */
    public function generate(string $stub, string $outputPath, array $replacements = []): bool
    {
        $content = $this->render($stub, $replacements);

        // Ensure directory exists
        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException("Failed to create directory: {$directory}");
            }
        }

        // Write file with proper error handling
        set_error_handler(function () {
            return true;
        });
        $result = file_put_contents($outputPath, $content);
        restore_error_handler();

        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Render stub template to string
     *
     * @param  string  $stub  Stub name or path
     * @param  array  $replacements  Variable replacements
     * @return string Rendered content
     * @throws RuntimeException If stub not found
     */
    public function render(string $stub, array $replacements = []): string
    {
        $content = $this->getStubContent($stub);

        return $this->applyReplacements($content, $replacements);
    }

    /**
     * Generate content from inline template string
     *
     * @param  string  $template  Template string
     * @param  array  $replacements  Variable replacements
     * @return string Rendered content
     */
    public function renderString(string $template, array $replacements = []): string
    {
        return $this->applyReplacements($template, $replacements);
    }

    /**
     * Get stub file content
     *
     * @param  string  $stub  Stub name or path
     * @return string Stub content
     * @throws RuntimeException If stub not found
     */
    protected function getStubContent(string $stub): string
    {
        // Check registered stubs first
        if (isset($this->stubs[$stub])) {
            $path = $this->stubs[$stub];
        } elseif (file_exists($stub)) {
            // Direct path provided
            $path = $stub;
        } else {
            // Try default stub directory
            $path = $this->stubPath.'/'.$stub.'.stub';
        }

        if (! file_exists($path)) {
            throw new RuntimeException("Stub not found: {$stub}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Failed to read stub: {$path}");
        }

        return $content;
    }

    /**
     * Apply replacements to template content
     *
     * @param  string  $content  Template content
     * @param  array  $replacements  Variable replacements
     * @return string Processed content
     */
    protected function applyReplacements(string $content, array $replacements): string
    {
        // First, handle simple variable replacements
        $content = $this->replaceVariables($content, $replacements);

        // Handle conditional blocks
        $content = $this->processConditionals($content, $replacements);

        // Handle loop blocks
        $content = $this->processLoops($content, $replacements);

        return $content;
    }

    /**
     * Replace simple variables in content
     *
     * @param  string  $content  Template content
     * @param  array  $replacements  Variable replacements
     * @return string Content with variables replaced
     */
    protected function replaceVariables(string $content, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            // Skip arrays and objects for simple replacement
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $stringValue = (string) $value;

            // Multiple placeholder formats
            $patterns = [
                '{{'.$key.'}}',                    // {{key}}
                '{{ '.$key.' }}',                   // {{ key }}
                '${'.$key.'}',                      // ${key}
                '{{'.strtoupper($key).'}}',        // {{KEY}}
                '{{ '.strtoupper($key).' }}',      // {{ KEY }}
                '{{'.ucfirst($key).'}}',           // {{Key}}
                '{{ '.ucfirst($key).' }}',         // {{ Key }}
                '{{'.strtolower($key).'}}',        // {{key}} lowercase
                '{{ '.strtolower($key).' }}',      // {{ key }} lowercase
            ];

            $content = str_replace($patterns, $stringValue, $content);
        }

        return $content;
    }

    /**
     * Process conditional blocks in template
     *
     * @param  string  $content  Template content
     * @param  array  $data  Template data
     * @return string Processed content
     */
    protected function processConditionals(string $content, array $data): string
    {
        // Handle nested {{#if variable}} ... {{/if}} blocks properly
        $content = $this->processNestedConditionals($content, $data);

        // Handle {{#unless variable}} ... {{/unless}}
        $pattern = '/\{\{#unless\s+(\w+)\}\}(.*?)\{\{\/unless\}\}/s';

        $content = preg_replace_callback($pattern, function ($matches) use ($data) {
            $condition = $matches[1];
            $block = $matches[2];

            // Check if condition is falsy
            if (! $this->evaluateCondition($condition, $data)) {
                return $block;
            }

            return '';
        }, $content);

        // Handle {{#if variable}} ... {{else}} ... {{/if}}
        $pattern = '/\{\{#if\s+(\w+)\}\}(.*?)\{\{else\}\}(.*?)\{\{\/if\}\}/s';

        // @codeCoverageIgnoreStart
        $content = preg_replace_callback($pattern, function ($matches) use ($data) {
            $condition = $matches[1];
            $ifBlock = $matches[2];
            $elseBlock = $matches[3];

            if ($this->evaluateCondition($condition, $data)) {
                return $ifBlock;
            } else {
                return $elseBlock;
            }
        }, $content);
        // @codeCoverageIgnoreEnd

        return $content;
    }

    /**
     * Evaluate condition for conditionals
     *
     * @param  string  $condition  Condition to evaluate
     * @param  array  $data  Template data
     * @return bool Evaluation result
     */
    protected function evaluateCondition(string $condition, array $data): bool
    {
        if (! isset($data[$condition])) {
            return false;
        }

        $value = $data[$condition];

        // Check for truthy values
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value != 0;
        }

        if (is_string($value)) {
            return $value !== '' && $value !== '0';
        }

        if (is_array($value)) {
            return ! empty($value);
        }

        if (is_object($value)) {
            return true;
        }

        // @codeCoverageIgnoreStart
        return false;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Process nested conditional blocks properly
     *
     * @param  string  $content  Template content
     * @param  array  $data  Template data
     * @return string Processed content
     */
    protected function processNestedConditionals(string $content, array $data): string
    {
        $pos = 0;
        while (($startPos = strpos($content, '{{#if ', $pos)) !== false) {
            // Find the condition name
            $conditionEnd = strpos($content, '}}', $startPos);
            if ($conditionEnd === false) {
                break;
            }

            $conditionPart = substr($content, $startPos + 6, $conditionEnd - $startPos - 6);
            $condition = trim($conditionPart);

            // Skip special conditions like @first, @last etc - let other processors handle them
            if (str_starts_with($condition, '@')) {
                $pos = $startPos + 1;

                continue;
            }

            // Find matching {{/if}} with proper nesting
            $level = 0;
            $searchPos = $conditionEnd + 2;
            $endPos = false;

            while ($searchPos < strlen($content)) {
                $nextIf = strpos($content, '{{#if ', $searchPos);
                $nextEnd = strpos($content, '{{/if}}', $searchPos);

                if ($nextEnd === false) {
                    break;
                }

                if ($nextIf !== false && $nextIf < $nextEnd) {
                    $level++;
                    $searchPos = $nextIf + 6;
                } else {
                    if ($level === 0) {
                        $endPos = $nextEnd;

                        break;
                    }
                    $level--;
                    $searchPos = $nextEnd + 7;
                }
            }

            if ($endPos === false) {
                $pos = $startPos + 1;

                continue;
            }

            // Extract the block content
            $blockStart = $conditionEnd + 2;
            $block = substr($content, $blockStart, $endPos - $blockStart);

            // Evaluate condition and replace
            $replacement = '';
            if ($this->evaluateCondition($condition, $data)) {
                $replacement = $block;
            }

            $content = substr($content, 0, $startPos).$replacement.substr($content, $endPos + 7);
            $pos = $startPos;
        }

        return $content;
    }

    /**
     * Process loop blocks in template
     *
     * @param  string  $content  Template content
     * @param  array  $data  Template data
     * @return string Processed content
     */
    protected function processLoops(string $content, array $data): string
    {
        // Handle {{#each array}} ... {{/each}}
        $pattern = '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s';

        return preg_replace_callback($pattern, function ($matches) use ($data) {
            $arrayKey = $matches[1];
            $template = $matches[2];

            if (! isset($data[$arrayKey]) || ! is_array($data[$arrayKey])) {
                return '';
            }

            $result = '';
            foreach ($data[$arrayKey] as $index => $item) {
                $itemContent = $template;

                // Replace {{@index}} with current index
                $itemContent = str_replace('{{@index}}', (string) $index, $itemContent);
                $itemContent = str_replace('{{ @index }}', (string) $index, $itemContent);

                // Replace {{@key}} with current key
                $itemContent = str_replace('{{@key}}', (string) $index, $itemContent);
                $itemContent = str_replace('{{ @key }}', (string) $index, $itemContent);

                if (is_array($item)) {
                    // Replace item properties
                    foreach ($item as $key => $value) {
                        if (! is_array($value) && ! is_object($value)) {
                            $itemContent = str_replace('{{'.$key.'}}', (string) $value, $itemContent);
                            $itemContent = str_replace('{{ '.$key.' }}', (string) $value, $itemContent);
                        }
                    }

                    // Replace {{this.property}} syntax
                    foreach ($item as $key => $value) {
                        if (! is_array($value) && ! is_object($value)) {
                            $itemContent = str_replace('{{this.'.$key.'}}', (string) $value, $itemContent);
                            $itemContent = str_replace('{{ this.'.$key.' }}', (string) $value, $itemContent);
                        }
                    }
                } else {
                    // Simple value - replace {{this}} or {{item}}
                    $itemContent = str_replace('{{this}}', (string) $item, $itemContent);
                    $itemContent = str_replace('{{ this }}', (string) $item, $itemContent);
                    $itemContent = str_replace('{{item}}', (string) $item, $itemContent);
                    $itemContent = str_replace('{{ item }}', (string) $item, $itemContent);
                }

                $result .= $itemContent;
            }

            return $result;
        }, $content);
    }

    /**
     * Get list of registered stubs
     *
     * @return array Associative array of stub names to paths
     */
    public function getRegisteredStubs(): array
    {
        return $this->stubs;
    }

    /**
     * Check if stub is registered
     *
     * @param  string  $name  Stub name
     */
    public function hasStub(string $name): bool
    {
        return isset($this->stubs[$name]);
    }

    /**
     * Remove a registered stub
     *
     * @param  string  $name  Stub name
     */
    public function unregisterStub(string $name): void
    {
        unset($this->stubs[$name]);
    }

    /**
     * Clear all registered stubs
     */
    public function clearStubs(): void
    {
        $this->stubs = [];
    }
}
