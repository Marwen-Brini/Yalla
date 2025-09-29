<?php

declare(strict_types=1);

namespace Yalla\Commands\Middleware;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class MiddlewarePipeline
{
    private array $middleware = [];

    /**
     * Add middleware to the pipeline
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        $this->sortMiddleware();

        return $this;
    }

    /**
     * Add multiple middleware to the pipeline
     */
    public function addMultiple(array $middleware): self
    {
        foreach ($middleware as $m) {
            if (! $m instanceof MiddlewareInterface) {
                throw new \InvalidArgumentException('Middleware must implement MiddlewareInterface');
            }
            $this->middleware[] = $m;
        }
        $this->sortMiddleware();

        return $this;
    }

    /**
     * Remove middleware from the pipeline
     *
     * @param  string  $class  Class name of the middleware to remove
     */
    public function remove(string $class): self
    {
        $this->middleware = array_filter($this->middleware, function ($m) use ($class) {
            return ! ($m instanceof $class);
        });

        return $this;
    }

    /**
     * Execute the pipeline
     *
     * @param  \Closure  $destination  The final command execution
     * @return int Exit code
     */
    public function execute(Command $command, array $input, Output $output, \Closure $destination): int
    {
        // Filter middleware that should apply to this command
        $applicableMiddleware = array_filter($this->middleware, function ($m) use ($command, $input) {
            return $m->shouldApply($command, $input);
        });

        // Build the pipeline
        $pipeline = array_reduce(
            array_reverse($applicableMiddleware),
            $this->carry(),
            $destination
        );

        // Execute the pipeline
        return $pipeline($command, $input, $output);
    }

    /**
     * Create a middleware carrier function
     */
    private function carry(): \Closure
    {
        return function ($stack, $middleware) {
            return function ($command, $input, $output) use ($stack, $middleware) {
                return $middleware->handle($command, $input, $output, function ($cmd, $in, $out) use ($stack) {
                    return $stack($cmd, $in, $out);
                });
            };
        };
    }

    /**
     * Sort middleware by priority
     */
    private function sortMiddleware(): void
    {
        usort($this->middleware, function ($a, $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    /**
     * Get all middleware in the pipeline
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Clear all middleware from the pipeline
     */
    public function clear(): self
    {
        $this->middleware = [];

        return $this;
    }

    /**
     * Check if the pipeline has any middleware
     */
    public function hasMiddleware(): bool
    {
        return ! empty($this->middleware);
    }

    /**
     * Get the count of middleware in the pipeline
     */
    public function count(): int
    {
        return count($this->middleware);
    }
}
