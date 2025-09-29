<?php

declare(strict_types=1);

use Yalla\Environment\Environment;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/yalla_env_test_'.uniqid();
    mkdir($this->tempDir);

    // Save original environment
    $this->originalEnv = $_ENV;
});

afterEach(function () {
    // Restore original environment
    $_ENV = $this->originalEnv;

    // Clean up temp files recursively
    if (is_dir($this->tempDir)) {
        deleteDirectoryRecursively($this->tempDir);
    }
});

function deleteDirectoryRecursively(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $files = array_merge(
        glob($dir.'/*') ?: [],
        glob($dir.'/.*') ?: []
    );

    foreach ($files as $file) {
        if (basename($file) === '.' || basename($file) === '..') {
            continue;
        }

        if (is_dir($file)) {
            deleteDirectoryRecursively($file);
        } else {
            @chmod($file, 0644); // Ensure we can delete it
            @unlink($file);
        }
    }

    @rmdir($dir);
}

function createEnvFile(string $content, string $filename = '.env'): string
{
    $path = test()->tempDir.'/'.$filename;
    file_put_contents($path, $content);

    return $path;
}

test('load basic env file', function () {
    $content = <<<'ENV'
APP_NAME=TestApp
APP_ENV=testing
APP_DEBUG=true
DATABASE_URL=mysql://localhost/test
ENV;

    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->get('APP_NAME'))->toBe('TestApp');
    expect($env->get('APP_ENV'))->toBe('testing');
    expect($env->get('APP_DEBUG'))->toBe('1'); // true becomes '1'
    expect($env->get('DATABASE_URL'))->toBe('mysql://localhost/test');
});

test('skips comments', function () {
    $content = <<<'ENV'
# This is a comment
APP_NAME=TestApp
# Another comment
APP_ENV=testing # Inline comment
ENV;

    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->get('APP_NAME'))->toBe('TestApp');
    expect($env->get('APP_ENV'))->toBe('testing # Inline comment'); // Inline comments are part of value
});

test('handles quoted values', function () {
    $content = <<<'ENV'
SINGLE_QUOTED='single quoted value'
DOUBLE_QUOTED="double quoted value"
ESCAPED="Line with\nnewline and\ttab"
WITH_SPACES="  spaces preserved  "
ENV;

    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->get('SINGLE_QUOTED'))->toBe('single quoted value');
    expect($env->get('DOUBLE_QUOTED'))->toBe('double quoted value');
    expect($env->get('ESCAPED'))->toBe("line1\nline2");
    expect($env->get('WITH_SPACES'))->toBe('  spaces preserved  ');
});

test('variable expansion', function () {
    $content = <<<'ENV'
BASE_URL=http://localhost
API_URL=${BASE_URL}/api
FULL_URL=$BASE_URL/full
DATABASE_HOST=localhost
DATABASE_URL=mysql://${DATABASE_HOST}/db
ENV;

    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->get('BASE_URL'))->toBe('http://localhost');
    expect($env->get('API_URL'))->toBe('http://localhost/api');
    expect($env->get('FULL_URL'))->toBe('http://localhost/full');
    expect($env->get('DATABASE_URL'))->toBe('mysql://localhost/db');
});

test('special values', function () {
    $content = <<<'ENV'
TRUE_VALUE=true
FALSE_VALUE=false
NULL_VALUE=null
TRUE_PAREN=(true)
FALSE_PAREN=(false)
NULL_PAREN=(null)
ENV;

    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->get('TRUE_VALUE'))->toBe('1');
    expect($env->get('FALSE_VALUE'))->toBe('0');
    expect($env->get('NULL_VALUE'))->toBe('');
    expect($env->get('TRUE_PAREN'))->toBe('1');
    expect($env->get('FALSE_PAREN'))->toBe('0');
    expect($env->get('NULL_PAREN'))->toBe('');
});

