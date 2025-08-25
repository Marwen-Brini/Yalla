#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use Yalla\Output\Output;

$output = new Output;

$output->box('Yalla REPL Examples', Output::CYAN);

$output->section('Basic Usage');
$output->writeln('Start the REPL:');
$output->info('  php bin/yalla repl');
$output->writeln('');

$output->section('REPL Commands');
$commands = [
    ':help' => 'Show available commands',
    ':exit' => 'Exit the REPL',
    ':vars' => 'Show defined variables',
    ':imports' => 'Show imported classes',
    ':clear' => 'Clear the screen',
    ':history' => 'Show command history',
];

foreach ($commands as $cmd => $desc) {
    $output->writeln('  '.$output->color($cmd, Output::GREEN)." - $desc");
}

$output->section('Example Sessions');

$output->writeln('1. Basic calculations:');
$output->dim('   >>> 2 + 2');
$output->success('   4');
$output->writeln('');

$output->writeln('2. Define variables:');
$output->dim("   >>> \$name = 'Yalla'");
$output->success("   'Yalla'");
$output->dim('   >>> echo "Hello, $name!"');
$output->success('   Hello, Yalla!');
$output->writeln('');

$output->writeln('3. Work with arrays:');
$output->dim("   >>> \$users = ['Alice', 'Bob', 'Charlie']");
$output->dim('   >>> count($users)');
$output->success('   3');
$output->writeln('');

$output->section('Configuration');
$output->writeln('Create a repl.config.php file:');
$output->writeln('');

$configExample = <<<'PHP'
<?php
return [
    'shortcuts' => [
        'Str' => '\Illuminate\Support\Str',
        'Carbon' => '\Carbon\Carbon',
    ],
    'display' => [
        'prompt' => 'myapp> ',
        'performance' => true,
    ],
];
PHP;

$output->dim($configExample);
$output->writeln('');

$output->writeln('Then run with config:');
$output->info('  php bin/yalla repl --config=repl.config.php');

$output->section('Custom Extensions');
$output->writeln('Create custom extensions to add functionality:');
$output->writeln('');

$extensionExample = <<<'PHP'
class MyReplExtension implements ReplExtension {
    public function register(ReplContext $context): void {
        $context->addCommand('models', function($args, $output) {
            // List all models
        });
        
        $context->addShortcut('User', '\App\Models\User');
    }
}
PHP;

$output->dim($extensionExample);

$output->writeln('');
$output->success('✨ Happy coding with Yalla REPL!');
