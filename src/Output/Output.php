<?php

declare(strict_types=1);

namespace Yalla\Output;

use Yalla\Input\InteractiveInput;

class Output
{
    const RESET = "\033[0m";

    const BLACK = "\033[30m";

    const RED = "\033[31m";

    const GREEN = "\033[32m";

    const YELLOW = "\033[33m";

    const BLUE = "\033[34m";

    const MAGENTA = "\033[35m";

    const CYAN = "\033[36m";

    const WHITE = "\033[37m";

    const BG_BLACK = "\033[40m";

    const BG_RED = "\033[41m";

    const BG_GREEN = "\033[42m";

    const BG_YELLOW = "\033[43m";

    const BG_BLUE = "\033[44m";

    const BG_MAGENTA = "\033[45m";

    const BG_CYAN = "\033[46m";

    const BG_WHITE = "\033[47m";

    const BOLD = "\033[1m";

    const DIM = "\033[2m";

    const UNDERLINE = "\033[4m";

    private bool $supportsColors;

    private ?InteractiveInput $interactiveInput = null;

    public function __construct()
    {
        $this->supportsColors = $this->hasColorSupport();
    }

    private function hasColorSupport(): bool
    {
        if ($this->isWindows()) {
            return $this->hasWindowsColorSupport();
        }

        return $this->hasUnixColorSupport();
    }

    /**
     * Check if running on Windows (extracted for testability)
     */
    protected function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Check Windows color support (extracted for testability)
     */
    protected function hasWindowsColorSupport(): bool
    {
        return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
    }

    /**
     * Check Unix color support (extracted for testability)
     */
    protected function hasUnixColorSupport(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    public function write(string $message, bool $newline = false): void
    {
        echo $message;
        if ($newline) {
            echo PHP_EOL;
        }
    }

    public function writeln(string $message): void
    {
        $this->write($message, true);
    }

    public function success(string $message): void
    {
        $this->writeln($this->color($message, self::GREEN));
    }

    public function error(string $message): void
    {
        $this->writeln($this->color($message, self::RED));
    }

    public function warning(string $message): void
    {
        $this->writeln($this->color($message, self::YELLOW));
    }

    public function info(string $message): void
    {
        $this->writeln($this->color($message, self::CYAN));
    }

    public function color(string $text, string $color): string
    {
        if (! $this->supportsColors) {
            return $text;
        }

        return $color.$text.self::RESET;
    }

    public function table(array $headers, array $rows, array $options = []): void
    {
        // Use new Table class if options are provided
        if (! empty($options)) {
            $table = new Table($this, $options);
            $table->setHeaders($headers)
                ->setRows($rows)
                ->render();

            return;
        }

        // Legacy table rendering for backward compatibility
        $columnWidths = [];

        foreach ($headers as $i => $header) {
            $columnWidths[$i] = strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $columnWidths[$i] = max($columnWidths[$i] ?? 0, strlen((string) $cell));
            }
        }

        $this->drawTableRow($headers, $columnWidths, true);
        $this->drawTableSeparator($columnWidths);

        foreach ($rows as $row) {
            $this->drawTableRow($row, $columnWidths);
        }
    }

    public function createTable(array $options = []): Table
    {
        return new Table($this, $options);
    }

    private function drawTableRow(array $row, array $columnWidths, bool $isHeader = false): void
    {
        $line = '│';
        foreach ($row as $i => $cell) {
            $width = $columnWidths[$i];
            $padded = str_pad((string) $cell, $width);

            if ($isHeader) {
                $padded = $this->color($padded, self::BOLD);
            }

            $line .= ' '.$padded.' │';
        }
        $this->writeln($line);
    }

    private function drawTableSeparator(array $columnWidths): void
    {
        $line = '├';
        foreach ($columnWidths as $i => $width) {
            $line .= str_repeat('─', $width + 2);
            if ($i < count($columnWidths) - 1) {
                $line .= '┼';
            }
        }
        $line .= '┤';
        $this->writeln($line);
    }

