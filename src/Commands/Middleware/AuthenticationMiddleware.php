<?php

declare(strict_types=1);

namespace Yalla\Commands\Middleware;

use Yalla\Commands\Command;
use Yalla\Output\Output;

class AuthenticationMiddleware implements MiddlewareInterface
{
    private array $protectedCommands = [];

    private ?\Closure $authCallback;

    private int $priority;

    public function __construct(?\Closure $authCallback = null, int $priority = 200)
    {
        $this->authCallback = $authCallback;
        $this->priority = $priority;
    }

    /**
     * Handle the command execution with authentication
     */
    public function handle(Command $command, array $input, Output $output, \Closure $next): int
    {
        // Check if authentication is required
        if (! $this->isProtected($command->getName())) {
            return $next($command, $input, $output);
        }

        // Perform authentication
        if (! $this->authenticate($input, $output)) {
            $output->error('Authentication failed. Access denied.');

            return 77; // EXIT_NOPERM
        }

        // Continue with authenticated request
        return $next($command, $input, $output);
    }

    /**
     * Get the priority of this middleware
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Check if this middleware should be applied
     */
    public function shouldApply(Command $command, array $input): bool
    {
        // Apply to protected commands or when auth is explicitly requested
        return $this->isProtected($command->getName()) || isset($input['options']['auth']);
    }

    /**
     * Add a command to the protected list
     */
    public function protect(string $commandName): self
    {
        $this->protectedCommands[] = $commandName;

        return $this;
    }

    /**
     * Add multiple commands to the protected list
     */
    public function protectMultiple(array $commandNames): self
    {
        $this->protectedCommands = array_merge($this->protectedCommands, $commandNames);

        return $this;
    }

    /**
     * Check if a command is protected
     */
    public function isProtected(string $commandName): bool
    {
        return in_array($commandName, $this->protectedCommands);
    }

    /**
     * Set the authentication callback
     */
    public function setAuthCallback(\Closure $callback): self
    {
        $this->authCallback = $callback;

        return $this;
    }

    /**
     * Perform authentication
     */
    private function authenticate(array $input, Output $output): bool
    {
        // If custom auth callback is provided, use it
        if ($this->authCallback !== null) {
            return ($this->authCallback)($input, $output);
        }

        // Default authentication: check for token option
        if (isset($input['options']['token'])) {
            return $this->validateToken($input['options']['token']);
        }

        // Check for auth file
        $authFile = $input['options']['auth-file'] ?? '.yalla-auth';
        if (file_exists($authFile)) {
            $token = trim(file_get_contents($authFile));

            return $this->validateToken($token);
        }

        // Check environment variable
        $envToken = getenv('YALLA_AUTH_TOKEN');
        if ($envToken !== false) {
            return $this->validateToken($envToken);
        }

        return false;
    }

    /**
     * Validate an authentication token
     */
    private function validateToken(string $token): bool
    {
        // Simple token validation - override in subclass for custom logic
        return ! empty($token) && strlen($token) >= 32;
    }
}
