<?php

declare(strict_types=1);

namespace Yalla\Output;

class Table
{
    protected array $headers = [];

    protected array $rows = [];

    protected array $columnWidths = [];

    protected array $options = [];

    protected Output $output;

    protected array $cellFormatters = [];

    // Border styles
    const BORDER_NONE = 'none';

    const BORDER_ASCII = 'ascii';

    const BORDER_UNICODE = 'unicode';

    const BORDER_COMPACT = 'compact';

    const BORDER_MARKDOWN = 'markdown';

    const BORDER_DOUBLE = 'double';

    const BORDER_ROUNDED = 'rounded';

    // Alignment options
    const ALIGN_LEFT = 'left';

    const ALIGN_CENTER = 'center';

    const ALIGN_RIGHT = 'right';

    private array $borderChars = [
        'ascii' => [
            'top' => ['+', '-', '+', '+'],
            'mid' => ['|', '-', '+', '|'],
            'row' => ['|', ' ', '|', '|'],
            'bottom' => ['+', '-', '+', '+'],
        ],
        'unicode' => [
            'top' => ['┌', '─', '┬', '┐'],
            'mid' => ['├', '─', '┼', '┤'],
            'row' => ['│', ' ', '│', '│'],
            'bottom' => ['└', '─', '┴', '┘'],
        ],
        'double' => [
            'top' => ['╔', '═', '╦', '╗'],
            'mid' => ['╠', '═', '╬', '╣'],
            'row' => ['║', ' ', '║', '║'],
            'bottom' => ['╚', '═', '╩', '╝'],
        ],
        'rounded' => [
            'top' => ['╭', '─', '┬', '╮'],
            'mid' => ['├', '─', '┼', '┤'],
            'row' => ['│', ' ', '│', '│'],
            'bottom' => ['╰', '─', '┴', '╯'],
        ],
        'compact' => [
            'top' => ['', '', '', ''],
            'mid' => ['', '-', ' ', ''],
            'row' => ['', ' ', ' ', ''],
            'bottom' => ['', '', '', ''],
        ],
        'markdown' => [
            'top' => ['', '', '', ''],
            'mid' => ['|', '-', '|', '|'],
            'row' => ['|', ' ', '|', '|'],
            'bottom' => ['', '', '', ''],
        ],
        'none' => [
            'top' => ['', '', '', ''],
            'mid' => ['', '', '', ''],
            'row' => ['', ' ', '  ', ''],
            'bottom' => ['', '', '', ''],
        ],
    ];

    public function __construct(Output $output, array $options = [])
    {
        $this->output = $output;
        $this->options = array_merge([
            'borders' => self::BORDER_UNICODE,
            'colors' => true,
            'max_width' => 120,
            'padding' => 1,
            'alignment' => [],
            'header_color' => Output::BOLD,
            'row_separator' => false,
            'compact' => false,
            'show_index' => false,
            'index_name' => '#',
        ], $options);
    }

    public function __clone()
    {
        // Deep clone the rows array to prevent modifications from affecting the original
        $this->rows = array_map(function ($row) {
            return is_array($row) ? array_values($row) : $row;
        }, $this->rows);
    }

    public function setHeaders(array $headers): self
    {
        if ($this->options['show_index']) {
            array_unshift($headers, $this->options['index_name']);
        }
        $this->headers = $headers;

        return $this;
    }

    public function setRows(array $rows): self
    {
        $this->rows = [];
        foreach ($rows as $index => $row) {
            $this->addRow($row, $index);
        }

        return $this;
    }

    public function addRow(array $row, ?int $index = null): self
    {
        if ($this->options['show_index']) {
            $displayIndex = $index ?? count($this->rows) + 1;
            array_unshift($row, $displayIndex);
        }
        $this->rows[] = $row;

        return $this;
    }

    public function setCellFormatter(int $column, callable $formatter): self
    {
        $this->cellFormatters[$column] = $formatter;

        return $this;
    }

