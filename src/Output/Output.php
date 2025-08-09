<?php

declare(strict_types=1);

namespace Yalla\Output;

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

    public function __construct()
    {
        $this->supportsColors = $this->hasColorSupport();
    }

    private function hasColorSupport(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
        }

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

    public function table(array $headers, array $rows): void
    {
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
}
