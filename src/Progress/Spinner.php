<?php

declare(strict_types=1);

namespace Yalla\Progress;

use Yalla\Output\Output;

class Spinner
{
    protected Output $output;

    protected string $message;

    protected bool $running = false;

    protected int $currentFrame = 0;

    protected float $startTime;

    protected string $lastOutput = '';

    protected ?int $pid = null;

    // Spinner frames sets
    protected array $frames = [
        'dots' => ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'],
        'dots2' => ['⣾', '⣽', '⣻', '⢿', '⡿', '⣟', '⣯', '⣷'],
        'line' => ['─', '\\', '│', '/'],
        'line2' => ['⠂', '-', '–', '—', '–', '-'],
        'pipe' => ['┤', '┘', '┴', '└', '├', '┌', '┬', '┐'],
        'simple' => ['.  ', '.. ', '...', '   '],
        'arrow' => ['←', '↖', '↑', '↗', '→', '↘', '↓', '↙'],
        'bounce' => ['⠁', '⠂', '⠄', '⠂'],
        'box' => ['◰', '◳', '◲', '◱'],
        'star' => ['✶', '✸', '✹', '✺', '✹', '✸'],
        'circle' => ['◐', '◓', '◑', '◒'],
        'square' => ['◻', '◼'],
        'triangle' => ['◢', '◣', '◤', '◥'],
        'clock' => ['🕐', '🕑', '🕒', '🕓', '🕔', '🕕', '🕖', '🕗', '🕘', '🕙', '🕚', '🕛'],
        'earth' => ['🌍', '🌎', '🌏'],
        'moon' => ['🌑', '🌒', '🌓', '🌔', '🌕', '🌖', '🌗', '🌘'],
        'hearts' => ['💛', '💙', '💜', '💚', '❤️'],
        'bar' => ['▁', '▂', '▃', '▄', '▅', '▆', '▇', '█', '▇', '▆', '▅', '▄', '▃', '▂', '▁'],
    ];

    protected string $frameSet = 'dots';

    protected float $interval = 0.1; // Frame update interval in seconds

    protected float $lastUpdate = 0;

    public function __construct(Output $output, string $message = '', string $frameSet = 'dots')
    {
        $this->output = $output;
        $this->message = $message;
        $this->setFrames($frameSet);
    }

    /**
     * Set the spinner frames
     */
    public function setFrames(string $frameSet): self
    {
        if (isset($this->frames[$frameSet])) {
            $this->frameSet = $frameSet;
        }

        return $this;
    }

    /**
     * Set custom frames
     */
    public function setCustomFrames(array $frames): self
    {
        $this->frames['custom'] = $frames;
        $this->frameSet = 'custom';

        return $this;
    }

    /**
     * Set the update interval
     */
    public function setInterval(float $interval): self
    {
        $this->interval = max(0.01, $interval);

        return $this;
    }

    /**
     * Set the spinner message
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        if ($this->running) {
            $this->render();
        }

        return $this;
    }

    /**
     * Start the spinner
     */
    public function start(string $message = ''): self
    {
        if ($this->running) {
            return $this;
        }

        if ($message !== '') {
            $this->message = $message;
        }

        $this->running = true;
        $this->startTime = microtime(true);
        $this->currentFrame = 0;
        $this->lastUpdate = microtime(true);
        $this->render();

        return $this;
    }

    /**
     * Advance the spinner (call this in your loop)
     */
    public function advance(): self
    {
        if (! $this->running) {
            return $this;
        }

        $currentTime = microtime(true);
        if (($currentTime - $this->lastUpdate) >= $this->interval) {
            $this->currentFrame = ($this->currentFrame + 1) % count($this->frames[$this->frameSet]);
            $this->lastUpdate = $currentTime;
            $this->render();
        }

        return $this;
    }

    /**
     * Stop the spinner with success message
     */
    public function success(string $message = ''): self
    {
        return $this->stop('✅', $message, Output::GREEN);
    }

    /**
     * Stop the spinner with error message
     */
    public function error(string $message = ''): self
    {
        return $this->stop('❌', $message, Output::RED);
    }

    /**
     * Stop the spinner with warning message
     */
    public function warning(string $message = ''): self
    {
        return $this->stop('⚠️', $message, Output::YELLOW);
    }

    /**
     * Stop the spinner with info message
     */
    public function info(string $message = ''): self
    {
        return $this->stop('ℹ️', $message, Output::CYAN);
    }

    /**
     * Stop the spinner
     */
    public function stop(string $symbol = '', string $message = '', ?string $color = null): self
    {
        if (! $this->running) {
            return $this;
        }

        $this->running = false;

        // Clear the spinner line
        $this->clear();

        // Display final message
        $finalMessage = $message ?: $this->message;

        if ($symbol) {
            $output = $symbol.' '.$finalMessage;
        } else {
            $output = $finalMessage;
        }

        if ($color !== null) {
            $output = $this->output->color($output, $color);
        }

        $this->output->writeln($output);

        return $this;
    }

    /**
     * Clear the spinner line
     */
    public function clear(): self
    {
        $clear = "\r".str_repeat(' ', strlen($this->lastOutput));
        $this->output->write($clear."\r");
        $this->lastOutput = '';

        return $this;
    }

    /**
     * Render the current frame
     */
    protected function render(): void
    {
        $frame = $this->frames[$this->frameSet][$this->currentFrame];
        $output = $frame.' '.$this->message;

        // Clear previous output
        $clear = "\r".str_repeat(' ', strlen($this->lastOutput));
        $this->output->write($clear);

        // Write new frame
        $this->output->write("\r".$output);
        $this->lastOutput = $output;
    }

    /**
     * Get elapsed time
     */
    public function getElapsedTime(): float
    {
        if (! isset($this->startTime)) {
            return 0;
        }

        return microtime(true) - $this->startTime;
    }

    /**
     * Check if spinner is running
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Get current frame
     */
    public function getCurrentFrame(): string
    {
        return $this->frames[$this->frameSet][$this->currentFrame];
    }
}