    public function sortBy(int $column, string $direction = 'asc'): self
    {
        usort($this->rows, function ($a, $b) use ($column, $direction) {
            $aVal = $a[$column] ?? '';
            $bVal = $b[$column] ?? '';

            // Handle numeric comparison
            if (is_numeric($aVal) && is_numeric($bVal)) {
                $result = $aVal <=> $bVal;
            } else {
                $result = strcasecmp((string) $aVal, (string) $bVal);
            }

            return $direction === 'desc' ? -$result : $result;
        });

        return $this;
    }

    public function filter(callable $callback): self
    {
        $this->rows = array_values(array_filter($this->rows, $callback));

        return $this;
    }

    public function render(): void
    {
        if (empty($this->headers) && empty($this->rows)) {
            return;
        }

        $this->calculateColumnWidths();
        $this->renderTable();
    }

    private function calculateColumnWidths(): void
    {
        // Reset column widths
        $this->columnWidths = [];

        // Calculate base widths from headers (without formatting)
        foreach ($this->headers as $i => $header) {
            $this->columnWidths[$i] = $this->getStringWidth($this->formatCell($header, null));
        }

        // Calculate from rows (with formatting)
        foreach ($this->rows as $row) {
            foreach ($row as $i => $cell) {
                $width = $this->getStringWidth($this->formatCell($cell, $i));
                $this->columnWidths[$i] = max($this->columnWidths[$i] ?? 0, $width);
            }
        }

        // Apply max width constraint if needed
        $this->applyMaxWidth();
    }

    private function getStringWidth(string $text): int
    {
        // Remove ANSI color codes for accurate width calculation
        $text = preg_replace('/\033\[[0-9;]*m/', '', $text);

        // Base width from mb_strlen
        $width = mb_strlen($text);

        // Count emojis and add extra width for each
        // Common emoji ranges that display wider than 1 char
        preg_match_all('/[\x{2700}-\x{27BF}]|[\x{2600}-\x{26FF}]|[\x{2300}-\x{23FF}]|[\x{1F000}-\x{1F9FF}]|[\x{2190}-\x{21FF}]/u', $text, $emojis);
        $emojiCount = count($emojis[0]);

        // Add 1 extra width unit per emoji to account for their visual width
        $width += $emojiCount;

        return $width;
    }

