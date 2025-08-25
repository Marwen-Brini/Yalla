<?php

declare(strict_types=1);

namespace Yalla\Repl;

interface ReplExtension
{
    /**
     * Register extension components with the REPL context
     */
    public function register(ReplContext $context): void;
    
    /**
     * Boot the extension (called after all extensions are registered)
     */
    public function boot(): void;
    
    /**
     * Get the extension name
     */
    public function getName(): string;
    
    /**
     * Get the extension version
     */
    public function getVersion(): string;
    
    /**
     * Get extension description
     */
    public function getDescription(): string;
}