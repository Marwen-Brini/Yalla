<?php

declare(strict_types=1);

namespace Yalla\Environment;

use RuntimeException;

/**
 * Environment variable management with .env file support
 *
 * @package Yalla\Environment
 */
class Environment
{
    /**
     * Loaded environment variables
     */
    protected array $variables = [];

    /**
     * .env files to load
     */
    protected array $files = [];

    /**
     * Whether environment has been loaded
     */
    protected bool $loaded = false;

    /**
     * Create a new Environment instance
     *
     * @param array $files Array of .env file paths to load
     */
    public function __construct(array $files = ['.env'])
    {
        $this->files = $files;
        $this->load();
    }

    /**
     * Load environment variables from files and system
     *
     * @param bool $overwrite Whether to overwrite existing variables
     * @return void
     */
    public function load(bool $overwrite = false): void
    {
        if ($this->loaded && !$overwrite) {
            return;
        }

        // Start fresh if overwriting
        if ($overwrite) {
            $this->variables = [];
        } else {
            // Load from system environment first
            $this->variables = $_ENV;
        }

        // Load from .env files
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                $this->loadFile($file, $overwrite);
            }
        }

        $this->loaded = true;
    }

    /**
     * Load variables from a specific .env file
     *
     * @param string $path Path to .env file
     * @param bool $overwrite Whether to overwrite existing variables
     * @return void
     * @throws RuntimeException If file is not readable
     */
    protected function loadFile(string $path, bool $overwrite = false): void
    {
        if (!is_readable($path)) {
            throw new RuntimeException("Environment file not readable: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                [$key, $value] = array_map('trim', explode('=', $line, 2));

                // Skip if key is empty
                if (empty($key)) {
                    continue;
                }

                // Handle quoted values
                $value = $this->parseValue($value);

                // Expand variables
                $value = $this->expandVariables($value);

                // Set only if not exists or overwrite is true
                if ($overwrite || !isset($this->variables[$key])) {
                    $this->set($key, $value);
                }
            }
        }
    }

    /**
     * Parse value from .env file
     *
     * @param string $value Raw value from file
     * @return string Parsed value
     */
    protected function parseValue(string $value): string
    {
        // Handle special values first (before quote processing)
        $lowerValue = strtolower(trim($value));
        if ($lowerValue === 'true' || $lowerValue === '(true)') {
            return '1';
        }
        if ($lowerValue === 'false' || $lowerValue === '(false)') {
            return '0';
        }
        if ($lowerValue === 'null' || $lowerValue === '(null)' || $lowerValue === '') {
            return '';
        }

        // Remove surrounding quotes
        if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
            $value = $matches[2];

            // Handle escaped characters in double quotes
            if ($matches[1] === '"') {
                $value = str_replace(['\\n', '\\r', '\\t', '\\"'], ["\n", "\r", "\t", '"'], $value);
            }
        }

        return $value;
    }

    /**
     * Expand variables in value (${VAR} or $VAR format)
     *
     * @param string $value Value potentially containing variables
     * @return string Value with expanded variables
     */
    protected function expandVariables(string $value): string
    {
        // Replace ${VAR} format
        $value = preg_replace_callback('/\$\{([^}]+)\}/', function ($matches) {
            return $this->get($matches[1], '');
        }, $value);

        // Replace $VAR format (only if followed by non-alphanumeric)
        $value = preg_replace_callback('/\$([A-Z_][A-Z0-9_]*)(?![A-Z0-9_])/i', function ($matches) {
            return $this->get($matches[1], '');
        }, $value);

        return $value;
    }

    /**
     * Get environment variable
     *
     * @param string $key Variable name
     * @param mixed $default Default value if not found
     * @return mixed Variable value or default
     */
    public function get(string $key, $default = null)
    {
        if (isset($this->variables[$key])) {
            return $this->variables[$key];
        }

        $envValue = getenv($key);
        if ($envValue !== false) {
            return $envValue;
        }

        return $default;
    }

    /**
     * Get required environment variable
     *
     * @param string $key Variable name
     * @return mixed Variable value
     * @throws RuntimeException If variable is not set
     */
    public function getRequired(string $key)
    {
        $value = $this->get($key);

        if ($value === null) {
            throw new RuntimeException("Required environment variable not set: {$key}");
        }

        return $value;
    }

    /**
     * Set environment variable
     *
     * @param string $key Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $stringValue = (string) $value;

        $this->variables[$key] = $stringValue;
        $_ENV[$key] = $stringValue;
        putenv("{$key}={$stringValue}");
    }

    /**
     * Check if variable exists
     *
     * @param string $key Variable name
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get all variables
     *
     * @return array All environment variables
     */
    public function all(): array
    {
        return $this->variables;
    }

    /**
     * Check if current environment matches given name(s)
     *
     * @param string ...$environments Environment names to check
     * @return bool
     */
    public function is(string ...$environments): bool
    {
        $current = $this->get('APP_ENV', 'production');

        foreach ($environments as $env) {
            if ($current === $env) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if production environment
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->is('production', 'prod');
    }

    /**
     * Check if development environment
     *
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return $this->is('development', 'dev', 'local');
    }

    /**
     * Check if testing environment
     *
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->is('testing', 'test');
    }

    /**
     * Check if staging environment
     *
     * @return bool
     */
    public function isStaging(): bool
    {
        return $this->is('staging', 'stage');
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->getBool('APP_DEBUG', false);
    }

    /**
     * Get value as integer
     *
     * @param string $key Variable name
     * @param int $default Default value
     * @return int
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        return (int) $value;
    }

    /**
     * Get value as float
     *
     * @param string $key Variable name
     * @param float $default Default value
     * @return float
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key, $default);
        return (float) $value;
    }

    /**
     * Get value as boolean
     *
     * @param string $key Variable name
     * @param bool $default Default value
     * @return bool
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower($value);
            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * Get value as array
     *
     * @param string $key Variable name
     * @param array $default Default value
     * @return array
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            // Try JSON decode
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // Try comma-separated
            if (strpos($value, ',') !== false) {
                return array_map('trim', explode(',', $value));
            }

            // Single value as array
            return [$value];
        }

        return $default;
    }

    /**
     * Clear all loaded variables (useful for testing)
     *
     * @return void
     */
    public function clear(): void
    {
        // Clear all variables we've set
        foreach ($this->variables as $key => $value) {
            unset($_ENV[$key]);
            putenv($key); // Clear from getenv
        }

        $this->variables = [];
        $this->loaded = false;
    }

    /**
     * Reload environment variables
     *
     * @param bool $overwrite Whether to overwrite existing variables
     * @return void
     */
    public function reload(bool $overwrite = true): void
    {
        $this->loaded = false;
        $this->load($overwrite);
    }

    /**
     * Set the .env files to load
     *
     * @param array $files Array of file paths
     * @return void
     */
    public function setFiles(array $files): void
    {
        $this->files = $files;
        $this->loaded = false;
    }
}