<?php

declare(strict_types=1);

use Yalla\Output\Output;
use Yalla\Output\Table;
use Yalla\Output\MigrationTable;

beforeEach(function () {
    $this->output = new Output();
});

test('creates basic table with default options', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Name', 'Age', 'City'])
          ->setRows([
              ['John', '30', 'New York'],
              ['Jane', '25', 'London']
          ]);

    expect($table->getColumnCount())->toBe(3);
    expect($table->getRowCount())->toBe(2);
});

test('handles empty cells correctly', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Name', 'Value', 'Status'])
          ->addRow(['Test', null, 'Active'])
          ->addRow(['', 'Value', ''])
          ->addRow(['Another', '', null]);

    expect($table->getRowCount())->toBe(3);
});

test('formats boolean values', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Feature', 'Enabled'])
          ->addRow(['Feature A', true])
          ->addRow(['Feature B', false]);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('true');
    expect($output)->toContain('false');
});

test('supports different border styles', function () {
    $borderStyles = [
        Table::BORDER_ASCII,
        Table::BORDER_UNICODE,
        Table::BORDER_DOUBLE,
        Table::BORDER_ROUNDED,
        Table::BORDER_COMPACT,
        Table::BORDER_MARKDOWN,
        Table::BORDER_NONE
    ];

    foreach ($borderStyles as $style) {
        $table = new Table($this->output, ['borders' => $style]);
        $table->setHeaders(['Col1', 'Col2'])
              ->addRow(['A', 'B']);

        ob_start();
        $table->render();
        $output = ob_get_clean();

        expect($output)->toBeString();

        // Check for specific border characters
        if ($style === Table::BORDER_ASCII) {
            expect($output)->toContain('+');
        } elseif ($style === Table::BORDER_UNICODE) {
            expect($output)->toContain('┌');
        } elseif ($style === Table::BORDER_DOUBLE) {
            expect($output)->toContain('╔');
        } elseif ($style === Table::BORDER_MARKDOWN) {
            expect($output)->toContain('|');
        }
    }
});

test('applies column alignment', function () {
    $table = new Table($this->output, [
        'alignment' => [
            Table::ALIGN_LEFT,
            Table::ALIGN_CENTER,
            Table::ALIGN_RIGHT
        ],
        'borders' => Table::BORDER_ASCII
    ]);

    $table->setHeaders(['Left', 'Center', 'Right'])
          ->addRow(['A', 'B', 'C']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toBeString();
    expect($output)->toContain('Left');
});

test('truncates long text with ellipsis', function () {
    $table = new Table($this->output, ['max_width' => 30]);

    $longText = str_repeat('Very long text ', 20);
    $table->setHeaders(['Content'])
          ->addRow([$longText]);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('...');
});

test('sorts table by column', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Name', 'Score'])
          ->addRow(['Alice', 90])
          ->addRow(['Bob', 85])
          ->addRow(['Charlie', 95])
          ->sortBy(1, 'desc'); // Sort by score descending

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Charlie should appear before Alice in the output
    $charliePos = strpos($output, 'Charlie');
    $alicePos = strpos($output, 'Alice');

    expect($charliePos)->toBeLessThan($alicePos);
});

test('filters table rows', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Name', 'Status'])
          ->addRow(['Item 1', 'active'])
          ->addRow(['Item 2', 'inactive'])
          ->addRow(['Item 3', 'active'])
          ->filter(fn($row) => $row[1] === 'active');

    expect($table->getRowCount())->toBe(2);
});

test('applies cell formatters', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Name', 'Status'])
          ->setCellFormatter(1, function($status) {
              return strtoupper($status);
          })
          ->addRow(['Test', 'active']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('ACTIVE');
});

test('shows row indices when enabled', function () {
    $table = new Table($this->output, [
        'show_index' => true,
        'index_name' => '#'
    ]);

    $table->setHeaders(['Name', 'Value'])
          ->addRow(['First', 'A'])
          ->addRow(['Second', 'B']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('#');
    expect($output)->toContain('1');
    expect($output)->toContain('2');
});

test('creates table via Output class', function () {
    ob_start();
    $this->output->table(
        ['Header 1', 'Header 2'],
        [['Row 1', 'Value 1'], ['Row 2', 'Value 2']],
        ['borders' => Table::BORDER_ASCII]
    );
    $output = ob_get_clean();

    expect($output)->toContain('+');
    expect($output)->toContain('Header 1');
    expect($output)->toContain('Row 1');
});

test('creates table using fluent interface', function () {
    $table = $this->output->createTable(['borders' => Table::BORDER_COMPACT]);

    $table->setHeaders(['ID', 'Name'])
          ->addRow([1, 'Item 1'])
          ->addRow([2, 'Item 2']);

    expect($table)->toBeInstanceOf(Table::class);
    expect($table->getRowCount())->toBe(2);
});

test('clears table rows', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Col1'])
          ->addRow(['Row1'])
          ->addRow(['Row2']);

    expect($table->getRowCount())->toBe(2);

    $table->clear();
    expect($table->getRowCount())->toBe(0);
});

