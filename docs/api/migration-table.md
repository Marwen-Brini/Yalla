# MigrationTable API Reference

The `MigrationTable` class is a specialized table formatter designed for database migration systems. It extends the base `Table` class with migration-specific functionality and status formatting.

## Class: `Yalla\Output\MigrationTable`

Extends: [`Yalla\Output\Table`](/api/table)

### Constructor

```php
public function __construct(Output $output, array $options = [])
```

Creates a new migration table with predefined headers and migration-optimized settings.

**Parameters:**
- `$output` (Output) - The output instance for rendering
- `$options` (array) - Table configuration options

**Default Headers:**
- `Migration` - Migration file name
- `Batch` - Migration batch number
- `Status` - Migration status with emoji indicators
- `Date` - Migration execution date

## Methods

### `addMigration(string $name, ?int $batch, string $status, ?string $date = null): self`

Add a migration row to the table.

```php
$table->addMigration('2024_01_create_users', 1, 'migrated', '2024-01-15');
$table->addMigration('2024_02_create_posts', 2, 'pending', null);
$table->addMigration('2024_03_add_indexes', null, 'error: Constraint violation', null);
```

**Parameters:**
- `$name` (string) - Migration file name
- `$batch` (int|null) - Batch number (null for unprocessed migrations)
- `$status` (string) - Migration status
- `$date` (string|null) - Execution date (null for pending/failed migrations)

**Returns:** MigrationTable instance for chaining

**Status Values:**
- `'migrated'` - Successfully executed migration (✅)
- `'pending'` - Migration waiting to be executed (⏳)
- `'error'` / `'failed'` - Migration failed (❌)
- `'running'` - Migration currently executing (🔄)
- `'rolled_back'` - Migration was rolled back (↩️)
- Any other string - Displayed with bullet point (•)

---

### `renderSummary(): void`

Render a summary of migration statistics below the table.

```php
$table->renderSummary();
```

**Example Output:**
```
Migration Summary:
├─ Total: 5 migrations
├─ Migrated: 3 migrations
├─ Pending: 1 migration
└─ Failed: 1 migration
```

---

### `filterByStatus(string $status): self`

Filter migrations by status.

```php
$table->filterByStatus('pending');  // Show only pending migrations
$table->filterByStatus('migrated'); // Show only completed migrations
```

**Parameters:**
- `$status` (string) - Status to filter by

**Returns:** MigrationTable instance for chaining

---

### `filterByBatch(?int $batch): self`

Filter migrations by batch number.

```php
$table->filterByBatch(1);    // Show migrations from batch 1
$table->filterByBatch(null); // Show unprocessed migrations
```

**Parameters:**
- `$batch` (int|null) - Batch number to filter by

**Returns:** MigrationTable instance for chaining

---

### `formatStatus(string $status, bool $withColors = true): string`

Format a status string with appropriate emoji and colors.

```php
$formatted = $table->formatStatus('migrated');  // Returns "✅ Migrated"
$formatted = $table->formatStatus('pending');   // Returns "⏳ Pending"
```

**Parameters:**
- `$status` (string) - Raw status string
- `$withColors` (bool) - Whether to apply colors (default: true)

**Returns:** Formatted status string with emoji and colors

## Status Icons and Colors

| Status | Icon | Color | Description |
|--------|------|-------|-------------|
| `migrated` | ✅ | Green | Successfully executed |
| `pending` | ⏳ | Yellow | Waiting to be executed |
| `error` / `failed` | ❌ | Red | Execution failed |
| `running` | 🔄 | Blue | Currently executing |
| `rolled_back` | ↩️ | Magenta | Rolled back |
| Other | • | Default | Custom status |

## Usage Examples

### Basic Migration Table