    private function formatCell($value, ?int $column = null): string
    {
        // Apply custom formatter if available
        if ($column !== null && isset($this->cellFormatters[$column])) {
            $value = ($this->cellFormatters[$column])($value);
        }

        if ($value === null) {
            return ''; // @codeCoverageIgnore
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    private function applyMaxWidth(): void
    {
        $borderOverhead = $this->options['borders'] === self::BORDER_NONE ? 0 : 1;
        $totalWidth = array_sum($this->columnWidths)
            + (count($this->columnWidths) * ($this->options['padding'] * 2 + 1))
            + $borderOverhead;

        if ($totalWidth <= $this->options['max_width']) {
            return;
        }

        // Find the longest columns and reduce them proportionally
        $excess = $totalWidth - $this->options['max_width'];
        $sortedWidths = $this->columnWidths;
        arsort($sortedWidths);

        foreach ($sortedWidths as $index => $width) {
            if ($excess <= 0) {
                // @codeCoverageIgnoreStart
                break;
                // @codeCoverageIgnoreEnd
            }

            $reduction = min($excess, (int) ($width * 0.3)); // Reduce by up to 30%
            $this->columnWidths[$index] = max(3, $width - $reduction);
            $excess -= $reduction;
        }
    }

    private function renderTable(): void
    {
        $borderStyle = $this->options['borders'];

        if ($borderStyle !== self::BORDER_NONE) {
            $this->renderBorder('top');
        }

        if (! empty($this->headers)) {
            $this->renderRow($this->headers, true);

            if ($borderStyle === self::BORDER_MARKDOWN) {
                // @codeCoverageIgnoreStart
                $this->renderMarkdownSeparator();
                // @codeCoverageIgnoreEnd
            } elseif ($borderStyle !== self::BORDER_NONE && $borderStyle !== self::BORDER_COMPACT) {
                $this->renderBorder('mid');
            }
        }

        foreach ($this->rows as $index => $row) {
            $this->renderRow($row, false);

            if ($this->options['row_separator'] && $index < count($this->rows) - 1) {
                $this->renderBorder('mid');
            }
        }

        if ($borderStyle !== self::BORDER_NONE &&
            $borderStyle !== self::BORDER_MARKDOWN &&
            $borderStyle !== self::BORDER_COMPACT) {
            $this->renderBorder('bottom');
        }
    }

    private function renderBorder(string $type): void
    {
        $chars = $this->borderChars[$this->options['borders']][$type];

        if (empty($chars[0]) && empty($chars[1]) && empty($chars[2]) && empty($chars[3])) {
            return; // Skip empty borders
        }

        $line = $chars[0];
        foreach ($this->columnWidths as $i => $width) {
            $padding = $this->options['padding'] * 2;
            $line .= str_repeat($chars[1], $width + $padding);

            if ($i < count($this->columnWidths) - 1) {
                $line .= $chars[2];
            }
        }
        $line .= $chars[3];

        if (! empty(trim($line))) {
            $this->output->writeln($line);
        }
    }

    private function renderMarkdownSeparator(): void
    {
        $line = '|';
        foreach ($this->columnWidths as $i => $width) {
            $padding = $this->options['padding'] * 2;
            $alignment = $this->options['alignment'][$i] ?? self::ALIGN_LEFT;

            $separator = str_repeat('-', $width + $padding);

            // Add alignment indicators for markdown
            if ($alignment === self::ALIGN_CENTER) {
                $separator = ':'.substr($separator, 2).':';
            } elseif ($alignment === self::ALIGN_RIGHT) {
                $separator = substr($separator, 0, -1).':';
            }

            $line .= $separator.'|';
        }

        $this->output->writeln($line);
    }

    private function renderRow(array $row, bool $isHeader): void
    {
        $chars = $this->borderChars[$this->options['borders']]['row'];
        $line = $chars[0];

        foreach ($this->columnWidths as $i => $width) {
            // Don't apply formatters to headers
            if ($isHeader) {
                $cell = $this->formatCell($row[$i] ?? '', null);
            } else {
                $cell = $this->formatCell($row[$i] ?? '', $i);
            }
            $alignment = $this->options['alignment'][$i] ?? self::ALIGN_LEFT;

            // Apply alignment
            $aligned = $this->alignText($cell, $width, $alignment);

            // Apply padding
            $padding = str_repeat(' ', $this->options['padding']);
            $cellContent = $padding.$aligned.$padding;

            // Apply colors for header
            if ($isHeader && $this->options['colors'] && ! empty($this->options['header_color'])) {
                $cellContent = $this->output->color($cellContent, $this->options['header_color']);
            }

            $line .= $cellContent;

            if ($i < count($this->columnWidths) - 1) {
                $line .= $chars[2];
            }
        }

        $line .= $chars[3];
        $this->output->writeln(rtrim($line));
    }

    private function alignText(string $text, int $width, string $alignment): string
    {
        $textWidth = $this->getStringWidth($text);

        if ($textWidth > $width) {
            // Truncate with ellipsis if text is too long
            // We need to be careful with emojis when truncating
            $truncated = $this->truncateString($text, $width - 3);

            return $truncated.'...';
        }

        $spaces = $width - $textWidth;

        return match ($alignment) {
            self::ALIGN_CENTER => str_repeat(' ', intval($spaces / 2)).$text.str_repeat(' ', $spaces - intval($spaces / 2)),
            self::ALIGN_RIGHT => str_repeat(' ', $spaces).$text,
            default => $text.str_repeat(' ', $spaces),
        };
    }

    private function truncateString(string $text, int $maxWidth): string
    {
        $currentWidth = 0;
        $result = '';
        $chars = mb_str_split($text);

        foreach ($chars as $char) {
            $charWidth = $this->getCharWidth($char);
            if ($currentWidth + $charWidth > $maxWidth) {
                break;
            }
            $result .= $char;
            $currentWidth += $charWidth;
        }

        return $result;
    }

    private function getCharWidth(string $char): int
    {
        // Simplified: just return 1 for all characters
        // The buffer is handled in getStringWidth
        return 1;
    }

    public function getRowCount(): int
    {
        return count($this->rows);
    }

    public function getColumnCount(): int
    {
        return count($this->headers);
    }

    public function clear(): self
    {
        $this->rows = [];

        return $this;
    }
}