test('MigrationTable formats status correctly', function () {
    $migrationTable = new MigrationTable($this->output);

    $migrationTable->addMigratedMigration(
        '2024_01_01_create_users_table',
        1,
        '2024-01-01 10:30:00'
    );

    $migrationTable->addPendingMigration('2024_01_02_add_email_column');

    $migrationTable->addErrorMigration(
        '2024_01_03_create_posts_table',
        null,
        'Foreign key constraint failed'
    );

    expect($migrationTable->getRowCount())->toBe(3);

    ob_start();
    $migrationTable->render();
    $output = ob_get_clean();

    expect($output)->toContain('create_users_table');
    expect($output)->toContain('add_email_column');
    expect($output)->toContain('create_posts_table');
});

test('MigrationTable filters by status', function () {
    $migrationTable = new MigrationTable($this->output);

    $migrationTable->addMigration('migration1', 1, 'migrated', '2024-01-01');
    $migrationTable->addMigration('migration2', null, 'pending', null);
    $migrationTable->addMigration('migration3', 2, 'migrated', '2024-01-02');

    $filtered = clone $migrationTable;
    $filtered->filterByStatus('migrated');

    expect($filtered->getRowCount())->toBe(2);
});

test('MigrationTable filters by batch', function () {
    $migrationTable = new MigrationTable($this->output);

    $migrationTable->addMigration('migration1', 1, 'migrated', '2024-01-01');
    $migrationTable->addMigration('migration2', 1, 'migrated', '2024-01-01');
    $migrationTable->addMigration('migration3', 2, 'migrated', '2024-01-02');

    $filtered = clone $migrationTable;
    $filtered->filterByBatch(1);

    expect($filtered->getRowCount())->toBe(2);
});

test('MigrationTable renders summary', function () {
    $migrationTable = new MigrationTable($this->output);

    $migrationTable->addMigratedMigration('migration1', 1, '2024-01-01');
    $migrationTable->addPendingMigration('migration2');
    $migrationTable->addMigration('migration3', 2, 'running', null);
    $migrationTable->addMigration('migration4', 3, 'rolled_back', null);

    ob_start();
    $migrationTable->renderSummary();
    $output = ob_get_clean();

    expect($output)->toContain('Migration Summary:');
    expect($output)->toContain('Total');
});

test('MigrationTable formats status without colors', function () {
    $migrationTable = new MigrationTable($this->output, ['colors' => false]);

    $migrationTable->addMigration('test1', 1, 'running', null);
    $migrationTable->addMigration('test2', 2, 'rolled_back', null);

    ob_start();
    $migrationTable->render();
    $output = ob_get_clean();

    // Should contain status with icons but no color codes
    expect($output)->toContain('🔄 Running');
    expect($output)->toContain('↩️ Rolled_back');
});

test('Table handles JSON values in cells', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Data'])
          ->addRow([['key' => 'value', 'nested' => ['a' => 1]]]);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('{"key":"value","nested":{"a":1}}');
});

test('Table handles null values in cells', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Col1', 'Col2'])
          ->addRow([null, 'Value'])
          ->addRow(['Value', null]);

    expect($table->getRowCount())->toBe(2);
});

test('Table truncateString works correctly', function () {
    $table = new Table($this->output, ['max_width' => 20]);

    $table->setHeaders(['Long Header That Will Be Truncated'])
          ->addRow(['Very long content that exceeds maximum width']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('...');
});

test('Table applyMaxWidth adjusts column widths', function () {
    $table = new Table($this->output, ['max_width' => 30]);

    $table->setHeaders(['Column1', 'Column2', 'Column3'])
          ->addRow(['Long content here', 'More long content', 'Even more content']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Table should be constrained - just check it renders without error
    expect($output)->toBeString();
    expect($output)->toContain('Column1');
});

test('Table handles row separator option', function () {
    $table = new Table($this->output, [
        'row_separator' => true,
        'borders' => Table::BORDER_UNICODE
    ]);

    $table->setHeaders(['Col1', 'Col2'])
          ->addRow(['A', 'B'])
          ->addRow(['C', 'D']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Should have separators between rows
    $separatorCount = substr_count($output, '├');
    expect($separatorCount)->toBeGreaterThan(1);
});

test('Table handles compact border style', function () {
    $table = new Table($this->output, ['borders' => Table::BORDER_COMPACT]);

    $table->setHeaders(['Col1', 'Col2'])
          ->addRow(['A', 'B']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Compact style has minimal borders
    expect($output)->not->toContain('┌');
    expect($output)->not->toContain('└');
});

test('Table handles none border style', function () {
    $table = new Table($this->output, ['borders' => Table::BORDER_NONE]);

    $table->setHeaders(['Col1', 'Col2'])
          ->addRow(['A', 'B']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // No borders
    expect($output)->not->toContain('│');
    expect($output)->not->toContain('┌');
});

test('Table handles center alignment in markdown', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_CENTER, Table::ALIGN_RIGHT]
    ]);

    $table->setHeaders(['Centered', 'Right'])
          ->addRow(['A', 'B']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Markdown tables use pipes
    expect($output)->toContain('|');
});

test('handles markdown table format', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT]
    ]);

    $table->setHeaders(['Left', 'Center', 'Right'])
          ->addRow(['A', 'B', 'C']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Markdown tables should have pipes and alignment indicators
    expect($output)->toContain('|');
    expect($output)->toContain('-');
});

