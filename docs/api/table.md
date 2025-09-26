# Table API Reference

The `Table` class provides advanced table formatting capabilities with multiple border styles, column alignment, and cell formatting options.

## Class: `Yalla\Output\Table`

### Constructor

```php
public function __construct(Output $output, array $options = [])
```

Creates a new table instance.

**Parameters:**
- `$output` (Output) - The output instance for rendering
- `$options` (array) - Table configuration options

**Options:**
- `borders` (string) - Border style constant (default: `BORDER_UNICODE`)
- `colors` (bool) - Enable colored output (default: `true`)
- `max_width` (int) - Maximum table width (default: `120`)
- `padding` (int) - Cell padding (default: `1`)
- `alignment` (array) - Column alignment configuration
- `header_color` (string) - Header text color (default: `Output::BOLD`)
- `row_separator` (bool) - Add separators between rows (default: `false`)
- `compact` (bool) - Compact mode (default: `false`)
- `show_index` (bool) - Show row indices (default: `false`)
- `index_name` (string) - Index column name (default: `'#'`)

### Border Style Constants

```php
const BORDER_NONE = 'none';        // No borders
const BORDER_ASCII = 'ascii';      // ASCII borders (+---)
const BORDER_UNICODE = 'unicode';  // Unicode borders (┌─┐)
const BORDER_COMPACT = 'compact';  // Minimal borders
const BORDER_MARKDOWN = 'markdown'; // Markdown format
const BORDER_DOUBLE = 'double';    // Double line borders (╔═╗)
const BORDER_ROUNDED = 'rounded';  // Rounded corners (╭─╮)
```

### Alignment Constants

```php
const ALIGN_LEFT = 'left';     // Left alignment
const ALIGN_CENTER = 'center'; // Center alignment
const ALIGN_RIGHT = 'right';   // Right alignment
```

## Methods

### `setHeaders(array $headers): self`

Set table headers.

```php
$table->setHeaders(['Name', 'Age', 'City']);
```

**Parameters:**
- `$headers` (array) - Array of header strings

**Returns:** Table instance for chaining

---

### `setRows(array $rows): self`

Set all table rows, replacing existing data.

```php
$table->setRows([
    ['John', '30', 'New York'],
    ['Jane', '25', 'London']
]);
```

**Parameters:**
- `$rows` (array) - Array of row arrays

**Returns:** Table instance for chaining

---

### `addRow(array $row, ?int $index = null): self`

Add a single row to the table.

```php
$table->addRow(['Bob', '35', 'Paris']);
$table->addRow(['Alice', '28', 'Tokyo'], 1); // With custom index
```

**Parameters:**
- `$row` (array) - Row data
- `$index` (int|null) - Optional custom index for row numbering

**Returns:** Table instance for chaining

---

### `setCellFormatter(int $column, callable $formatter): self`

Set a custom formatter for a specific column.

```php
$table->setCellFormatter(1, function($value) {
    return is_numeric($value) ? number_format($value) : $value;
});
```

**Parameters:**
- `$column` (int) - Zero-based column index
- `$formatter` (callable) - Formatter function `function($value): string`

**Returns:** Table instance for chaining

---

### `sortBy(int $column, string $direction = 'asc'): self`

Sort table rows by a column.

```php
$table->sortBy(1);           // Sort by column 1 ascending
$table->sortBy(2, 'desc');   // Sort by column 2 descending
```

**Parameters:**
- `$column` (int) - Zero-based column index
- `$direction` (string) - Sort direction: `'asc'` or `'desc'`

**Returns:** Table instance for chaining

---

### `filter(callable $callback): self`

Filter table rows based on a condition.

```php
$table->filter(function($row) {
    return $row[1] > 25; // Keep rows where column 1 > 25
});
```

**Parameters:**
- `$callback` (callable) - Filter function `function($row): bool`

**Returns:** Table instance for chaining

---

### `render(): void`

Render the table to output.

```php
$table->render();
```

---

### `getRowCount(): int`

Get the number of rows in the table.

```php
$count = $table->getRowCount();
```

**Returns:** Number of rows

---

### `getColumnCount(): int`

Get the number of columns in the table.

```php
$count = $table->getColumnCount();
```

**Returns:** Number of columns

---

### `clear(): self`

Clear all rows from the table (keeps headers).

```php
$table->clear();
```

**Returns:** Table instance for chaining

---

### `__clone()`

Support for cloning tables with deep copy of row data.

```php
$newTable = clone $existingTable;
```

## Usage Examples

### Basic Table

```php
use Yalla\Output\Output;
use Yalla\Output\Table;

$output = new Output();
$table = new Table($output);

$table->setHeaders(['Name', 'Score', 'Grade'])
      ->addRow(['Alice', '95', 'A'])
      ->addRow(['Bob', '87', 'B'])
      ->render();
```

### Advanced Configuration

```php
$table = new Table($output, [
    'borders' => Table::BORDER_DOUBLE,
    'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_RIGHT, Table::ALIGN_CENTER],
    'max_width' => 100,
    'show_index' => true,
    'row_separator' => true
]);
```

### Cell Formatting

```php
// Format currency in column 2
$table->setCellFormatter(2, function($value) {
    return '$' . number_format((float)$value, 2);
});

// Status formatting with colors
$table->setCellFormatter(3, function($value) use ($output) {
    return match($value) {
        'active' => $output->color('✅ Active', Output::GREEN),
        'pending' => $output->color('⏳ Pending', Output::YELLOW),
        'error' => $output->color('❌ Error', Output::RED),
        default => $value
    };
});
```

### Sorting and Filtering

```php
$table->setRows($data)
      ->sortBy(1, 'desc')              // Sort by score descending
      ->filter(fn($row) => $row[1] >= 80) // Keep high scores only
      ->render();
```

### Markdown Output

```php
$table = new Table($output, [
    'borders' => Table::BORDER_MARKDOWN,
    'alignment' => [Table::ALIGN_LEFT, Table::ALIGN_CENTER, Table::ALIGN_RIGHT]
]);

$table->setHeaders(['Feature', 'Status', 'Priority'])
      ->addRow(['Authentication', 'Complete', 'High'])
      ->addRow(['Database', 'In Progress', 'Medium'])
      ->render();
```

Output:
```markdown
| Feature        | Status      | Priority |
|----------------|:-----------:|---------:|
| Authentication |  Complete   |     High |
| Database       | In Progress |   Medium |
```

## See Also

- [MigrationTable API](/api/migration-table) - Specialized table for database migrations
- [Output API](/api/output) - Main output formatting class
- [Table Formatting Guide](/guide/tables) - Complete guide with examples