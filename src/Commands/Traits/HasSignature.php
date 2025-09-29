<?php

declare(strict_types=1);

namespace Yalla\Commands\Traits;

trait HasSignature
{
    protected string $signature = '';

    protected array $argumentMetadata = [];

    protected array $optionMetadata = [];

    /**
     * Parse command signature to extract arguments and options
     */
    protected function parseSignature(): void
    {
        if (empty($this->signature)) {
            return;
        }

        // Initialize arrays if not already set
        if (! isset($this->arguments)) {
            $this->arguments = [];
        }
        if (! isset($this->options)) {
            $this->options = [];
        }

        // Extract command name and parameters
        $parts = explode(' ', trim($this->signature));

        // First part is the command name
        if (! empty($parts[0]) && ! isset($this->name)) {
            $this->name = $parts[0];
        }

        // Parse remaining parts for arguments and options
        for ($i = 1; $i < count($parts); $i++) {
            $part = $parts[$i];

            // Check for option first (more specific pattern)
            if ($this->isOption($part)) {
                $this->parseOption($part);
            } elseif ($this->isArgument($part)) {
                $this->parseArgument($part);
            }
        }
    }

    /**
     * Check if a signature part is an argument
     */
    private function isArgument(string $part): bool
    {
        return str_starts_with($part, '{') && str_ends_with($part, '}');
    }

    /**
     * Check if a signature part is an option
     */
    private function isOption(string $part): bool
    {
        return str_starts_with($part, '{--') || str_starts_with($part, '{-');
    }

    /**
     * Parse an argument from the signature
     */
    private function parseArgument(string $part): void
    {
        // Remove braces
        $content = trim($part, '{}');

        // Check if it's optional (has ? suffix)
        $isOptional = str_ends_with($content, '?');
        if ($isOptional) {
            $content = rtrim($content, '?');
        }

        // Check for default value
        $default = null;
        if (str_contains($content, '=')) {
            [$name, $default] = explode('=', $content, 2);
            $name = trim($name);
            $default = $this->parseDefaultValue(trim($default));
        } else {
            $name = $content;
        }

        // Check for array argument (has * suffix)
        $isArray = str_ends_with($name, '*');
        if ($isArray) {
            $name = rtrim($name, '*');
        }

        // Add the argument
        $description = ucfirst(str_replace(['_', '-'], ' ', $name));
        if ($isArray) {
            $description .= ' (multiple values)';
        }

        // Use parent class method if available
        if (method_exists($this, 'addArgument')) {
            $this->addArgument($name, $description, ! $isOptional && $default === null);
        } else {
            // Fallback: directly add to arguments array
            $this->arguments[] = [
                'name' => $name,
                'description' => $description,
                'required' => ! $isOptional && $default === null,
            ];
        }

        // Store additional metadata if needed
        if (! isset($this->argumentMetadata)) {
            $this->argumentMetadata = [];
        }
        $this->argumentMetadata[$name] = [
            'isArray' => $isArray,
            'default' => $default,
        ];
    }

    /**
     * Parse an option from the signature
     */
    private function parseOption(string $part): void
    {
        // Remove braces
        $content = trim($part, '{}');

        // Extract option name and shortcut
        $shortcut = null;
        $name = null;
        $default = null;
        $isValueRequired = false;

        if (str_starts_with($content, '--')) {
            // Long option
            $content = substr($content, 2);
        } elseif (str_starts_with($content, '-')) {
            // Short option (convert to long format)
            $content = substr($content, 1);
        }

        // Check for shortcut syntax (name|shortcut)
        if (str_contains($content, '|')) {
            [$name, $shortcut] = explode('|', $content, 2);
            $content = $name;
        }

        // Check for value requirement (=)
        if (str_contains($content, '=')) {
            [$content, $valueSpec] = explode('=', $content, 2);
            $isValueRequired = true;

            if (! empty($valueSpec)) {
                // Has default value
                $default = $this->parseDefaultValue($valueSpec);
            }
        } elseif (str_ends_with($content, '?')) {
            // Optional value
            $content = rtrim($content, '?');
            $isValueRequired = false;
        }

        // If no name was extracted from shortcut syntax, use content as name
        if ($name === null) {
            $name = $content;
        }

        // Generate description
        $description = ucfirst(str_replace(['_', '-'], ' ', $name));
        if ($isValueRequired) {
            $description .= ' (value required)';
        }

        // Use parent class method if available
        if (method_exists($this, 'addOption')) {
            $this->addOption($name, $shortcut, $description, $default);
        } else {
            // Fallback: directly add to options array
            $this->options[] = [
                'name' => $name,
                'shortcut' => $shortcut,
                'description' => $description,
                'default' => $default,
            ];
        }

        // Store additional metadata if needed
        if (! isset($this->optionMetadata)) {
            $this->optionMetadata = [];
        }
        $this->optionMetadata[$name] = [
            'isValueRequired' => $isValueRequired,
        ];
    }

    /**
     * Parse a default value from the signature
     */
    private function parseDefaultValue(string $value): mixed
    {
        // Remove quotes if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        // Check for boolean values
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }

        // Check for null
        if ($value === 'null') {
            return null;
        }

        // Check for numeric values
        if (is_numeric($value)) {
            if (str_contains($value, '.')) {
                return (float) $value;
            }

            return (int) $value;
        }

        // Check for array
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $arrayContent = substr($value, 1, -1);
            if (empty($arrayContent)) {
                return [];
            }

            // Simple array parsing (comma-separated)
            return array_map('trim', explode(',', $arrayContent));
        }

        return $value;
    }

    /**
     * Configure the command using the signature
     */
    protected function configureUsingSignature(): void
    {
        $this->parseSignature();
    }
}
