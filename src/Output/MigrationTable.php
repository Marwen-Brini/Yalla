<?php

declare(strict_types=1);

namespace Yalla\Output;

class MigrationTable extends Table
{
    private array $statusColors = [
        'migrated' => Output::GREEN,
        'pending' => Output::YELLOW,
        'error' => Output::RED,
        'running' => Output::CYAN,
        'rolled_back' => Output::MAGENTA,
    ];

    public function __construct(Output $output, array $options = [])
    {
        $defaultOptions = [
            'borders' => self::BORDER_UNICODE,
            'colors' => true,
            'alignment' => ['center', 'left', 'center', 'left', 'right'],
            'header_color' => Output::CYAN,
            'show_index' => true,
            'index_name' => 'ID',
        ];

        parent::__construct($output, array_merge($defaultOptions, $options));

        $this->setHeaders(['Migration', 'Batch', 'Status', 'Executed At']);

        // Set up status column formatter
        $this->setCellFormatter($this->options['show_index'] ? 3 : 2, function ($status) {
            return $this->formatStatus((string) $status);
        });
    }

    public function addMigration(
        string $name,
        ?int $batch = null,
        string $status = 'pending',
        ?string $timestamp = null
    ): self {
        return $this->addRow([
            $name,
            $batch ?? '-',
            $status,
            $timestamp ?? '-',
        ]);
    }

    public function addPendingMigration(string $name): self
    {
        return $this->addMigration($name, null, 'pending', null);
    }

    public function addMigratedMigration(
        string $name,
        int $batch,
        string $timestamp
    ): self {
        return $this->addMigration($name, $batch, 'migrated', $timestamp);
    }

    public function addErrorMigration(
        string $name,
        ?int $batch = null,
        string $errorMessage = ''
    ): self {
        $status = 'error';
        if (! empty($errorMessage)) {
            $status .= ': '.$errorMessage;
        }

        return $this->addMigration($name, $batch, $status, null);
    }

    private function formatStatus(string $status): string
    {
        if (! $this->options['colors']) {
            return $this->getStatusIcon($status).' '.ucfirst($status);
        }

        $statusLower = strtolower($status);
        $baseStatus = explode(':', $statusLower)[0]; // Handle "error: message" format

        $color = $this->statusColors[$baseStatus] ?? Output::WHITE;
        $icon = $this->getStatusIcon($baseStatus);
        $text = ucfirst($status);

        return $this->output->color($icon.' '.$text, $color);
    }

    private function getStatusIcon(string $status): string
    {
        return match (strtolower($status)) {
            'migrated' => '✅',
            'pending' => '⏳',
            'error' => '❌',
            'running' => '🔄',
            'rolled_back' => '↩️',
            default => '•'
        };
    }

    public function renderSummary(): void
    {
        $counts = $this->getCounts();

        if (empty($counts)) {
            return;
        }

        $this->output->writeln('');
        $this->output->writeln('Migration Summary:');

        $summaryTable = new Table($this->output, [
            'borders' => self::BORDER_COMPACT,
            'alignment' => ['left', 'right'],
        ]);

        $summaryTable->setHeaders(['Status', 'Count']);

        foreach ($counts as $status => $count) {
            $formattedStatus = $this->formatStatus($status);
            $summaryTable->addRow([$formattedStatus, $count]);
        }

        $summaryTable->addRow(['Total', array_sum($counts)]);
        $summaryTable->render();
    }

    private function getCounts(): array
    {
        $counts = [];
        $statusColumn = $this->options['show_index'] ? 3 : 2;

        foreach ($this->rows as $row) {
            $statusValue = (string) ($row[$statusColumn] ?? 'unknown');
            $status = strtolower(explode(':', $statusValue)[0]);
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    public function filterByStatus(string $status): self
    {
        $statusColumn = $this->options['show_index'] ? 3 : 2;

        return $this->filter(function ($row) use ($status, $statusColumn) {
            $statusValue = (string) ($row[$statusColumn] ?? '');
            $rowStatus = strtolower(explode(':', $statusValue)[0]);

            return $rowStatus === strtolower($status);
        });
    }

    public function filterByBatch(int $batch): self
    {
        $batchColumn = $this->options['show_index'] ? 2 : 1;

        return $this->filter(function ($row) use ($batch, $batchColumn) {
            return $row[$batchColumn] == $batch;
        });
    }
}