    public function box(string $content, string $color = self::WHITE): void
    {
        $lines = explode("\n", $content);
        $maxLength = max(array_map('strlen', $lines));
        $width = $maxLength + 4;

        // Top border
        $top = '╔'.str_repeat('═', $width - 2).'╗';
        $this->writeln($this->color($top, $color));

        // Content lines
        foreach ($lines as $line) {
            $padding = $width - 4 - strlen($line);
            $paddedLine = '║ '.$line.str_repeat(' ', $padding + 1).' ║';
            $this->writeln($this->color($paddedLine, $color));
        }

        // Bottom border
        $bottom = '╚'.str_repeat('═', $width - 2).'╝';
        $this->writeln($this->color($bottom, $color));
    }

    public function progressBar(int $current, int $total, int $width = 50): void
    {
        if ($total === 0) {
            return;
        }

        $percent = ($current / $total) * 100;
        $filled = (int) (($current / $total) * $width);
        $empty = $width - $filled;

        $bar = '['.str_repeat('█', $filled).str_repeat('░', $empty).']';
        $percentage = sprintf(' %d%%', $percent);

        // Use carriage return to overwrite the same line
        $this->write("\r".$bar.$percentage);

        if ($current === $total) {
            $this->writeln('');
        }
    }

    public function spinner(int $step = 0): void
    {
        $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $frame = $frames[$step % count($frames)];
        $this->write("\r".$frame);
    }

    public function dim(string $message): void
    {
        $this->writeln($this->color($message, self::DIM));
    }

    public function bold(string $message): void
    {
        $this->writeln($this->color($message, self::BOLD));
    }

    public function underline(string $message): void
    {
        $this->writeln($this->color($message, self::UNDERLINE));
    }

    public function section(string $title): void
    {
        $this->writeln('');
        $this->writeln($this->color('━━━ '.$title.' ━━━', self::CYAN));
        $this->writeln('');
    }

    public function tree(array $items, int $level = 0): void
    {
        foreach ($items as $key => $value) {
            $isLast = ($key === array_key_last($items));
            $prefix = str_repeat('  ', $level);

            if ($level > 0) {
                $prefix .= $isLast ? '└── ' : '├── ';
            }

            if (is_array($value)) {
                $this->writeln($prefix.$this->color($key, self::CYAN));
                $this->tree($value, $level + 1);
            } else {
                $this->writeln($prefix.$key.': '.$this->color((string) $value, self::GREEN));
            }
        }
    }

    /**
     * Get or create the InteractiveInput instance
     */
    protected function getInteractiveInput(): InteractiveInput
    {
        if ($this->interactiveInput === null) {
            $this->interactiveInput = new InteractiveInput($this);
        }

        return $this->interactiveInput;
    }

    /**
     * Ask for confirmation (yes/no)
     */
    public function confirm(
        string $question,
        bool $default = false,
        ?string $yesText = null,
        ?string $noText = null
    ): bool {
        return $this->getInteractiveInput()->confirm($question, $default, $yesText, $noText);
    }

    /**
     * Ask for a single choice from a list
     */
    public function choice(
        string $question,
        array $choices,
        $default = null,
        int $maxAttempts = 3
    ): string {
        return $this->getInteractiveInput()->choice($question, $choices, $default, $maxAttempts);
    }

    /**
     * Ask for multiple choices from a list
     */
    public function multiChoice(
        string $question,
        array $choices,
        array $defaults = [],
        int $maxAttempts = 3
    ): array {
        return $this->getInteractiveInput()->multiChoice($question, $choices, $defaults, $maxAttempts);
    }

    /**
     * Ask for text input
     */
    public function ask(string $question, ?string $default = null): string
    {
        return $this->getInteractiveInput()->ask($question, $default);
    }

    /**
     * Ask for text input with validation
     */
    public function askValid(
        string $question,
        callable $validator,
        string $error = 'Invalid input. Please try again.',
        ?string $default = null,
        int $maxAttempts = 3
    ): string {
        return $this->getInteractiveInput()->askValid($question, $validator, $error, $default, $maxAttempts);
    }

    /**
     * Ask for hidden input (e.g., passwords)
     */
    public function askHidden(string $question): string
    {
        return $this->getInteractiveInput()->askHidden($question);
    }

    /**
     * Set interactive mode
     */
    public function setInteractive(bool $interactive): self
    {
        $this->getInteractiveInput()->setInteractive($interactive);

        return $this;
    }

    /**
     * Check if interactive mode is enabled
     */
    public function isInteractive(): bool
    {
        return $this->getInteractiveInput()->isInteractiveMode();
    }
}
