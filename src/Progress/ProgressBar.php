<?php

declare(strict_types=1);

namespace Yalla\Progress;

use Yalla\Output\Output;

class ProgressBar
{
    protected Output $output;
    protected int $total;
    protected int $current = 0;
    protected int $barWidth = 30;
    protected string $format = 'normal';
    protected ?string $message = null;
    protected float $startTime;
    protected bool $finished = false;
    protected int $redrawFrequency = 1;
    protected int $lastDrawn = 0;
    protected string $lastOutput = '';

    // Format templates
    protected array $formats = [
        'normal' => ' {current}/{total} [{bar}] {percent}%',
        'verbose' => ' {current}/{total} [{bar}] {percent}% - {message}',
        'detailed' => ' {current}/{total} [{bar}] {percent}% - {elapsed} / {estimated}',
        'minimal' => ' [{bar}] {percent}%',
        'memory' => ' {current}/{total} [{bar}] {percent}% - Memory: {memory}'
    ];

    protected array $options = [
        'barChar' => '=',
        'emptyBarChar' => '-',
        'progressChar' => '>',
        'redrawFrequency' => 1,
    ];

    public function __construct(Output $output, int $total)
    {
        $this->output = $output;
        $this->total = max(1, $total);
        $this->startTime = microtime(true);
    }

    /**
     * Start the progress bar
     */
    public function start(): self
    {
        $this->current = 0;
        $this->startTime = microtime(true);
        $this->finished = false;
        $this->display();

        return $this;
    }

    /**
     * Advance the progress bar
     */
    public function advance(int $step = 1): self
    {
        $this->setProgress($this->current + $step);

        return $this;
    }

    /**
     * Set the current progress
     */
    public function setProgress(int $progress): self
    {
        $this->current = min($progress, $this->total);

        if ($this->shouldRedraw()) {
            $this->display();
            $this->lastDrawn = $this->current;
        }

        return $this;
    }

    /**
     * Set the progress bar width
     */
    public function setBarWidth(int $width): self
    {
        $this->barWidth = max(1, $width);

        return $this;
    }

    /**
     * Set the display format
     */
    public function setFormat(string $format): self
    {
        if (isset($this->formats[$format])) {
            $this->format = $format;
        } else {
            $this->format = 'normal';
        }

        return $this;
    }

    /**
     * Set a custom format template
     */
    public function setCustomFormat(string $template): self
    {
        $this->formats['custom'] = $template;
        $this->format = 'custom';

        return $this;
    }

    /**
     * Set the progress message
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        if (!$this->finished) {
            $this->display();
        }

        return $this;
    }

    /**
     * Set redraw frequency (for performance)
     */
    public function setRedrawFrequency(int $frequency): self
    {
        $this->redrawFrequency = max(1, $frequency);

        return $this;
    }

    /**
     * Finish the progress bar
     */
    public function finish(): self
    {
        if ($this->finished) {
            return $this;
        }

        $this->current = $this->total;
        $this->finished = true;
        $this->display();
        $this->output->writeln(''); // New line after completion

        return $this;
    }

    /**
     * Clear the progress bar
     */
    public function clear(): self
    {
        $this->overwrite('');

        return $this;
    }

    /**
     * Check if should redraw
     */
    protected function shouldRedraw(): bool
    {
        if ($this->current === $this->total) {
            return true;
        }

        if ($this->redrawFrequency === 1) {
            return true;
        }

        return ($this->current - $this->lastDrawn) >= $this->redrawFrequency;
    }

    /**
     * Display the progress bar
     */
    protected function display(): void
    {
        $format = $this->formats[$this->format] ?? $this->formats['normal'];

        $placeholders = [
            '{current}' => str_pad((string) $this->current, strlen((string) $this->total), ' ', STR_PAD_LEFT),
            '{total}' => $this->total,
            '{bar}' => $this->buildBar(),
            '{percent}' => $this->getPercentage(),
            '{message}' => $this->message ?? '',
            '{elapsed}' => $this->getElapsedTime(),
            '{estimated}' => $this->getEstimatedTime(),
            '{memory}' => $this->getMemoryUsage(),
        ];

        $output = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $format
        );

        $this->overwrite($output);
        $this->lastOutput = $output;
    }

    /**
     * Overwrite the current line
     */
    protected function overwrite(string $text): void
    {
        // Clear the line
        $clear = "\r" . str_repeat(' ', strlen($this->lastOutput));
        $this->output->write($clear);

        // Write new content
        $this->output->write("\r" . $text);
    }

    /**
     * Build the progress bar string
     */
    protected function buildBar(): string
    {
        $percentage = $this->getPercentage() / 100;
        $completeLength = (int) ($this->barWidth * $percentage);

        $bar = str_repeat($this->options['barChar'], $completeLength);

        if ($completeLength < $this->barWidth) {
            $bar .= $this->options['progressChar'];
            $bar .= str_repeat($this->options['emptyBarChar'], $this->barWidth - $completeLength - 1);
        }

        return $bar;
    }

    /**
     * Get current percentage
     */
    protected function getPercentage(): int
    {
        if ($this->total === 0) {
            return 100;
        }

        return (int) floor(($this->current / $this->total) * 100);
    }

    /**
     * Get elapsed time
     */
    protected function getElapsedTime(): string
    {
        $elapsed = microtime(true) - $this->startTime;

        return $this->formatTime($elapsed);
    }

    /**
     * Get estimated time remaining
     */
    protected function getEstimatedTime(): string
    {
        if ($this->current === 0) {
            return '--:--';
        }

        $elapsed = microtime(true) - $this->startTime;
        $rate = $this->current / $elapsed;

        if ($rate === 0) {
            // @codeCoverageIgnoreStart
            return '--:--';
            // @codeCoverageIgnoreEnd
        }

        $remaining = ($this->total - $this->current) / $rate;

        return $this->formatTime($remaining);
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage(): string
    {
        $memory = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($memory, 1024));

        return sprintf('%.2f %s', $memory / pow(1024, $power), $units[$power]);
    }

    /**
     * Format time in human-readable format
     */
    protected function formatTime(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%02ds', $seconds);
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        if ($minutes < 60) {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }

        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Get the current progress
     */
    public function getProgress(): int
    {
        return $this->current;
    }

    /**
     * Get the total
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Check if finished
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }
}