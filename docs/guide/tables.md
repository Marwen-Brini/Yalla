# Table Formatting

Yalla 1.4 introduces a powerful table formatting system with professional-grade features including multiple border styles, emoji support, column alignment, and specialized table types for different use cases.

## Quick Start

```php
use Yalla\Output\Output;

$output = new Output();

// Simple table
$output->table(
    ['Name', 'Age', 'City'],
    [
        ['John', '30', 'New York'],
        ['Jane', '25', 'London']
    ]
);
```

## Advanced Table Creation

For more control, use the `createTable()` method:

```php
use Yalla\Output\Table;

$table = $output->createTable([
    'borders' => Table::BORDER_UNICODE,
    'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT],
    'colors' => true,
    'max_width' => 120
]);

$table->setHeaders(['Migration', 'Batch', 'Status'])
      ->addRow(['2024_01_create_users', '1', '✅ Migrated'])
      ->addRow(['2024_02_create_posts', '2', '⏳ Pending'])
      ->render();
```

## Border Styles

Yalla supports multiple border styles for different output contexts:

### Unicode Borders (Default)
```php
Table::BORDER_UNICODE
```
```
┌──────────────┬───────┬──────────┐
│ Migration    │ Batch │ Status   │
├──────────────┼───────┼──────────┤
│ create_users │   1   │ Migrated │
│ create_posts │   2   │ Pending  │
└──────────────┴───────┴──────────┘
```

### ASCII Borders
```php
Table::BORDER_ASCII
```
```
+--------------+-------+----------+
| Migration    | Batch | Status   |
+--------------+-------+----------+
| create_users |   1   | Migrated |
| create_posts |   2   | Pending  |
+--------------+-------+----------+
```

### Markdown Format
```php
Table::BORDER_MARKDOWN
```
```
| Migration    | Batch | Status   |
|--------------|:-----:|----------|
| create_users |   1   | Migrated |
| create_posts |   2   | Pending  |
```

### Double Line Borders
```php
Table::BORDER_DOUBLE
```
```
╔══════════════╦═══════╦══════════╗
║ Migration    ║ Batch ║ Status   ║
╠══════════════╬═══════╬══════════╣
║ create_users ║   1   ║ Migrated ║
║ create_posts ║   2   ║ Pending  ║
╚══════════════╩═══════╩══════════╝
```

### Rounded Borders
```php
Table::BORDER_ROUNDED
```
```
╭──────────────┬───────┬──────────╮
│ Migration    │ Batch │ Status   │
├──────────────┼───────┼──────────┤
│ create_users │   1   │ Migrated │
│ create_posts │   2   │ Pending  │
╰──────────────┴───────┴──────────╯
```

### Compact Style
```php
Table::BORDER_COMPACT
```
```
Migration      Batch  Status
-------------- ------ ---------
create_users     1    Migrated
create_posts     2    Pending
```

### No Borders
```php
Table::BORDER_NONE
```
```
Migration      Batch  Status
create_users     1    Migrated
create_posts     2    Pending
```

## Column Alignment

Control text alignment for each column:

```php
$table->setOptions([
    'alignment' => [
        Table::ALIGN_LEFT,    // Left align
        Table::ALIGN_CENTER,  // Center align
        Table::ALIGN_RIGHT    // Right align
    ]
]);
```

Alignment affects both content and markdown table separators:

```php
// Markdown with alignment
$table = $output->createTable([
    'borders' => Table::BORDER_MARKDOWN,
    'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT]
]);
```

Output:
```
| Name    | Score | Grade |
|---------|:-----:|------:|
| Alice   |  95   |   A+  |
| Bob     |  87   |    B  |
```

## Cell Formatting

Apply custom formatting to specific columns:

```php
// Format numbers in column 2
$table->setCellFormatter(2, function($value) {
    return is_numeric($value) ? number_format($value) : $value;
});

// Format status with colors
$table->setCellFormatter(3, function($value) use ($output) {
    return match(strtolower($value)) {
        'active' => $output->color($value, Output::GREEN),
        'pending' => $output->color($value, Output::YELLOW),
        'inactive' => $output->color($value, Output::RED),
        default => $value
    };
});
```

## Table Operations

### Sorting

Sort table data by any column:

```php
// Sort by column 1 ascending (default)
$table->sortBy(1);

// Sort by column 2 descending
$table->sortBy(2, 'desc');
```

### Filtering

Filter rows based on conditions:

```php
// Keep only active users
$table->filter(function($row) {
    return $row[2] === 'Active';
});

// Filter by multiple conditions
$table->filter(function($row) {
    return $row[1] > 25 && $row[2] !== 'Inactive';
});
```

### Row Indices

Display row numbers:

```php
$table->setOptions([
    'show_index' => true,
    'index_name' => 'ID'  // Custom index column name
]);
```

## Table Options

Complete list of available options:

```php
$options = [
    'borders' => Table::BORDER_UNICODE,  // Border style
    'colors' => true,                    // Enable colors
    'max_width' => 120,                  // Maximum table width
    'padding' => 1,                      // Cell padding
    'alignment' => [],                   // Column alignments
    'header_color' => Output::BOLD,      // Header text formatting
    'row_separator' => false,            // Add separator between rows
    'compact' => false,                  // Compact mode
    'show_index' => false,               // Show row indices
    'index_name' => '#'                  // Index column name
];

$table = $output->createTable($options);
```

## Emoji and Unicode Support

Yalla properly handles emoji and wide characters in table cells:

```php
$table->setHeaders(['Service', 'Status', 'Health'])
      ->addRow(['Database', '✅ Online', '🟢 Healthy'])
      ->addRow(['Cache', '⏳ Starting', '🟡 Warning'])
      ->addRow(['API', '❌ Offline', '🔴 Critical'])
      ->render();
```

## Fluent Interface

Chain methods for clean, readable code:

```php
$output->createTable(['borders' => Table::BORDER_UNICODE])
       ->setHeaders(['Name', 'Score', 'Grade'])
       ->addRow(['Alice', '95', 'A+'])
       ->addRow(['Bob', '87', 'B'])
       ->sortBy(1, 'desc')
       ->filter(fn($row) => $row[1] >= 90)
       ->render();
```

## Table Cloning

Create variations of existing tables:

```php
$baseTable = $output->createTable(['borders' => Table::BORDER_UNICODE])
                   ->setHeaders(['Name', 'Score']);

// Clone and modify
$gradeATable = clone $baseTable;
$gradeATable->addRow(['Alice', '95'])
           ->addRow(['Charlie', '92'])
           ->render();

$gradeBTable = clone $baseTable;
$gradeBTable->addRow(['Bob', '87'])
           ->addRow(['David', '84'])
           ->render();
```

## Performance Considerations

- Tables automatically adjust column widths based on content
- Large tables are truncated if they exceed `max_width`
- Emoji width calculation is optimized for terminal display
- Use `compact` mode for better performance with large datasets

## Migration Tables

For database migration systems, Yalla provides a specialized `MigrationTable` class:

```php
use Yalla\Output\MigrationTable;

$migrationTable = new MigrationTable($output);
$migrationTable->addMigration('2024_01_create_users', 1, 'migrated', '2024-01-15')
               ->addMigration('2024_02_create_posts', 2, 'pending', null)
               ->addMigration('2024_03_add_indexes', null, 'error: Constraint violation', null)
               ->render();

// Show summary
$migrationTable->renderSummary();
```

See the [MigrationTable API reference](/api/migration-table) for complete details.