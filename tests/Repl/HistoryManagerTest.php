<?php

declare(strict_types=1);

use Yalla\Repl\History\HistoryManager;
use Yalla\Repl\ReplConfig;

test('can add and retrieve history', function () {
    $config = new ReplConfig;
    $config->set('history.file', tempnam(sys_get_temp_dir(), 'history_'));

    $history = new HistoryManager($config);
    $history->add('Post::find(1)');
    $history->add('User::all()');

    expect($history->getPrevious())->toBe('User::all()');
    expect($history->getPrevious())->toBe('Post::find(1)');

    // Clean up
    unlink($config->get('history.file'));
});

test('ignores duplicate consecutive entries when configured', function () {
    $config = new ReplConfig;
    $config->set('history.file', tempnam(sys_get_temp_dir(), 'history_'));
    $config->set('history.ignore_duplicates', true);

    $history = new HistoryManager($config);
    $history->add('command1');
    $history->add('command1'); // Should be ignored
    $history->add('command2');

    $all = $history->getAll();
    expect(count($all))->toBe(2);
    expect($all)->toBe(['command1', 'command2']);

    // Clean up
    unlink($config->get('history.file'));
});

test('can navigate history with next and previous', function () {
    $config = new ReplConfig;
    $config->set('history.file', tempnam(sys_get_temp_dir(), 'history_'));

    $history = new HistoryManager($config);
    $history->add('first');
    $history->add('second');
    $history->add('third');

    // Start at the end
    expect($history->getPrevious())->toBe('third');
    expect($history->getPrevious())->toBe('second');
    expect($history->getPrevious())->toBe('first');
    expect($history->getPrevious())->toBe('first'); // Should stay at first

    expect($history->getNext())->toBe('second');
    expect($history->getNext())->toBe('third');
    expect($history->getNext())->toBe(''); // At the end

    // Clean up
    unlink($config->get('history.file'));
});

test('can search history', function () {
    $config = new ReplConfig;
    $config->set('history.file', tempnam(sys_get_temp_dir(), 'history_'));

    $history = new HistoryManager($config);
    $history->add('User::find(1)');
    $history->add('Post::all()');
    $history->add('User::where("active", true)');
    $history->add('Comment::latest()');

    $results = $history->search('User');
    expect(count($results))->toBe(2);
    expect($results)->toContain('User::find(1)');
    expect($results)->toContain('User::where("active", true)');

    // Clean up
    unlink($config->get('history.file'));
});

test('persists history to file', function () {
    $historyFile = tempnam(sys_get_temp_dir(), 'history_');
    $config = new ReplConfig;
    $config->set('history.file', $historyFile);

    // First session
    $history1 = new HistoryManager($config);
    $history1->add('command1');
    $history1->add('command2');

    // Second session - should load previous history
    $history2 = new HistoryManager($config);
    $all = $history2->getAll();

    expect($all)->toBe(['command1', 'command2']);

    // Clean up
    unlink($historyFile);
});

test('respects max entries limit', function () {
    $config = new ReplConfig;
    $config->set('history.file', tempnam(sys_get_temp_dir(), 'history_'));
    $config->set('history.max_entries', 3);

    $history = new HistoryManager($config);
    $history->add('command1');
    $history->add('command2');
    $history->add('command3');
    $history->add('command4'); // Should remove command1

    $all = $history->getAll();
    expect(count($all))->toBe(3);
    expect($all)->toBe(['command2', 'command3', 'command4']);

    // Clean up
    unlink($config->get('history.file'));
});

test('can clear history', function () {
    $historyFile = tempnam(sys_get_temp_dir(), 'history_');
    $config = new ReplConfig;
    $config->set('history.file', $historyFile);

    $history = new HistoryManager($config);
    $history->add('command1');
    $history->add('command2');

    $history->clear();

    expect($history->getAll())->toBe([]);
    expect(file_exists($historyFile))->toBeFalse();
});

test('does not add commands when history is disabled', function () {
    $config = new ReplConfig;
    $config->set('history.enabled', false);
    $config->set('history.file', tempnam(sys_get_temp_dir(), 'history_'));

    $history = new HistoryManager($config);
    $history->add('command1');
    $history->add('command2');

    expect($history->getAll())->toBe([]);
});

test('ignores empty commands', function () {
    $config = new ReplConfig;
    $config->set('history.file', tempnam(sys_get_temp_dir(), 'history_'));

    $history = new HistoryManager($config);
    $history->add('');
    $history->add('   ');
    $history->add("\t");
    $history->add('valid_command');

    expect($history->getAll())->toBe(['valid_command']);

    // Clean up
    unlink($config->get('history.file'));
});

test('returns null when getting previous with history disabled', function () {
    $config = new ReplConfig;
    $config->set('history.enabled', false);

    $history = new HistoryManager($config);

    expect($history->getPrevious())->toBeNull();
    expect($history->getNext())->toBeNull();
});

test('returns empty array when searching with history disabled', function () {
    $config = new ReplConfig;
    $config->set('history.enabled', false);

    $history = new HistoryManager($config);

    expect($history->search('anything'))->toBe([]);
});

test('handles non-existent history file gracefully', function () {
    $config = new ReplConfig;
    $config->set('history.file', '/tmp/non_existent_'.uniqid().'/history.txt');

    $history = new HistoryManager($config);

    expect($history->getAll())->toBe([]);

    // Should still be able to add commands
    $history->add('test');
    expect($history->getAll())->toBe(['test']);
});

test('creates directory if it does not exist when saving', function () {
    $tempDir = sys_get_temp_dir().'/test_history_'.uniqid();
    $historyFile = $tempDir.'/history.txt';

    $config = new ReplConfig;
    $config->set('history.file', $historyFile);

    $history = new HistoryManager($config);
    $history->add('command1');

    // Directory should have been created
    expect(is_dir($tempDir))->toBeTrue();
    expect(file_exists($historyFile))->toBeTrue();

    // Clean up
    unlink($historyFile);
    rmdir($tempDir);
});

test('loads empty history when file exists but is empty', function () {
    $historyFile = tempnam(sys_get_temp_dir(), 'history_');
    // Create an empty file
    file_put_contents($historyFile, '');

    $config = new ReplConfig;
    $config->set('history.file', $historyFile);

    $history = new HistoryManager($config);

    expect($history->getAll())->toBe([]);

    // Clean up
    unlink($historyFile);
});
