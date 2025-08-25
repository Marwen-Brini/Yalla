<?php

declare(strict_types=1);

namespace Yalla\Repl\Autocomplete;

use Yalla\Repl\ReplContext;

class AutocompleteProvider
{
    private ReplContext $context;
    private array $cache = [];
    
    public function __construct(ReplContext $context)
    {
        $this->context = $context;
    }
    
    public function complete(string $partial): array
    {
        $suggestions = [];
        
        // Trim and normalize input
        $partial = trim($partial);
        
        if (empty($partial)) {
            return [];
        }
        
        // Check cache
        if (isset($this->cache[$partial])) {
            return $this->cache[$partial];
        }
        
        // REPL commands (start with :)
        if (str_starts_with($partial, ':')) {
            $commandPart = substr($partial, 1);
            foreach ($this->context->getCommands() as $command) {
                if (str_starts_with($command, $commandPart)) {
                    $suggestions[] = ':' . $command;
                }
            }
        }
        
        // Class shortcuts
        foreach ($this->context->getShortcuts() as $shortcut => $full) {
            if (stripos($shortcut, $partial) === 0) {
                $suggestions[] = $shortcut . '::';
            }
        }
        
        // Variables (start with $)
        if (str_starts_with($partial, '$')) {
            $varPart = substr($partial, 1);
            foreach (array_keys($this->context->getVariables()) as $var) {
                if (str_starts_with($var, $varPart)) {
                    $suggestions[] = '$' . $var;
                }
            }
        }
        
        // PHP built-in functions
        if (!str_contains($partial, '::') && !str_starts_with($partial, '$')) {
            $functions = get_defined_functions()['internal'];
            foreach ($functions as $function) {
                if (stripos($function, $partial) === 0) {
                    $suggestions[] = $function . '()';
                    if (count($suggestions) >= 20) {
                        break;
                    }
                }
            }
        }
        
        // Custom completers from extensions
        foreach ($this->context->getCompleters() as $name => $completer) {
            $customSuggestions = $completer($partial, $this->context);
            $suggestions = array_merge($suggestions, $customSuggestions);
        }
        
        // Limit suggestions
        $maxSuggestions = $this->context->getConfig()->get('autocomplete.max_suggestions', 10);
        $suggestions = array_slice(array_unique($suggestions), 0, $maxSuggestions);
        
        // Cache result
        $this->cache[$partial] = $suggestions;
        
        return $suggestions;
    }
}