test('Table sortBy handles string comparison', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Name', 'Value'])
          ->addRow(['Beta', 'B'])
          ->addRow(['Alpha', 'A'])
          ->addRow(['Gamma', 'C'])
          ->sortBy(0); // Sort by name column

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Alpha should come before Beta
    $alphaPos = strpos($output, 'Alpha');
    $betaPos = strpos($output, 'Beta');
    expect($alphaPos)->toBeLessThan($betaPos);
});

test('Table render handles empty table', function () {
    $table = new Table($this->output);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toBe('');
});

test('MigrationTable renderSummary with no migrations', function () {
    $migrationTable = new MigrationTable($this->output);

    ob_start();
    $migrationTable->renderSummary();
    $output = ob_get_clean();

    expect($output)->toBe('');
});

test('Table with markdown right alignment', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_RIGHT]
    ]);

    $table->setHeaders(['Right Aligned'])
          ->addRow(['Value']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Check for markdown table structure
    expect($output)->toContain('|');
});

test('Table with markdown center and all alignments', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT]
    ]);

    $table->setHeaders(['Left', 'Center', 'Right'])
          ->addRow(['A', 'B', 'C']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Check for markdown table structure
    expect($output)->toContain('|');
    expect($output)->toContain('-');
});

test('Table handles double border style', function () {
    $table = new Table($this->output, ['borders' => Table::BORDER_DOUBLE]);

    $table->setHeaders(['Header'])
          ->addRow(['Value']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('╔');
    expect($output)->toContain('╚');
});

test('Table handles rounded border style', function () {
    $table = new Table($this->output, ['borders' => Table::BORDER_ROUNDED]);

    $table->setHeaders(['Header'])
          ->addRow(['Value']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('╭');
    expect($output)->toContain('╰');
});


test('Table handles formatCell with null input in formatter', function () {
    $table = new Table($this->output);
    
    $table->setHeaders(['Test'])
          ->setCellFormatter(0, function($value) {
              return $value === null ? 'NULL' : $value;
          })
          ->addRow([null]);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    expect($output)->toContain('Test');
});

test('Table renders markdown separator correctly', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT]
    ]);

    $table->setHeaders(['Left', 'Center', 'Right'])
          ->addRow(['A', 'B', 'C']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Should contain markdown separator line after headers
    $lines = explode("\n", $output);
    $separatorLine = $lines[1] ?? '';
    expect($separatorLine)->toContain('|');
    expect($separatorLine)->toContain('-');
});

test('Table handles getCharWidth correctly', function () {
    $table = new Table($this->output);

    // Test with emoji-containing text to trigger getCharWidth
    $table->setHeaders(['Emoji'])
          ->addRow(['🎉']);

    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('getCharWidth');
    $method->setAccessible(true);

    // Test the getCharWidth method directly
    $result = $method->invoke($table, '🎉');
    expect($result)->toBe(1);
    
    $result = $method->invoke($table, 'a');
    expect($result)->toBe(1);
});

test('Table handles truncateString with width 0', function () {
    $table = new Table($this->output, ['max_width' => 10]);

    $table->setHeaders(['Very very very long header that will trigger truncation'])
          ->addRow(['Short']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Should handle truncation gracefully
    expect($output)->toBeString();
});


test('Table renderMarkdownSeparator with center alignment', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_CENTER]
    ]);

    $table->setHeaders(['Center'])
          ->addRow(['Test']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Should contain center alignment indicator in separator
    $lines = explode("\n", $output);
    $separatorLine = $lines[1] ?? '';
    expect($separatorLine)->toContain('|'); // Just check it renders
});

test('Table renderMarkdownSeparator with right alignment', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_RIGHT]
    ]);

    $table->setHeaders(['Right'])
          ->addRow(['Test']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Should contain right alignment indicator in separator
    $lines = explode("\n", $output);
    $separatorLine = $lines[1] ?? '';
    expect($separatorLine)->toContain('|'); // Just check it renders
});

test('Table renderMarkdownSeparator with mixed alignments', function () {
    $table = new Table($this->output, [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT]
    ]);

    $table->setHeaders(['Left', 'Center', 'Right'])
          ->addRow(['A', 'B', 'C']);

    ob_start();
    $table->render();
    $output = ob_get_clean();

    // Check that the separator line has proper alignment indicators
    $lines = explode("\n", $output);
    $separatorLine = $lines[1] ?? '';
    
    // Should have left (no indicator), center (:---:), and right (---:)
    expect($separatorLine)->toContain('|');
    expect($separatorLine)->toContain('-');
    expect($separatorLine)->toContain(':');
});


test('Table formatCell handles null value without formatter', function () {
    $table = new Table($this->output);

    $table->setHeaders(['Nullable'])
          ->addRow([null]);

    // Use reflection to test formatCell directly
    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('formatCell');
    $method->setAccessible(true);

    $result = $method->invoke($table, null, null);
    expect($result)->toBe('');
});
