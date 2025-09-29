<?php

declare(strict_types=1);

use Yalla\Environment\Environment;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/yalla_env_extra_test_' . uniqid();
    mkdir($this->tempDir);

    // Clear any system environment variables that might interfere
    putenv('APP_ENV');
    putenv('APP_DEBUG');
    putenv('BASE');
    putenv('VAR1');
    putenv('VAR2');
    putenv('VAR3');
    unset($_ENV['APP_ENV']);
    unset($_ENV['APP_DEBUG']);
    unset($_ENV['BASE']);
    unset($_ENV['VAR1']);
    unset($_ENV['VAR2']);
    unset($_ENV['VAR3']);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        // More thorough cleanup - handle all files including hidden ones
        $files = array_merge(
            glob($this->tempDir . '/*') ?: [],
            glob($this->tempDir . '/.*') ?: []
        );

        foreach ($files as $file) {
            if (basename($file) !== '.' && basename($file) !== '..') {
                if (is_file($file)) {
                    @chmod($file, 0644); // Ensure we can delete it
                    @unlink($file);
                }
            }
        }
        @rmdir($this->tempDir);
    }
});

test('load returns early when already loaded and not overwriting', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, "FIRST=value1\n");

    $env = new Environment([$envFile]);

    // First load
    $env->load();
    $this->assertEquals('value1', $env->get('FIRST'));

    // Modify file
    file_put_contents($envFile, "FIRST=value2\nSECOND=value2\n");

    // Second load without overwrite - should return early
    $env->load(false);
    $this->assertEquals('value1', $env->get('FIRST'));
    $this->assertNull($env->get('SECOND'));
});

test('constructor loads files automatically', function () {
    // The constructor loads files automatically, but doesn't throw if they don't exist
    // It only throws if a file exists but is not readable
    $unreadableFile = $this->tempDir . '/unreadable.env';
    file_put_contents($unreadableFile, 'TEST=value');
    chmod($unreadableFile, 0000); // Make file unreadable

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Environment file not readable');

    // Constructor will call load() which will throw for unreadable file
    new Environment([$unreadableFile]);
});

test('getArray handles invalid JSON with fallback', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, 'INVALID_JSON={"broken":');

    $env = new Environment([$envFile]);

    $result = $env->getArray('INVALID_JSON');
    $this->assertEquals(['{"broken":'], $result);
});

test('expandVariables handles multiple variable formats', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, <<<ENV
BASE=base
VAR1=\${BASE}/path
VAR2=\$BASE/path
VAR3=\${BASE}_suffix
VAR4=prefix_\$BASE
ENV
    );

    $env = new Environment([$envFile]);

    $this->assertEquals('base/path', $env->get('VAR1'));
    $this->assertEquals('base/path', $env->get('VAR2'));
    $this->assertEquals('base_suffix', $env->get('VAR3'));
    $this->assertEquals('prefix_base', $env->get('VAR4'));
});

test('isStaging checks staging environment', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, '');

    $env = new Environment([$envFile]);

    // Default is false
    $this->assertFalse($env->isStaging());

    // Set to staging
    $env->set('APP_ENV', 'staging');
    $this->assertTrue($env->isStaging());

    // Check stage alias
    $env->set('APP_ENV', 'stage');
    $this->assertTrue($env->isStaging());
});

test('clear method with multiple environments set', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, <<<ENV
VAR1=value1
VAR2=value2
VAR3=value3
ENV
    );

    $env = new Environment([$envFile]);

    $this->assertEquals('value1', $env->get('VAR1'));
    $this->assertEquals('value2', $env->get('VAR2'));
    $this->assertEquals('value3', $env->get('VAR3'));

    $env->clear();

    $this->assertNull($env->get('VAR1'));
    $this->assertNull($env->get('VAR2'));
    $this->assertNull($env->get('VAR3'));
});

test('reload loads fresh environment variables', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, "INITIAL=first\n");

    $env = new Environment([$envFile]);
    $this->assertEquals('first', $env->get('INITIAL'));

    // Update file
    file_put_contents($envFile, "INITIAL=second\nNEW=added\n");

    // Reload
    $env->reload();

    $this->assertEquals('second', $env->get('INITIAL'));
    $this->assertEquals('added', $env->get('NEW'));
});

test('parseValue handles edge cases', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, <<<ENV
EMPTY=
SPACES=
QUOTES=""
SINGLE_QUOTES=''
MIXED_QUOTES="hello'world"
ESCAPED="line1\\nline2"
TABS="\\ttabbed"
ENV
    );

    $env = new Environment([$envFile]);

    $this->assertEquals('', $env->get('EMPTY'));
    $this->assertEquals('', $env->get('SPACES'));
    $this->assertEquals('', $env->get('QUOTES'));
    $this->assertEquals('', $env->get('SINGLE_QUOTES'));
    $this->assertEquals("hello'world", $env->get('MIXED_QUOTES'));
    $this->assertEquals("line1\nline2", $env->get('ESCAPED'));
    $this->assertEquals("\ttabbed", $env->get('TABS'));
});

test('load handles malformed env file lines', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, <<<ENV
VALID=value
NOEQUALS
=NOKEY
KEY=
# Comment line
  # Indented comment
AFTER_COMMENT=works
ENV
    );

    $env = new Environment([$envFile]);

    $this->assertEquals('value', $env->get('VALID'));
    $this->assertNull($env->get('NOEQUALS'));
    $this->assertNull($env->get(''));
    $this->assertEquals('', $env->get('KEY'));
    $this->assertEquals('works', $env->get('AFTER_COMMENT'));
});

