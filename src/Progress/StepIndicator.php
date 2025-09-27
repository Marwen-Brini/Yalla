<?php

declare(strict_types=1);

namespace Yalla\Progress;

use Yalla\Output\Output;

class StepIndicator
{
    protected Output $output;

    protected array $steps = [];

    protected array $statuses = [];

    protected int $currentStep = -1;

    protected float $startTime;

    protected bool $started = false;

    protected bool $finished = false;

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_FAILED = 'failed';

    // Display options
    protected array $options = [
        'showStepNumbers' => true,
        'showTime' => true,
        'indentSize' => 2,
        'symbols' => [
            'pending' => '⏳',
            'running' => '🔄',
            'complete' => '✅',
            'skipped' => '⏭️',
            'failed' => '❌',
        ],
        'colors' => [
            'pending' => Output::DIM,
            'running' => Output::CYAN,
            'complete' => Output::GREEN,
            'skipped' => Output::YELLOW,
            'failed' => Output::RED,
        ],
    ];

    public function __construct(Output $output, array $steps)
    {
        $this->output = $output;
        $this->setSteps($steps);
    }

    /**
     * Set the steps
     */
    public function setSteps(array $steps): self
    {
        $this->steps = [];
        $this->statuses = [];

        foreach ($steps as $key => $step) {
            if (is_array($step)) {
                $this->steps[$key] = $step['name'] ?? $step[0] ?? "Step $key";
                $this->statuses[$key] = [
                    'status' => self::STATUS_PENDING,
                    'message' => $step['description'] ?? $step[1] ?? null,
                    'startTime' => null,
                    'endTime' => null,
                ];
            } else {
                $this->steps[$key] = $step;
                $this->statuses[$key] = [
                    'status' => self::STATUS_PENDING,
                    'message' => null,
                    'startTime' => null,
                    'endTime' => null,
                ];
            }
        }

        return $this;
    }

    /**
     * Set custom symbols
     */
    public function setSymbols(array $symbols): self
    {
        $this->options['symbols'] = array_merge($this->options['symbols'], $symbols);

        return $this;
    }

    /**
     * Set custom colors
     */
    public function setColors(array $colors): self
    {
        $this->options['colors'] = array_merge($this->options['colors'], $colors);

        return $this;
    }

    /**
     * Start the step indicator
     */
    public function start(): self
    {
        if ($this->started) {
            return $this;
        }

        $this->started = true;
        $this->startTime = microtime(true);
        $this->currentStep = 0;

        $this->output->writeln('');
        $this->renderAll();
        $this->output->writeln('');

        if (! empty($this->steps)) {
            $this->statuses[0]['status'] = self::STATUS_RUNNING;
            $this->statuses[0]['startTime'] = microtime(true);
            $this->updateStep(0);
        }

        return $this;
    }

    /**
     * Move to next step
     */
    public function next(?string $message = null): self
    {
        if ($this->currentStep >= 0 && $this->currentStep < count($this->steps)) {
            $this->complete($this->currentStep, $message);
        }

        $this->currentStep++;

        if ($this->currentStep < count($this->steps)) {
            $this->statuses[$this->currentStep]['status'] = self::STATUS_RUNNING;
            $this->statuses[$this->currentStep]['startTime'] = microtime(true);
            $this->updateStep($this->currentStep);
        } else {
            $this->finish();
        }

        return $this;
    }

    /**
     * Complete a step
     */
    public function complete(int $step, ?string $message = null): self
    {
        if (! isset($this->statuses[$step])) {
            return $this;
        }

        $this->statuses[$step]['status'] = self::STATUS_COMPLETE;
        $this->statuses[$step]['endTime'] = microtime(true);

        if ($message !== null) {
            $this->statuses[$step]['message'] = $message;
        }

        $this->updateStep($step);

        return $this;
    }

    /**
     * Skip a step
     */
    public function skip(int $step, ?string $message = null): self
    {
        if (! isset($this->statuses[$step])) {
            return $this;
        }

        $this->statuses[$step]['status'] = self::STATUS_SKIPPED;
        $this->statuses[$step]['endTime'] = microtime(true);

        if ($message !== null) {
            $this->statuses[$step]['message'] = $message;
        }

        $this->updateStep($step);

        return $this;
    }

    /**
     * Fail a step
     */
    public function fail(int $step, ?string $message = null): self
    {
        if (! isset($this->statuses[$step])) {
            return $this;
        }

        $this->statuses[$step]['status'] = self::STATUS_FAILED;
        $this->statuses[$step]['endTime'] = microtime(true);

        if ($message !== null) {
            $this->statuses[$step]['message'] = $message;
        }

        $this->updateStep($step);

        return $this;
    }

