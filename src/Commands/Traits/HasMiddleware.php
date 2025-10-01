<?php

declare(strict_types=1);

namespace Yalla\Commands\Traits;

use Yalla\Commands\Middleware\MiddlewareInterface;
use Yalla\Commands\Middleware\MiddlewarePipeline;

trait HasMiddleware
{
    protected ?MiddlewarePipeline $middlewarePipeline = null;

    protected array $commandMiddleware = [];

    /**
     * Add middleware to the command
     */
    public function middleware(MiddlewareInterface $middleware): self
    {
        $this->commandMiddleware[] = $middleware;

        return $this;
    }

    /**
     * Add multiple middleware to the command
     */
    public function middlewares(array $middleware): self
    {
        foreach ($middleware as $m) {
            $this->middleware($m);
        }

        return $this;
    }

    /**
     * Get the middleware pipeline for this command
     */
    protected function getMiddlewarePipeline(): MiddlewarePipeline
    {
        if ($this->middlewarePipeline === null) {
            $this->middlewarePipeline = new MiddlewarePipeline;

            // Add command-specific middleware
            if (! empty($this->commandMiddleware)) {
                $this->middlewarePipeline->addMultiple($this->commandMiddleware);
            }
        }

        return $this->middlewarePipeline;
    }

    /**
     * Execute command through middleware pipeline
     *
     * @param  mixed  $output
     */
    protected function executeWithMiddleware(array $input, $output, \Closure $commandExecution): int
    {
        $pipeline = $this->getMiddlewarePipeline();

        if (! $pipeline->hasMiddleware()) {
            // No middleware, execute directly
            return $commandExecution($this, $input, $output);
        }

        // Execute through middleware pipeline
        return $pipeline->execute($this, $input, $output, $commandExecution);
    }

    /**
     * Check if the command has any middleware
     */
    public function hasMiddleware(): bool
    {
        return ! empty($this->commandMiddleware) ||
               ($this->middlewarePipeline !== null && $this->middlewarePipeline->hasMiddleware());
    }

    /**
     * Remove all middleware from the command
     */
    public function clearMiddleware(): self
    {
        $this->commandMiddleware = [];
        if ($this->middlewarePipeline !== null) {
            $this->middlewarePipeline->clear();
        }

        return $this;
    }

    /**
     * Get all middleware for this command
     */
    public function getMiddleware(): array
    {
        return $this->commandMiddleware;
    }
}