test('multiple env files with overwrite behavior', function () {
    $env1 = $this->tempDir . '/.env';
    $env2 = $this->tempDir . '/.env.local';

    file_put_contents($env1, <<<ENV
SHARED=from_env
ONLY_ENV=env_value
ENV
    );

    file_put_contents($env2, <<<ENV
SHARED=from_local
ONLY_LOCAL=local_value
ENV
    );

    // When loading multiple files, they're loaded in order but don't overwrite by default
    // The first occurrence of a variable wins unless explicitly using load(true)
    $env = new Environment([$env1, $env2]);

    // Since overwrite is false by default, first file wins
    $this->assertEquals('from_env', $env->get('SHARED'));
    $this->assertEquals('env_value', $env->get('ONLY_ENV'));
    $this->assertEquals('local_value', $env->get('ONLY_LOCAL'));
});

test('expandVariables with non-existent variable', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, <<<ENV
BASE=value
REF1=\${NONEXISTENT}/path
REF2=\$NONEXISTENT/path
ENV
    );

    $env = new Environment([$envFile]);

    $this->assertEquals('/path', $env->get('REF1'));
    $this->assertEquals('/path', $env->get('REF2'));
});

test('special boolean value variations', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, <<<ENV
BOOL_TRUE=TRUE
BOOL_ON=ON
BOOL_YES=YES
BOOL_FALSE=FALSE
BOOL_OFF=OFF
BOOL_NO=NO
BOOL_NULL=NULL
ENV
    );

    $env = new Environment([$envFile]);

    $this->assertTrue($env->getBool('BOOL_TRUE'));
    $this->assertTrue($env->getBool('BOOL_ON'));
    $this->assertTrue($env->getBool('BOOL_YES'));
    $this->assertFalse($env->getBool('BOOL_FALSE'));
    $this->assertFalse($env->getBool('BOOL_OFF'));
    $this->assertFalse($env->getBool('BOOL_NO'));
    $this->assertFalse($env->getBool('BOOL_NULL'));
});

test('getFloat handles various formats', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, <<<ENV
FLOAT1=3.14
FLOAT2=0.5
FLOAT3=-2.5
FLOAT4=1e3
FLOAT5=.5
INTEGER=42
STRING=not_a_number
ENV
    );

    $env = new Environment([$envFile]);

    $this->assertEquals(3.14, $env->getFloat('FLOAT1'));
    $this->assertEquals(0.5, $env->getFloat('FLOAT2'));
    $this->assertEquals(-2.5, $env->getFloat('FLOAT3'));
    $this->assertEquals(1000.0, $env->getFloat('FLOAT4'));
    $this->assertEquals(0.5, $env->getFloat('FLOAT5'));
    $this->assertEquals(42.0, $env->getFloat('INTEGER'));
    $this->assertEquals(0.0, $env->getFloat('STRING'));
    $this->assertEquals(99.9, $env->getFloat('MISSING', 99.9));
});

test('get falls back to getenv when value not in internal array', function () {
    // Set a value using putenv that's not in our loaded env
    putenv('SYSTEM_VAR=from_getenv');

    $env = new Environment();

    // This should hit line 186 - getting value from getenv
    expect($env->get('SYSTEM_VAR'))->toBe('from_getenv');

    // Clean up
    putenv('SYSTEM_VAR');
});

test('getBool handles non-string values', function () {
    $env = new Environment();

    // Use reflection to force a non-string value in the internal variables array
    $reflection = new ReflectionClass($env);
    $varsProperty = $reflection->getProperty('variables');
    $varsProperty->setAccessible(true);

    $vars = $varsProperty->getValue($env);
    $vars['INT_TRUE'] = 1;  // Integer 1 (truthy)
    $vars['INT_FALSE'] = 0; // Integer 0 (falsy)
    $vars['FLOAT_TRUE'] = 3.14; // Float (truthy)
    $varsProperty->setValue($env, $vars);

    // These should hit line 362 - casting non-string to bool
    expect($env->getBool('INT_TRUE'))->toBeTrue();
    expect($env->getBool('INT_FALSE'))->toBeFalse();
    expect($env->getBool('FLOAT_TRUE'))->toBeTrue();
});

test('getArray returns single value as array', function () {
    $envFile = $this->tempDir . '/.env';
    file_put_contents($envFile, "
SINGLE_VALUE=just_a_string_without_comma
");

    $env = new Environment([$envFile]);

    // This should hit line 393 - returning single value as array
    expect($env->getArray('SINGLE_VALUE', ['default']))->toBe(['just_a_string_without_comma']);
});

test('getArray returns default when value is not string or array', function () {
    $env = new Environment();

    // Manually set the internal value to ensure it's not a string
    // We need to use reflection to directly set a non-string value
    $reflection = new ReflectionClass($env);
    $varsProperty = $reflection->getProperty('variables');
    $varsProperty->setAccessible(true);

    $vars = $varsProperty->getValue($env);
    $vars['NON_STRING_VALUE'] = 123; // Integer value
    $varsProperty->setValue($env, $vars);

    // This should hit line 396 - returning default for non-string/non-array value
    expect($env->getArray('NON_STRING_VALUE', ['default']))->toBe(['default']);
});