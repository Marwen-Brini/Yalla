<?php

declare(strict_types=1);

use Yalla\Repl\ReplConfig;

test('loads default configuration', function () {
    $config = new ReplConfig;

    expect($config->get('display.colors'))->toBeTrue();
    expect($config->get('display.prompt'))->toBe('[{counter}] yalla> ');
    expect($config->get('history.enabled'))->toBeTrue();
    expect($config->get('autocomplete.enabled'))->toBeTrue();
});

test('can get nested configuration values', function () {
    $config = new ReplConfig;

    expect($config->get('display.colors'))->toBeTrue();
    expect($config->get('history.max_entries'))->toBe(1000);
    expect($config->get('security.sandbox'))->toBeFalse();
});

test('returns default value for missing keys', function () {
    $config = new ReplConfig;

    expect($config->get('nonexistent.key', 'default'))->toBe('default');
    expect($config->get('display.nonexistent', null))->toBeNull();
});

test('can set configuration values', function () {
    $config = new ReplConfig;

    $config->set('custom.value', 'test');
    $config->set('display.colors', false);

    expect($config->get('custom.value'))->toBe('test');
    expect($config->get('display.colors'))->toBeFalse();
});

test('can merge configuration arrays', function () {
    $config = new ReplConfig;

    $config->merge([
        'custom' => ['key' => 'value'],
        'display' => ['prompt' => 'custom> '],
    ]);

    expect($config->get('custom.key'))->toBe('value');
    expect($config->get('display.prompt'))->toBe('custom> ');
    expect($config->get('display.colors'))->toBeTrue(); // Should still exist
});

test('loads configuration from file', function () {
    // Create a temporary config file
    $tempFile = tempnam(sys_get_temp_dir(), 'repl_config_');
    file_put_contents($tempFile, '<?php return ["test" => ["key" => "value"]];');

    $config = new ReplConfig($tempFile);

    expect($config->get('test.key'))->toBe('value');
    expect($config->get('display.colors'))->toBeTrue(); // Defaults should still exist

    unlink($tempFile);
});

test('can get all configuration', function () {
    $config = new ReplConfig;

    $all = $config->all();

    expect($all)->toBeArray();
    expect($all)->toHaveKey('display');
    expect($all)->toHaveKey('history');
    expect($all)->toHaveKey('autocomplete');
    expect($all)->toHaveKey('security');
});
