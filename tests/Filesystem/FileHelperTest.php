<?php

declare(strict_types=1);

use Yalla\Filesystem\FileHelper;

beforeEach(function () {
    $this->helper = new FileHelper;
    $this->tempDir = sys_get_temp_dir().'/yalla_filehelper_test_'.uniqid();
    mkdir($this->tempDir);
});

afterEach(function () {
    // Clean up temp directory
    if (is_dir($this->tempDir)) {
        $this->helper->deleteDirectory($this->tempDir);
    }
});

test('ensureDirectoryExists creates directory', function () {
    $dir = $this->tempDir.'/new/nested/directory';

    $this->assertFalse(is_dir($dir));

    $this->helper->ensureDirectoryExists($dir);

    $this->assertTrue(is_dir($dir));
});

test('ensureDirectoryExists with custom permissions', function () {
    $dir = $this->tempDir.'/custom_perms';

    $this->helper->ensureDirectoryExists($dir, 0700);

    $this->assertTrue(is_dir($dir));
    $perms = fileperms($dir) & 0777;
    $this->assertEquals(0700, $perms);
});

test('uniqueFilename generates unique names', function () {
    $pattern = 'test_{counter}.txt';

    $file1 = $this->helper->uniqueFilename($this->tempDir, $pattern);
    $this->assertEquals($this->tempDir.'/test_1.txt', $file1);

    // Create the file
    touch($file1);

    $file2 = $this->helper->uniqueFilename($this->tempDir, $pattern);
    $this->assertEquals($this->tempDir.'/test_2.txt', $file2);
});

test('uniqueFilename with timestamp placeholder', function () {
    $pattern = 'backup_{timestamp}.sql';

    $file = $this->helper->uniqueFilename($this->tempDir, $pattern);

    $this->assertMatchesRegularExpression('/backup_\d{4}_\d{2}_\d{2}_\d{6}\.sql$/', $file);
});

test('uniqueFilename with date placeholder', function () {
    $pattern = 'report_{date}.pdf';

    $file = $this->helper->uniqueFilename($this->tempDir, $pattern);

    $this->assertMatchesRegularExpression('/report_\d{4}-\d{2}-\d{2}\.pdf$/', $file);
});

test('uniqueFilename with unique placeholder', function () {
    $pattern = 'temp_{unique}.tmp';

    $file = $this->helper->uniqueFilename($this->tempDir, $pattern);

    $this->assertMatchesRegularExpression('/temp_[a-f0-9]{13}\.tmp$/', $file);
});

test('uniqueFilename with custom replacements', function () {
    $pattern = 'user_{id}_{name}.json';
    $replacements = ['id' => '123', 'name' => 'john'];

    $file = $this->helper->uniqueFilename($this->tempDir, $pattern, $replacements);

    $this->assertEquals($this->tempDir.'/user_123_john.json', $file);
});

test('safeWrite creates file atomically', function () {
    $file = $this->tempDir.'/safe.txt';
    $content = 'Safe content';

    $result = $this->helper->safeWrite($file, $content);

    $this->assertTrue($result);
    $this->assertFileExists($file);
    $this->assertEquals($content, file_get_contents($file));
});

test('safeWrite creates backup when file exists', function () {
    $file = $this->tempDir.'/data.json';
    $original = '{"version": 1}';
    $new = '{"version": 2}';

    file_put_contents($file, $original);

    $result = $this->helper->safeWrite($file, $new, true);

    $this->assertTrue($result);
    $this->assertEquals($new, file_get_contents($file));

    // Check backup was created
    $backups = glob($this->tempDir.'/.data.json.backup.*');
    $this->assertCount(1, $backups);
    $this->assertEquals($original, file_get_contents($backups[0]));
});

test('safeWrite without backup', function () {
    $file = $this->tempDir.'/nobackup.txt';
    file_put_contents($file, 'original');

    $this->helper->safeWrite($file, 'new', false);

    $backups = glob($this->tempDir.'/.nobackup.txt.backup.*');
    $this->assertEmpty($backups);
});

test('findFiles with glob pattern', function () {
    // Create test files
    touch($this->tempDir.'/test1.php');
    touch($this->tempDir.'/test2.php');
    touch($this->tempDir.'/other.txt');
    mkdir($this->tempDir.'/sub');
    touch($this->tempDir.'/sub/test3.php');

    // findFiles uses glob patterns
    $files = $this->helper->findFiles($this->tempDir, '*.php', false);

    $this->assertCount(2, $files);
    $this->assertContains($this->tempDir.'/test1.php', $files);
    $this->assertContains($this->tempDir.'/test2.php', $files);
});

test('findFiles with recursive search', function () {
    // Create nested structure
    touch($this->tempDir.'/root.txt');
    mkdir($this->tempDir.'/level1');
    touch($this->tempDir.'/level1/file1.txt');
    mkdir($this->tempDir.'/level1/level2');
    touch($this->tempDir.'/level1/level2/file2.txt');

    $files = $this->helper->findFiles($this->tempDir, '*.txt', true);

    $this->assertCount(3, $files);
});

