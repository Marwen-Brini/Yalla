#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Yalla\Output\Output;
use Yalla\Output\Table;
use Yalla\Output\MigrationTable;

$output = new Output();

// Example 1: Basic table with default Unicode borders
$output->section('Example 1: Basic Table');
$output->table(
    ['Name', 'Age', 'City', 'Country'],
    [
        ['John Doe', '30', 'New York', 'USA'],
        ['Jane Smith', '25', 'London', 'UK'],
        ['Pierre Dubois', '35', 'Paris', 'France'],
        ['Yuki Tanaka', '28', 'Tokyo', 'Japan']
    ]
);

// Example 2: Table with ASCII borders (CI/CD friendly)
$output->section('Example 2: ASCII Borders');
$output->table(
    ['Product', 'Price', 'Stock', 'Status'],
    [
        ['Laptop', '$999', '15', 'Available'],
        ['Mouse', '$25', '0', 'Out of Stock'],
        ['Keyboard', '$75', '8', 'Low Stock'],
        ['Monitor', '$299', '22', 'Available']
    ],
    ['borders' => Table::BORDER_ASCII]
);

// Example 3: Compact table (minimal borders)
$output->section('Example 3: Compact Table');
$output->table(
    ['Test', 'Result', 'Time (ms)'],
    [
        ['UserTest::testLogin', 'PASS', '45'],
        ['UserTest::testLogout', 'PASS', '12'],
        ['UserTest::testRegister', 'FAIL', '156'],
        ['ProductTest::testCreate', 'PASS', '89']
    ],
    ['borders' => Table::BORDER_COMPACT]
);

// Example 4: Markdown format (for documentation)
$output->section('Example 4: Markdown Format');
$output->table(
    ['Feature', 'Status', 'Priority', 'Assigned To'],
    [
        ['Table Output', 'Complete', 'Critical', 'Team A'],
        ['Confirmations', 'In Progress', 'Critical', 'Team B'],
        ['Progress Bar', 'Pending', 'High', 'Team A'],
        ['File Generation', 'Pending', 'High', 'Team C']
    ],
    [
        'borders' => Table::BORDER_MARKDOWN,
        'alignment' => [
            Table::ALIGN_LEFT,
            Table::ALIGN_CENTER,
            Table::ALIGN_CENTER,
            Table::ALIGN_RIGHT
        ]
    ]
);

// Example 5: Double borders for emphasis
$output->section('Example 5: Double Borders');
$output->table(
    ['Environment', 'Status', 'URL'],
    [
        ['Production', '✅ Online', 'https://app.example.com'],
        ['Staging', '✅ Online', 'https://staging.example.com'],
        ['Development', '⚠️ Maintenance', 'https://dev.example.com']
    ],
    ['borders' => Table::BORDER_DOUBLE]
);

// Example 6: Rounded borders (modern look)
$output->section('Example 6: Rounded Borders');
$output->table(
    ['Database', 'Size', 'Tables', 'Last Backup'],
    [
        ['users_db', '1.2 GB', '15', '2024-01-26 03:00'],
        ['products_db', '856 MB', '8', '2024-01-26 03:00'],
        ['logs_db', '5.4 GB', '3', '2024-01-25 23:00']
    ],
    ['borders' => Table::BORDER_ROUNDED]
);

// Example 7: Table with custom alignment
$output->section('Example 7: Custom Alignment');
$output->table(
    ['Item', 'Quantity', 'Price', 'Total'],
    [
        ['Widget A', '10', '$5.99', '$59.90'],
        ['Gadget B', '5', '$12.50', '$62.50'],
        ['Tool C', '25', '$2.00', '$50.00'],
        ['Device D', '3', '$89.99', '$269.97']
    ],
    [
        'alignment' => [
            Table::ALIGN_LEFT,   // Item
            Table::ALIGN_CENTER, // Quantity
            Table::ALIGN_RIGHT,  // Price
            Table::ALIGN_RIGHT   // Total
        ]
    ]
);

// Example 8: Table with row separators
$output->section('Example 8: Row Separators');
$output->table(
    ['Stage', 'Description', 'Duration', 'Status'],
    [
        ['Build', 'Compile source code', '2m 15s', '✅ Complete'],
        ['Test', 'Run unit tests', '5m 32s', '✅ Complete'],
        ['Deploy', 'Deploy to staging', '1m 45s', '🔄 Running'],
        ['Verify', 'Health checks', '-', '⏳ Pending']
    ],
    [
        'row_separator' => true,
        'borders' => Table::BORDER_UNICODE
    ]
);

// Example 9: Using fluent interface
$output->section('Example 9: Fluent Interface');
$table = $output->createTable(['borders' => Table::BORDER_ASCII]);
$table->setHeaders(['ID', 'Task', 'Priority', 'Completed'])
      ->addRow(['1', 'Write documentation', 'High', 'Yes'])
      ->addRow(['2', 'Fix bugs', 'Critical', 'No'])
      ->addRow(['3', 'Add tests', 'Medium', 'Yes'])
      ->addRow(['4', 'Code review', 'Medium', 'No'])
      ->sortBy(2) // Sort by priority
      ->render();

