<?php

declare(strict_types=1);

namespace Yalla\Repl\Input;

use Yalla\Repl\ReplContext;
use Yalla\Repl\History\HistoryManager;
use Yalla\Repl\Autocomplete\AutocompleteProvider;

/**
 * @codeCoverageIgnore
 */
class InputReader
{
    private ReplContext $context;
    private ?HistoryManager $history;
    private ?AutocompleteProvider $autocomplete = null;
    private bool $readlineAvailable;
    
    public function __construct(ReplContext $context, ?HistoryManager $history = null)
    {
        $this->context = $context;
        $this->history = $history;
        $this->readlineAvailable = function_exists('readline');
        
        if ($this->readlineAvailable) {
            $this->setupReadline();
        }
        
        // Initialize autocomplete if enabled
        if ($context->getConfig()->get('autocomplete.enabled', true)) {
            if (class_exists(AutocompleteProvider::class)) {
                $this->autocomplete = new AutocompleteProvider($context);
            }
        }
    }
    
    private function setupReadline(): void
    {
        // Set up readline completion
        // The completion function needs to handle the full line buffer, not just the current word
        if ($this->autocomplete) {
            $provider = $this->autocomplete;
            readline_completion_function(function($input, $index) use ($provider) {
                // $input contains the partial word being completed
                // $index is the position in the readline buffer
                
                // Get the full line buffer to understand context
                $info = readline_info();
                $line = $info['line_buffer'] ?? '';
                $point = $info['point'] ?? 0;
                
                // Extract the word being completed
                // Look backwards from current position to find start of word
                $start = $point;
                while ($start > 0 && !in_array($line[$start - 1] ?? ' ', [' ', "\t", "\n"])) {
                    $start--;
                }
                
                $word = substr($line, $start, $point - $start);
                
                // Get completions for this word
                $completions = $provider->complete($word);
                
                return $completions;
            });
        }
        
        // Load history into readline
        if ($this->history) {
            foreach ($this->history->getAll() as $command) {
                readline_add_history($command);
            }
        }
    }
    
    public function readline(string $prompt): ?string
    {
        if ($this->readlineAvailable) {
            // Re-register completion function before each readline
            // This ensures it's not lost
            if ($this->autocomplete) {
                $provider = $this->autocomplete;
                readline_completion_function(function($input, $index) use ($provider) {
                    // The $input already contains just the partial word being completed
                    $completions = $provider->complete($input);
                    
                    // Return only the completions, not modified
                    return $completions;
                });
            }
            
            // readline() doesn't handle ANSI codes well, so strip them
            $plainPrompt = $this->stripAnsiCodes($prompt);
            $input = readline($plainPrompt);
            
            if ($input === false) {
                // EOF (Ctrl+D)
                return null;
            }
            
            return $input;
        }
        
        // Fallback to standard input
        echo $prompt;
        $input = fgets(STDIN);
        
        if ($input === false) {
            return null;
        }
        
        return rtrim($input, "\n\r");
    }
    
    private function stripAnsiCodes(string $text): string
    {
        return preg_replace('/\033\[[0-9;]*m/', '', $text);
    }
    
    public function autocomplete(string $input, int $index): array
    {
        if (!$this->autocomplete) {
            return [];
        }
        
        return $this->autocomplete->complete($input);
    }
}