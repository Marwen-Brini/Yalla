<?php

declare(strict_types=1);

namespace Yalla\Repl;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class ReplCommand extends Command
{
    private ReplContext $context;
    private ReplConfig $config;
    private array $extensions = [];
    
    public function __construct()
    {
        $this->name = 'repl';
        $this->description = 'Start an interactive REPL session';
        
        $this->addOption('config', 'c', 'Configuration file path', null);
        $this->addOption('bootstrap', 'b', 'Bootstrap file to load', null);
        $this->addOption('no-history', null, 'Disable command history', false);
        $this->addOption('no-colors', null, 'Disable colored output', false);
        $this->addOption('quiet', 'q', 'Minimal output mode', false);
    }
    
    /**
     * @codeCoverageIgnore
     */
    public function execute(array $input, Output $output): int
    {
        // Load configuration - check for default repl.config.php if no config specified
        $configPath = $this->getOption($input, 'config');
        if (!$configPath) {
            if (file_exists('repl.config.php')) {
                $configPath = 'repl.config.php';
            } else {
                // No config file found, show helpful message
                $output->info('No repl.config.php found. Using default configuration.');
                $output->dim('Run "./vendor/bin/yalla init:repl" to create a config file.');
                $output->writeln('');
            }
        }
        $this->config = new ReplConfig($configPath);
        
        // Override config with CLI options
        if ($this->getOption($input, 'no-history')) {
            $this->config->set('history.enabled', false);
        }
        
        if ($this->getOption($input, 'no-colors')) {
            $this->config->set('display.colors', false);
        }
        
        // Bootstrap environment
        $this->bootstrap($input);
        
        // Initialize context
        $this->context = new ReplContext($this->config);
        
        // Load and register extensions
        $this->loadExtensions();
        
        // Boot extensions
        $this->bootExtensions();
        
        // Start REPL session
        $session = new ReplSession($this->context, $output, $this->config);
        return $session->run();
    }
    
    /**
     * @codeCoverageIgnore Called from execute method - requires terminal interaction
     */
    private function bootstrap(array $input): void
    {
        $bootstrapFile = $this->getOption($input, 'bootstrap') 
            ?? $this->config->get('bootstrap.file');
            
        if ($bootstrapFile && file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }
        
        // Load additional bootstrap files from config
        foreach ($this->config->get('bootstrap.files', []) as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    /**
     * @codeCoverageIgnore Called from execute method - requires terminal interaction
     */
    private function loadExtensions(): void
    {
        $extensionClasses = $this->config->get('extensions', []);
        
        foreach ($extensionClasses as $extensionClass) {
            if (!class_exists($extensionClass)) {
                throw new \RuntimeException("Extension class not found: $extensionClass");
            }
            
            $extension = new $extensionClass();
            
            if (!$extension instanceof ReplExtension) {
                throw new \RuntimeException("Invalid extension: $extensionClass");
            }
            
            $this->extensions[] = $extension;
            $extension->register($this->context);
        }
    }
    
    /**
     * @codeCoverageIgnore Called from execute method - requires terminal interaction
     */
    private function bootExtensions(): void
    {
        foreach ($this->extensions as $extension) {
            $extension->boot();
        }
    }
    
    public function registerExtension(ReplExtension $extension): self
    {
        $this->extensions[] = $extension;
        return $this;
    }
}