test('get required', function () {
    $content = 'REQUIRED_VAR=value';
    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->getRequired('REQUIRED_VAR'))->toBe('value');

    expect(fn () => $env->getRequired('MISSING_VAR'))
        ->toThrow(RuntimeException::class, 'Required environment variable not set: MISSING_VAR');
});

test('set and has', function () {
    $env = new Environment([]);

    // Use a unique variable name to avoid system conflicts
    $uniqueVar = 'YALLA_TEST_VAR_'.uniqid();

    expect($env->has($uniqueVar))->toBeFalse();

    $env->set($uniqueVar, 'test value');

    expect($env->has($uniqueVar))->toBeTrue();
    expect($env->get($uniqueVar))->toBe('test value');
    expect($_ENV[$uniqueVar])->toBe('test value');
    expect(getenv($uniqueVar))->toBe('test value');

    // Clean up
    unset($_ENV[$uniqueVar]);
    putenv($uniqueVar);
});

test('environment detection', function () {
    // Create isolated environment without system vars
    $envFile = createEnvFile(''); // Empty file
    $env = new Environment([$envFile]);
    $env->clear(); // Clear any system variables

    // Ensure APP_ENV is not set in system environment
    putenv('APP_ENV');
    unset($_ENV['APP_ENV']);

    // Test production (default when not set)
    expect($env->isProduction())->toBeTrue();
    expect($env->isDevelopment())->toBeFalse();
    expect($env->isTesting())->toBeFalse();
    expect($env->isStaging())->toBeFalse();

    // Test development
    $env->set('APP_ENV', 'development');
    expect($env->isProduction())->toBeFalse();
    expect($env->isDevelopment())->toBeTrue();

    // Test with aliases
    $env->set('APP_ENV', 'dev');
    expect($env->isDevelopment())->toBeTrue();

    $env->set('APP_ENV', 'local');
    expect($env->isDevelopment())->toBeTrue();

    // Test testing
    $env->set('APP_ENV', 'testing');
    expect($env->isTesting())->toBeTrue();

    // Test staging
    $env->set('APP_ENV', 'staging');
    expect($env->isStaging())->toBeTrue();
});

test('is method', function () {
    $env = new Environment([]);
    $env->set('APP_ENV', 'custom');

    expect($env->is('custom'))->toBeTrue();
    expect($env->is('other', 'custom', 'another'))->toBeTrue();
    expect($env->is('production'))->toBeFalse();
    expect($env->is('development', 'testing'))->toBeFalse();
});

test('typed getters', function () {
    $content = <<<'ENV'
INT_VALUE=42
FLOAT_VALUE=3.14
BOOL_TRUE=true
BOOL_FALSE=false
BOOL_ONE=1
BOOL_ZERO=0
JSON_ARRAY=["one", "two", "three"]
CSV_ARRAY=apple,banana,orange
ENV;

    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    // Test integer
    expect($env->getInt('INT_VALUE'))->toBe(42);
    expect($env->getInt('MISSING', 10))->toBe(10);

    // Test float
    expect($env->getFloat('FLOAT_VALUE'))->toBe(3.14);
    expect($env->getFloat('MISSING', 1.5))->toBe(1.5);

    // Test boolean
    expect($env->getBool('BOOL_TRUE'))->toBeTrue();
    expect($env->getBool('BOOL_FALSE'))->toBeFalse();
    expect($env->getBool('BOOL_ONE'))->toBeTrue();
    expect($env->getBool('BOOL_ZERO'))->toBeFalse();
    expect($env->getBool('MISSING'))->toBeFalse();
    expect($env->getBool('MISSING', true))->toBeTrue();

    // Test array
    expect($env->getArray('JSON_ARRAY'))->toBe(['one', 'two', 'three']);
    expect($env->getArray('CSV_ARRAY'))->toBe(['apple', 'banana', 'orange']);
    expect($env->getArray('MISSING', ['default']))->toBe(['default']);
});

