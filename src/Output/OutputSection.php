<?php

declare(strict_types=1);

namespace Yalla\Output;

/**
 * Output section for grouped and updateable content
 */
class OutputSection
{
    private Output $output;

    private string $title;

    private array $content = [];

    private int $linesWritten = 0;

    private bool $cleared = false;

    public function __construct(Output $output, string $title)
    {
        $this->output = $output;
        $this->title = $title;
    }

    /**
     * Write a line to the section
     */
    public function writeln(string $message): void
    {
        $this->content[] = $message;
        $this->render();
    }

    /**
     * Clear the section content
     */
    public function clear(): void
    {
        $this->content = [];
        $this->clearScreen();
        $this->cleared = true;
    }

    /**
     * Overwrite section with new content
     */
    public function overwrite(string $message): void
    {
        $this->clear();
        $this->writeln($message);
    }

    /**
     * Render the section content
     */
    protected function render(): void
    {
        $this->clearScreen();

        $this->output->writeln("=== {$this->title} ===");
        foreach ($this->content as $line) {
            $this->output->writeln($line);
        }

        $this->linesWritten = count($this->content) + 1; // +1 for title
    }

    /**
     * Clear the screen area for this section
     */
    protected function clearScreen(): void
    {
        if ($this->linesWritten > 0) {
            // Use ANSI escape codes to move cursor up and clear lines
            for ($i = 0; $i < $this->linesWritten; $i++) {
                $this->output->write("\033[1A"); // Move cursor up one line
                $this->output->write("\033[2K"); // Clear current line
            }
        }
    }

    /**
     * Get the section title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the section content
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Check if section was cleared
     */
    public function isCleared(): bool
    {
        return $this->cleared;
    }
}
