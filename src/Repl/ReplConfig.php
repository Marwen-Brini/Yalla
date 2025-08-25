<?php

declare(strict_types=1);

namespace Yalla\Repl;

class ReplConfig
{
    private array $config = [];

    private ?string $configFile = null;

    public function __construct(?string $configFile = null)
    {
        $this->configFile = $configFile;
        $this->loadDefaults();

        if ($configFile && file_exists($configFile)) {
            $this->loadFromFile($configFile);
        }
    }

    private function loadDefaults(): void
    {
        $this->config = [
            'extensions' => [],

            'bootstrap' => [
                'file' => null,
                'files' => [],
            ],

            'shortcuts' => [],

            'imports' => [],

            'variables' => [],

            'history' => [
                'enabled' => true,
                'file' => ($_SERVER['HOME'] ?? '/tmp').'/.yalla_history',
                'max_entries' => 1000,
                'ignore_duplicates' => true,
            ],

            'display' => [
                'colors' => true,
                'prompt' => '[{counter}] yalla> ',
                'welcome' => true,
                'goodbye' => true,
                'show_help' => true,
                'performance' => false,
                'stacktrace' => false,
                'max_depth' => 5,
                'max_items' => 100,
            ],

            'autocomplete' => [
                'enabled' => true,
                'min_chars' => 2,
                'max_suggestions' => 10,
            ],

            'security' => [
                'sandbox' => false,
                'allowed_functions' => [],
                'blocked_functions' => ['exec', 'system', 'shell_exec', 'passthru'],
                'max_execution_time' => 30,
            ],
        ];
    }

    private function loadFromFile(string $file): void
    {
        $loaded = require $file;

        if (is_array($loaded)) {
            $this->config = array_replace_recursive($this->config, $loaded);
        }
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set(string $key, $value): self
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (! isset($config[$k]) || ! is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }

        return $this;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function merge(array $config): self
    {
        $this->config = $this->mergeDeep($this->config, $config);

        return $this;
    }

    private function mergeDeep(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (isset($array1[$key])) {
                if (is_array($array1[$key]) && is_array($value)) {
                    $array1[$key] = $this->mergeDeep($array1[$key], $value);
                } else {
                    $array1[$key] = $value;
                }
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }
}