test('get array formats', function () {
    $env = new Environment([]);

    // Test JSON array
    $env->set('JSON_ARRAY', '["a","b","c"]');
    expect($env->getArray('JSON_ARRAY'))->toBe(['a', 'b', 'c']);

    // Test CSV
    $env->set('CSV_ARRAY', 'one, two, three');
    expect($env->getArray('CSV_ARRAY'))->toBe(['one', 'two', 'three']);

    // Test single value
    $env->set('SINGLE_VALUE', 'single');
    expect($env->getArray('SINGLE_VALUE'))->toBe(['single']);

    // Test invalid JSON falls back to string
    $env->set('INVALID_JSON', '[invalid');
    expect($env->getArray('INVALID_JSON'))->toBe(['[invalid']);
});

test('is debug', function () {
    // Create isolated environment
    $envFile = createEnvFile('');
    $env = new Environment([$envFile]);
    $env->clear();

    // Ensure APP_DEBUG is not set in system environment
    putenv('APP_DEBUG');
    unset($_ENV['APP_DEBUG']);

    // Default is false
    expect($env->isDebug())->toBeFalse();

    // Test various true values
    foreach (['true', '1', 'yes', 'on'] as $value) {
        $env->set('APP_DEBUG', $value);
        expect($env->isDebug())->toBeTrue();
    }

    // Test false values
    foreach (['false', '0', 'no', 'off'] as $value) {
        $env->set('APP_DEBUG', $value);
        expect($env->isDebug())->toBeFalse();
    }
});

test('overwrite behavior', function () {
    $content1 = "VAR1=first\nVAR2=first";
    $content2 = "VAR1=second\nVAR3=second";

    $file1 = createEnvFile($content1, '.env');
    $file2 = createEnvFile($content2, '.env.local');

    // Without overwrite - first value wins
    $env = new Environment([$file1, $file2]);
    expect($env->get('VAR1'))->toBe('first');
    expect($env->get('VAR2'))->toBe('first');
    expect($env->get('VAR3'))->toBe('second');

    // With overwrite via reload
    $env->clear();
    $env = new Environment([$file1]);
    $env->setFiles([$file1, $file2]);
    $env->reload(true);
    expect($env->get('VAR1'))->toBe('second'); // Overwritten
    expect($env->get('VAR2'))->toBe('first');
    expect($env->get('VAR3'))->toBe('second');
});

test('get all', function () {
    $content = "VAR1=value1\nVAR2=value2";
    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    $all = $env->all();
    expect($all)->toHaveKey('VAR1');
    expect($all)->toHaveKey('VAR2');
    expect($all['VAR1'])->toBe('value1');
    expect($all['VAR2'])->toBe('value2');
});

test('clear and reload', function () {
    $content = 'TEST_VAR=original';
    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->get('TEST_VAR'))->toBe('original');

    $env->clear();
    expect($env->get('TEST_VAR'))->toBeNull();

    $env->reload();
    expect($env->get('TEST_VAR'))->toBe('original');
});

test('empty lines', function () {
    $content = <<<'ENV'
VAR1=value1

VAR2=value2


VAR3=value3
ENV;

    $envFile = createEnvFile($content);
    $env = new Environment([$envFile]);

    expect($env->get('VAR1'))->toBe('value1');
    expect($env->get('VAR2'))->toBe('value2');
    expect($env->get('VAR3'))->toBe('value3');
});

test('non existent file', function () {
    // Should not throw exception for non-existent file
    $env = new Environment(['/non/existent/.env']);
    expect($env->get('ANYTHING'))->toBeNull();
});

test('unreadable file throws exception', function () {
    $envFile = createEnvFile('TEST=value');
    chmod($envFile, 0000);

    expect(fn () => new Environment([$envFile]))
        ->toThrow(RuntimeException::class, 'Environment file not readable');
});

test('default value', function () {
    $env = new Environment([]);

    expect($env->get('NON_EXISTENT', 'default'))->toBe('default');
    expect($env->get('NON_EXISTENT'))->toBeNull();
});