```php
use Yalla\Output\Output;
use Yalla\Output\MigrationTable;

$output = new Output();
$table = new MigrationTable($output);

$table->addMigration('2024_01_01_create_users_table', 1, 'migrated', '2024-01-15 10:30:00')
      ->addMigration('2024_01_02_create_posts_table', 1, 'migrated', '2024-01-15 10:31:00')
      ->addMigration('2024_01_03_add_user_indexes', 2, 'pending', null)
      ->addMigration('2024_01_04_create_comments', null, 'error: Foreign key constraint', null)
      ->render();

$table->renderSummary();
```

### With Custom Border Style

```php
$table = new MigrationTable($output, [
    'borders' => MigrationTable::BORDER_DOUBLE,
    'max_width' => 140
]);
```

### Filtering Migrations

```php
// Show only pending migrations
$table->addMigration('migration_1', 1, 'migrated', '2024-01-15')
      ->addMigration('migration_2', null, 'pending', null)
      ->addMigration('migration_3', null, 'pending', null)
      ->filterByStatus('pending')
      ->render();

// Show migrations from specific batch
$table->filterByBatch(1)->render();
```

### Laravel-style Migration Display

```php
$migrations = [
    ['2014_10_12_000000_create_users_table', 1, 'migrated', '2024-01-15 10:30:00'],
    ['2014_10_12_100000_create_password_resets_table', 1, 'migrated', '2024-01-15 10:31:00'],
    ['2019_08_19_000000_create_failed_jobs_table', 2, 'migrated', '2024-01-16 09:15:00'],
    ['2024_01_01_000000_create_posts_table', 3, 'pending', null],
    ['2024_01_02_000000_add_user_avatar', null, 'error: Column already exists', null]
];

$table = new MigrationTable($output);
foreach ($migrations as [$name, $batch, $status, $date]) {
    $table->addMigration($name, $batch, $status, $date);
}

$table->render();
$table->renderSummary();
```

### Symfony-style Migration Display

```php
$table = new MigrationTable($output, [
    'borders' => MigrationTable::BORDER_ASCII,
    'alignment' => [
        MigrationTable::ALIGN_LEFT,   // Migration name
        MigrationTable::ALIGN_CENTER, // Batch
        MigrationTable::ALIGN_CENTER, // Status
        MigrationTable::ALIGN_RIGHT   // Date
    ]
]);
```

### Without Colors (CI/CD environments)

```php
$table = new MigrationTable($output, ['colors' => false]);
$table->addMigration('migration_1', 1, 'migrated', '2024-01-15');
// Status will show as "Migrated" without colors or emoji
```

## Integration Examples

### Laravel Migration Command

```php
class MigrationStatusCommand extends Command
{
    public function handle()
    {
        $output = new Output();
        $table = new MigrationTable($output);

        $migrations = DB::table('migrations')->get();

        foreach ($migrations as $migration) {
            $table->addMigration(
                $migration->migration,
                $migration->batch,
                'migrated',
                $migration->created_at
            );
        }

        // Add pending migrations
        $pending = $this->getMigrator()->pendingMigrations();
        foreach ($pending as $migration) {
            $table->addMigration(basename($migration), null, 'pending', null);
        }

        $table->render();
        $table->renderSummary();
    }
}
```

### Doctrine Migration Status

```php
class MigrationStatusCommand extends DoctrineCommand
{
    public function execute(): int
    {
        $output = new Output();
        $table = new MigrationTable($output);

        $versions = $this->migration->getVersions();

        foreach ($versions as $version) {
            $status = $version->isMigrated() ? 'migrated' : 'pending';
            $date = $version->isMigrated() ? $version->getExecutedAt() : null;

            $table->addMigration(
                $version->getVersion(),
                null, // Doctrine doesn't use batches
                $status,
                $date?->format('Y-m-d H:i:s')
            );
        }

        $table->render();
        $table->renderSummary();

        return 0;
    }
}
```

## See Also

- [Table API](/api/table) - Base table functionality
- [Output API](/api/output) - Main output formatting class
- [Table Formatting Guide](/guide/tables) - Complete guide with examples