test('findFiles non-recursive', function () {
    touch($this->tempDir.'/top.txt');
    mkdir($this->tempDir.'/sub');
    touch($this->tempDir.'/sub/nested.txt');

    $files = $this->helper->findFiles($this->tempDir, '*.txt', false);

    $this->assertCount(1, $files);
    $this->assertContains($this->tempDir.'/top.txt', $files);
});

test('relativePath calculates correctly', function () {
    $testCases = [
        ['/var/www/project', '/var/www/project/src/Model.php', 'src/Model.php'],
        ['/home/user/app', '/home/user/app', '.'],
        ['/home/user', '/home/other', '../other'],
        ['/a/b/c', '/a/b/d/e', '../d/e'],
        ['/root', '/root/sub/deep/file.txt', 'sub/deep/file.txt'],
    ];

    foreach ($testCases as [$from, $to, $expected]) {
        $result = $this->helper->relativePath($from, $to);
        $this->assertEquals($expected, $result);
    }
});

test('copyDirectory copies recursively', function () {
    // Create source structure
    $source = $this->tempDir.'/source';
    $dest = $this->tempDir.'/dest';

    mkdir($source);
    file_put_contents($source.'/file1.txt', 'content1');
    mkdir($source.'/subdir');
    file_put_contents($source.'/subdir/file2.txt', 'content2');

    $this->helper->copyDirectory($source, $dest);

    $this->assertFileExists($dest.'/file1.txt');
    $this->assertFileExists($dest.'/subdir/file2.txt');
    $this->assertEquals('content1', file_get_contents($dest.'/file1.txt'));
    $this->assertEquals('content2', file_get_contents($dest.'/subdir/file2.txt'));
});

test('deleteDirectory removes recursively', function () {
    $dir = $this->tempDir.'/to_delete';

    mkdir($dir);
    touch($dir.'/file.txt');
    mkdir($dir.'/sub');
    touch($dir.'/sub/nested.txt');

    $this->assertTrue(is_dir($dir));

    $this->helper->deleteDirectory($dir);

    $this->assertFalse(is_dir($dir));
});

test('humanFilesize formats sizes correctly', function () {
    $file = $this->tempDir.'/test.txt';

    // Test different sizes
    file_put_contents($file, str_repeat('a', 512));
    $this->assertEquals('512 B', $this->helper->humanFilesize($file));

    file_put_contents($file, str_repeat('a', 1024));
    $this->assertEquals('1 KB', $this->helper->humanFilesize($file));

    file_put_contents($file, str_repeat('a', 1024 * 1024));
    $this->assertEquals('1 MB', $this->helper->humanFilesize($file));

    file_put_contents($file, str_repeat('a', 1536 * 1024)); // 1.5 MB
    $this->assertEquals('1.5 MB', $this->helper->humanFilesize($file));
});

test('humanFilesize with different precisions', function () {
    $file = $this->tempDir.'/test.txt';
    file_put_contents($file, str_repeat('a', 1536 * 1024)); // 1.5 MB

    $this->assertEquals('1.5 MB', $this->helper->humanFilesize($file, 2));
    $this->assertEquals('1.5 MB', $this->helper->humanFilesize($file, 3));
});

test('humanFilesize handles non-existent file', function () {
    $result = $this->helper->humanFilesize($this->tempDir.'/nonexistent.txt');
    $this->assertEquals('0 B', $result);
});

// Tests for getMimeType and isWritable removed - methods don't exist in FileHelper

test('ensureDirectoryExists handles existing directory', function () {
    $this->helper->ensureDirectoryExists($this->tempDir);
    // Should not throw exception
    $this->assertTrue(is_dir($this->tempDir));
});

test('copyDirectory handles non-existent source', function () {
    $result = @$this->helper->copyDirectory(
        $this->tempDir.'/nonexistent',
        $this->tempDir.'/dest'
    );

    $this->assertFalse(is_dir($this->tempDir.'/dest'));
});

test('deleteDirectory handles non-existent directory', function () {
    // Should not throw exception
    $this->helper->deleteDirectory($this->tempDir.'/nonexistent');
    $this->assertTrue(true); // Just verify no exception
});

test('findFiles handles non-existent directory', function () {
    $files = $this->helper->findFiles($this->tempDir.'/nonexistent', '*');
    $this->assertEmpty($files);
});

test('safeWrite handles directory creation', function () {
    $file = $this->tempDir.'/new/deep/path/file.txt';

    $result = $this->helper->safeWrite($file, 'content');

    $this->assertTrue($result);
    $this->assertFileExists($file);
});

test('uniqueFilename handles edge cases', function () {
    // No placeholders
    $file = $this->helper->uniqueFilename($this->tempDir, 'static.txt');
    $this->assertEquals($this->tempDir.'/static.txt', $file);

    // Multiple same placeholders
    $pattern = '{counter}_{counter}.txt';
    $file = $this->helper->uniqueFilename($this->tempDir, $pattern);
    $this->assertEquals($this->tempDir.'/1_1.txt', $file);
});