    /**
     * Set a step as running
     */
    public function running(int $step, ?string $message = null): self
    {
        if (! isset($this->statuses[$step])) {
            return $this;
        }

        $this->statuses[$step]['status'] = self::STATUS_RUNNING;
        $this->statuses[$step]['startTime'] = microtime(true);

        if ($message !== null) {
            $this->statuses[$step]['message'] = $message;
        }

        $this->currentStep = $step;
        $this->updateStep($step);

        return $this;
    }

    /**
     * Finish all steps
     */
    public function finish(): self
    {
        if ($this->finished) {
            return $this;
        }

        $this->finished = true;

        // Mark any remaining steps as complete
        foreach ($this->statuses as $key => &$status) {
            if ($status['status'] === self::STATUS_PENDING) {
                $status['status'] = self::STATUS_SKIPPED;
                $status['endTime'] = microtime(true);
            } elseif ($status['status'] === self::STATUS_RUNNING) {
                $status['status'] = self::STATUS_COMPLETE;
                $status['endTime'] = microtime(true);
            }
        }

        $this->renderAll();
        $this->output->writeln('');
        $this->renderSummary();

        return $this;
    }

    /**
     * Update a specific step display
     */
    protected function updateStep(int $step): void
    {
        // Move cursor up to the step line
        $stepsFromTop = count($this->steps) - $step;
        $this->output->write("\033[{$stepsFromTop}A");

        // Render the step
        $this->renderStep($step);

        // Move cursor back down
        $this->output->write("\033[{$stepsFromTop}B");
    }

    /**
     * Render all steps
     */
    protected function renderAll(): void
    {
        foreach ($this->steps as $key => $step) {
            $this->renderStep($key);
            $this->output->writeln('');
        }
    }

    /**
     * Render a single step
     */
    protected function renderStep(int $key): void
    {
        $step = $this->steps[$key];
        $status = $this->statuses[$key];

        // Clear the line
        $this->output->write("\r\033[K");

        // Build the step output
        $indent = str_repeat(' ', $this->options['indentSize']);
        $symbol = $this->options['symbols'][$status['status']] ?? '?';
        $stepNumber = $this->options['showStepNumbers'] ? sprintf('[%d/%d] ', $key + 1, count($this->steps)) : '';

        $stepText = $indent.$symbol.' '.$stepNumber.$step;

        // Add message if available
        if ($status['message']) {
            $stepText .= ' - '.$status['message'];
        }

        // Add time if enabled and available
        if ($this->options['showTime'] && $status['startTime']) {
            $time = $this->getStepTime($key);
            if ($time) {
                $stepText .= ' ('.$time.')';
            }
        }

        // Apply color
        $color = $this->options['colors'][$status['status']] ?? null;
        if ($color !== null) {
            $stepText = $this->output->color($stepText, $color);
        }

        $this->output->write($stepText);
    }

    /**
     * Get the time for a step
     */
    protected function getStepTime(int $key): ?string
    {
        $status = $this->statuses[$key];

        if (! $status['startTime']) {
            return null;
        }

        if ($status['endTime']) {
            $duration = $status['endTime'] - $status['startTime'];
        } else {
            $duration = microtime(true) - $status['startTime'];
        }

        return $this->formatTime($duration);
    }

    /**
     * Format time duration
     */
    protected function formatTime(float $seconds): string
    {
        if ($seconds < 1) {
            return sprintf('%.0fms', $seconds * 1000);
        } elseif ($seconds < 60) {
            return sprintf('%.1fs', $seconds);
        } else {
            $minutes = floor($seconds / 60);
            $seconds = $seconds % 60;

            return sprintf('%dm %ds', $minutes, $seconds);
        }
    }

    /**
     * Render summary
     */
    protected function renderSummary(): void
    {
        $counts = [
            self::STATUS_COMPLETE => 0,
            self::STATUS_SKIPPED => 0,
            self::STATUS_FAILED => 0,
        ];

        foreach ($this->statuses as $status) {
            if (isset($counts[$status['status']])) {
                $counts[$status['status']]++;
            }
        }

        $totalTime = microtime(true) - $this->startTime;

        $summary = sprintf(
            'Summary: %d completed, %d skipped, %d failed (Total time: %s)',
            $counts[self::STATUS_COMPLETE],
            $counts[self::STATUS_SKIPPED],
            $counts[self::STATUS_FAILED],
            $this->formatTime($totalTime)
        );

        if ($counts[self::STATUS_FAILED] > 0) {
            $this->output->error($summary);
        } elseif ($counts[self::STATUS_SKIPPED] > 0) {
            $this->output->warning($summary);
        } else {
            $this->output->success($summary);
        }
    }

    /**
     * Get the current step index
     */
    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    /**
     * Get step status
     */
    public function getStepStatus(int $step): ?string
    {
        return $this->statuses[$step]['status'] ?? null;
    }

    /**
     * Check if finished
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }
}
