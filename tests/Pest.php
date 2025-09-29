<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Output\Output;

/*
|--------------------------------------------------------------------------
| Global Test Helpers and Utilities
|--------------------------------------------------------------------------
|
| Common utilities to improve test reliability and reduce duplication
|
*/

// Simple helper to create actual Output instances for testing
function createOutput(): Output
{
    return new Output;
}

// Helper to create command instances for testing
function createTestCommand(string $name = 'test:command'): Command
{
    return new class($name) extends Command
    {
        private string $commandName;

        public function __construct(string $name)
        {
            $this->commandName = $name;
        }

        public function getName(): string
        {
            return $this->commandName;
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };
}

// Setup and teardown
beforeEach(function () {
    // Any per-test setup can go here
});

afterEach(function () {
    // Cleanup after each test
});