// Example 10: Table with cell formatters
$output->section('Example 10: Cell Formatters');
$table = $output->createTable();
$table->setHeaders(['Service', 'Status', 'Uptime', 'Memory'])
      ->setCellFormatter(1, function($status) use ($output) {
          return match($status) {
              'running' => $output->color('● Running', Output::GREEN),
              'stopped' => $output->color('● Stopped', Output::RED),
              'paused' => $output->color('● Paused', Output::YELLOW),
              default => $status
          };
      })
      ->addRow(['nginx', 'running', '15 days', '128 MB'])
      ->addRow(['mysql', 'running', '15 days', '512 MB'])
      ->addRow(['redis', 'stopped', '-', '-'])
      ->addRow(['elasticsearch', 'paused', '3 days', '1.2 GB'])
      ->render();

// Example 11: Migration table
$output->section('Example 11: Migration Table');
$migrationTable = new MigrationTable($output);
$migrationTable
    ->addMigratedMigration('2024_01_01_create_users_table', 1, '2024-01-01 10:30:00')
    ->addMigratedMigration('2024_01_02_add_email_verified', 1, '2024-01-01 10:31:00')
    ->addMigratedMigration('2024_01_03_create_posts_table', 2, '2024-01-15 14:20:00')
    ->addPendingMigration('2024_01_04_add_user_avatar')
    ->addPendingMigration('2024_01_05_create_comments_table')
    ->addErrorMigration('2024_01_06_add_foreign_keys', 3, 'Constraint violation')
    ->render();

// Show summary
$migrationTable->renderSummary();

// Example 12: Filtered migration table
$output->section('Example 12: Filtered Migration Table (Pending Only)');
$filteredTable = new MigrationTable($output, ['borders' => Table::BORDER_COMPACT]);
$filteredTable
    ->addMigratedMigration('2024_01_01_create_users_table', 1, '2024-01-01 10:30:00')
    ->addPendingMigration('2024_01_02_add_email_column')
    ->addPendingMigration('2024_01_03_create_products_table')
    ->addMigratedMigration('2024_01_04_create_orders_table', 2, '2024-01-02 11:00:00')
    ->filterByStatus('pending')
    ->render();

// Example 13: Table with index numbers
$output->section('Example 13: Table with Index');
$output->table(
    ['Player', 'Score', 'Level'],
    [
        ['Alice', '9500', '10'],
        ['Bob', '8200', '9'],
        ['Charlie', '7800', '8'],
        ['Diana', '9800', '10'],
        ['Eve', '6500', '7']
    ],
    [
        'show_index' => true,
        'index_name' => 'Rank',
        'borders' => Table::BORDER_UNICODE
    ]
);

// Example 14: Table with maximum width constraint
$output->section('Example 14: Width-Constrained Table');
$output->table(
    ['File', 'Description', 'Size'],
    [
        ['very_long_filename_that_exceeds_normal_width.php', 'This is a very long description that would normally make the table extremely wide', '1.2 MB'],
        ['short.js', 'Brief', '15 KB'],
        ['another_extremely_long_filename_here.css', 'Another lengthy description to demonstrate width constraints', '458 KB']
    ],
    [
        'max_width' => 80,
        'borders' => Table::BORDER_ASCII
    ]
);

// Example 15: Empty/null value handling
$output->section('Example 15: Empty Value Handling');
$output->table(
    ['Field', 'Required', 'Default', 'Description'],
    [
        ['id', true, null, 'Primary key'],
        ['name', true, '', 'User name'],
        ['email', true, null, 'Email address'],
        ['bio', false, null, ''],
        ['age', false, 18, 'User age']
    ],
    ['borders' => Table::BORDER_UNICODE]
);

// Example 16: Performance metrics table
$output->section('Example 16: Performance Metrics');
$perfTable = $output->createTable([
    'borders' => Table::BORDER_DOUBLE,
    'alignment' => [
        Table::ALIGN_LEFT,
        Table::ALIGN_RIGHT,
        Table::ALIGN_RIGHT,
        Table::ALIGN_RIGHT,
        Table::ALIGN_CENTER
    ]
]);

$perfTable->setHeaders(['Endpoint', 'Requests', 'Avg Time', 'Max Time', 'Status'])
          ->addRow(['/api/users', '45,231', '45ms', '230ms', '✅'])
          ->addRow(['/api/products', '28,456', '67ms', '450ms', '✅'])
          ->addRow(['/api/orders', '12,789', '125ms', '1,200ms', '⚠️'])
          ->addRow(['/api/search', '89,234', '250ms', '3,400ms', '❌'])
          ->render();

$output->writeln('');
$output->success('All table examples completed successfully!');