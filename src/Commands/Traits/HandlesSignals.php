<?php

declare(strict_types=1);

namespace Yalla\Commands\Traits;

use Yalla\Output\Output;

/**
 * Trait for handling process signals (Unix only)
 *
 * Provides signal handling capabilities for CLI commands
 * to allow graceful shutdown and cleanup on interrupt signals.
 */
trait HandlesSignals
{
    /**
     * Registered signal handlers
     */
    protected array $signalHandlers = [];

    /**
     * Whether signal handling is enabled
     */
    protected bool $signalsEnabled = false;

    /**
     * Output instance for signal messages
     */
    protected ?Output $signalOutput = null;

    /**
     * Register a signal handler
     *
     * @param  int  $signal  Signal number (e.g., SIGINT, SIGTERM)
     * @param  callable  $handler  Handler function to call
     */
    public function onSignal(int $signal, callable $handler): self
    {
        if (! $this->isSignalHandlingAvailable()) {
            return $this;
        }

        $this->signalHandlers[$signal] = $handler;
        $this->enableSignalHandling();

        // Wrap handler to allow cleanup
        // @codeCoverageIgnoreStart
        $wrappedHandler = function ($signo) use ($handler) {
            // Call the registered handler
            $result = $handler($signo);

            // If handler returns false, don't exit
            if ($result !== false) {
                $this->cleanup();
                exit(130); // Standard exit code for SIGINT
            }
        };
        // @codeCoverageIgnoreEnd

        pcntl_signal($signal, $wrappedHandler);

        return $this;
    }

    /**
     * Register cleanup handler for SIGINT (Ctrl+C)
     *
     * @param  callable  $handler  Cleanup function
     */
    public function onInterrupt(callable $handler): self
    {
        return $this->onSignal(SIGINT, $handler);
    }

    /**
     * Register cleanup handler for SIGTERM
     *
     * @param  callable  $handler  Cleanup function
     */
    public function onTerminate(callable $handler): self
    {
        return $this->onSignal(SIGTERM, $handler);
    }

    /**
     * Register handlers for common signals
     *
     * @param  callable  $handler  Handler function called with signal number
     */
    public function onCommonSignals(callable $handler): self
    {
        $this->onSignal(SIGINT, $handler);
        $this->onSignal(SIGTERM, $handler);

        if (defined('SIGHUP')) {
            $this->onSignal(SIGHUP, $handler);
        }

        return $this;
    }

    /**
     * Enable signal handling and set up async signal dispatching
     */
    protected function enableSignalHandling(): void
    {
        if (! $this->signalsEnabled && $this->isSignalHandlingAvailable()) {
            pcntl_async_signals(true);
            $this->signalsEnabled = true;
        }
    }

    /**
     * Check if signal handling is available (Unix only)
     */
    protected function isSignalHandlingAvailable(): bool
    {
        return function_exists('pcntl_signal') && function_exists('pcntl_async_signals');
    }

    /**
     * Check if signals are currently enabled
     */
    public function areSignalsEnabled(): bool
    {
        return $this->signalsEnabled;
    }

    /**
     * Set output instance for signal messages
     *
     * @param  Output  $output  Output instance
     */
    public function setSignalOutput(Output $output): self
    {
        $this->signalOutput = $output;

        return $this;
    }

    /**
     * Cleanup method called before exit
     * Override this in your command to add custom cleanup
     */
    protected function cleanup(): void
    {
        // Override in child class if needed
    }

    /**
     * Dispatch pending signals
     * Call this in long-running loops
     */
    protected function dispatchSignals(): void
    {
        if ($this->signalsEnabled && $this->isSignalHandlingAvailable()) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * Remove a signal handler
     *
     * @param  int  $signal  Signal number
     */
    public function removeSignalHandler(int $signal): self
    {
        if (isset($this->signalHandlers[$signal])) {
            unset($this->signalHandlers[$signal]);

            if ($this->isSignalHandlingAvailable()) {
                pcntl_signal($signal, SIG_DFL);
            }
        }

        return $this;
    }

    /**
     * Remove all signal handlers
     */
    public function removeAllSignalHandlers(): void
    {
        foreach (array_keys($this->signalHandlers) as $signal) {
            $this->removeSignalHandler($signal);
        }

        $this->signalHandlers = [];
    }

    /**
     * Get all registered signal handlers
     *
     * @return array Array of signal => handler pairs
     */
    public function getSignalHandlers(): array
    {
        return $this->signalHandlers;
    }

    /**
     * Check if a specific signal has a handler
     *
     * @param  int  $signal  Signal number
     */
    public function hasSignalHandler(int $signal): bool
    {
        return isset($this->signalHandlers[$signal]);
    }

    /**
     * Helper to create a default interrupt handler with output
     *
     * @param  string  $message  Message to display on interrupt
     */
    protected function registerDefaultInterruptHandler(string $message = 'Operation interrupted by user'): void
    {
        $this->onInterrupt(function ($signo) use ($message) {
            // @codeCoverageIgnoreStart
            if ($this->signalOutput !== null) {
                $this->signalOutput->writeln('');
                $this->signalOutput->warning($message);
            }
            // @codeCoverageIgnoreEnd

            return true; // Continue with cleanup and exit
        });
    }

    /**
     * Helper to register graceful shutdown handlers
     *
     * @param  string  $message  Message to display on shutdown
     */
    protected function registerGracefulShutdown(string $message = 'Shutting down gracefully...'): void
    {
        // @codeCoverageIgnoreStart
        $this->onCommonSignals(function ($signo) use ($message) {
            if ($this->signalOutput !== null) {
                $this->signalOutput->writeln('');
                $this->signalOutput->info($message);
            }

            return true; // Continue with cleanup and exit
        });
        // @codeCoverageIgnoreEnd
    }
}
