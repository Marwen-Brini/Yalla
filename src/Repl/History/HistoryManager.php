<?php

declare(strict_types=1);

namespace Yalla\Repl\History;

use Yalla\Repl\ReplConfig;

class HistoryManager
{
    private array $history = [];
    private string $historyFile;
    private int $maxEntries;
    private bool $ignoreDuplicates;
    private int $currentIndex = 0;
    private bool $enabled;
    
    public function __construct(ReplConfig $config)
    {
        $this->enabled = $config->get('history.enabled', true);
        $this->historyFile = $config->get('history.file');
        $this->maxEntries = $config->get('history.max_entries', 1000);
        $this->ignoreDuplicates = $config->get('history.ignore_duplicates', true);
        
        if ($this->enabled) {
            $this->load();
        }
    }
    
    public function add(string $command): void
    {
        if (!$this->enabled) {
            return;
        }
        
        // Ignore empty commands
        if (empty(trim($command))) {
            return;
        }
        
        // Ignore duplicates if configured
        if ($this->ignoreDuplicates && !empty($this->history)) {
            if (end($this->history) === $command) {
                return;
            }
        }
        
        // Add to history
        $this->history[] = $command;
        $this->currentIndex = count($this->history);
        
        // Trim history if needed
        if (count($this->history) > $this->maxEntries) {
            $this->history = array_slice($this->history, -$this->maxEntries);
        }
        
        // Save to file
        $this->save();
    }
    
    public function getPrevious(): ?string
    {
        if (!$this->enabled || empty($this->history)) {
            return null;
        }
        
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
        }
        
        return $this->history[$this->currentIndex] ?? null;
    }
    
    public function getNext(): ?string
    {
        if (!$this->enabled || empty($this->history)) {
            return null;
        }
        
        if ($this->currentIndex < count($this->history) - 1) {
            $this->currentIndex++;
            return $this->history[$this->currentIndex];
        }
        
        $this->currentIndex = count($this->history);
        return '';
    }
    
    public function search(string $query): array
    {
        if (!$this->enabled) {
            return [];
        }
        
        return array_filter($this->history, function($command) use ($query) {
            return stripos($command, $query) !== false;
        });
    }
    
    public function getAll(): array
    {
        return $this->history;
    }
    
    public function clear(): void
    {
        $this->history = [];
        $this->currentIndex = 0;
        
        if ($this->enabled && file_exists($this->historyFile)) {
            unlink($this->historyFile);
        }
    }
    
    private function load(): void
    {
        if (!file_exists($this->historyFile)) {
            return;
        }
        
        $content = file_get_contents($this->historyFile);
        // @codeCoverageIgnoreStart
        if ($content === false) {
            return;
        }
        // @codeCoverageIgnoreEnd
        
        $this->history = array_filter(explode("\n", $content));
        $this->currentIndex = count($this->history);
    }
    
    private function save(): void
    {
        $dir = dirname($this->historyFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->historyFile, implode("\n", $this->history));
    }
}