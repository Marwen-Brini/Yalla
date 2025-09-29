<?php

declare(strict_types=1);

use Yalla\Filesystem\FileHelper;

beforeEach(function () {
    $this->helper = new FileHelper();
    $this->tempDir = sys_get_temp_dir() . '/yalla_filehelper_additional_' . uniqid();
    mkdir($this->tempDir);
});

afterEach(function () {
    // Clean up temp dir
    if (is_dir($this->tempDir)) {
        $files = array_diff(scandir($this->tempDir), ['.', '..']);
        foreach ($files as $file) {
            $path = $this->tempDir . '/' . $file;
            if (is_dir($path)) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($this->tempDir);
    }
});

test('ensureDirectoryExists throws exception when mkdir fails', function () {
    // Create a file where we want to create a directory
    $filePath = $this->tempDir . '/testfile';
    touch($filePath);

    // Try to create a directory with the same name as the file
    expect(fn() => $this->helper->ensureDirectoryExists($filePath))
        ->toThrow(RuntimeException::class, 'Failed to create directory');
});

test('isAbsolutePath detects Unix absolute paths', function () {
    expect($this->helper->isAbsolutePath('/home/user'))->toBeTrue();
    expect($this->helper->isAbsolutePath('/tmp'))->toBeTrue();
    expect($this->helper->isAbsolutePath('relative/path'))->toBeFalse();
});

test('isAbsolutePath detects Windows absolute paths', function () {
    expect($this->helper->isAbsolutePath('C:\\Windows'))->toBeTrue();
    expect($this->helper->isAbsolutePath('D:\\Users\\Documents'))->toBeTrue();
    expect($this->helper->isAbsolutePath('\\\\server\\share'))->toBeTrue(); // UNC path
    expect($this->helper->isAbsolutePath('relative\\path'))->toBeFalse();
});

test('makeAbsolute converts relative to absolute path', function () {
    $path = $this->helper->makeAbsolute('file.txt', '/home/user');
    expect($path)->toBe('/home/user' . DIRECTORY_SEPARATOR . 'file.txt');

    $absPath = $this->helper->makeAbsolute('/already/absolute', '/home/user');
    expect($absPath)->toBe('/already/absolute');
});

test('makeAbsolute uses current directory when base is null', function () {
    $path = $this->helper->makeAbsolute('file.txt');
    expect($path)->toBe(getcwd() . DIRECTORY_SEPARATOR . 'file.txt');
});

test('getExtension returns file extension', function () {
    expect($this->helper->getExtension('file.txt'))->toBe('txt');
    expect($this->helper->getExtension('archive.tar.gz'))->toBe('gz');
    expect($this->helper->getExtension('/path/to/file.php'))->toBe('php');
    expect($this->helper->getExtension('noextension'))->toBe('');
});

test('getFilenameWithoutExtension returns filename without extension', function () {
    expect($this->helper->getFilenameWithoutExtension('file.txt'))->toBe('file');
    expect($this->helper->getFilenameWithoutExtension('/path/to/document.pdf'))->toBe('document');
    expect($this->helper->getFilenameWithoutExtension('archive.tar.gz'))->toBe('archive.tar');
    expect($this->helper->getFilenameWithoutExtension('noextension'))->toBe('noextension');
});

test('readLines reads file lines', function () {
    $file = $this->tempDir . '/lines.txt';
    file_put_contents($file, "line1\nline2\n\nline3\n");

    $lines = $this->helper->readLines($file);
    expect($lines)->toBe(['line1', 'line2', '', 'line3']);

    $linesNoEmpty = $this->helper->readLines($file, true);
    expect($linesNoEmpty)->toBe(['line1', 'line2', 'line3']);
});

test('readLines returns empty array for non-existent file', function () {
    $lines = $this->helper->readLines($this->tempDir . '/nonexistent.txt');
    expect($lines)->toBe([]);
});

test('writeLines writes lines to file', function () {
    $file = $this->tempDir . '/output.txt';
    $lines = ['line1', 'line2', 'line3'];

    $result = $this->helper->writeLines($file, $lines);
    expect($result)->toBeTrue();

    $content = file_get_contents($file);
    expect($content)->toBe("line1\nline2\nline3\n");
});

test('writeLines appends to file when append is true', function () {
    $file = $this->tempDir . '/append.txt';
    file_put_contents($file, "existing\n");

    $lines = ['new1', 'new2'];
    $result = $this->helper->writeLines($file, $lines, true);
    expect($result)->toBeTrue();

    $content = file_get_contents($file);
    expect($content)->toBe("existing\nnew1\nnew2\n");
});

test('writeLines handles empty array', function () {
    $file = $this->tempDir . '/empty.txt';

    $result = $this->helper->writeLines($file, []);
    expect($result)->toBeTrue();

    $content = file_get_contents($file);
    expect($content)->toBe('');
});

test('safeWrite with atomic option', function () {
    $file = $this->tempDir . '/atomic.txt';

    // Test atomic write
    $result = $this->helper->safeWrite($file, 'test content', true, true);
    expect($result)->toBeTrue();
    expect(file_get_contents($file))->toBe('test content');
});

test('relativePath with different separators', function () {
    // Test with mixed separators
    $from = $this->tempDir . '/a/b';
    $to = $this->tempDir . '/c/d/file.txt';

    $relative = $this->helper->relativePath($from, $to);
    expect($relative)->toBe('../../c/d/file.txt');
});

test('copyDirectory with subdirectories', function () {
    $source = $this->tempDir . '/source';
    $dest = $this->tempDir . '/dest';

    mkdir($source);
    mkdir($source . '/subdir');
    touch($source . '/file.txt');
    touch($source . '/subdir/nested.txt');

    $this->helper->copyDirectory($source, $dest);

    expect(is_dir($dest))->toBeTrue();
    expect(file_exists($dest . '/file.txt'))->toBeTrue();
    expect(is_dir($dest . '/subdir'))->toBeTrue();
    expect(file_exists($dest . '/subdir/nested.txt'))->toBeTrue();
});

test('findFiles handles empty result', function () {
    $result = $this->helper->findFiles('*.nonexistent', $this->tempDir);
    expect($result)->toBe([]);
});

test('uniqueFilename with counter placeholder', function () {
    // Create some existing files
    touch($this->tempDir . '/file_001.txt');
    touch($this->tempDir . '/file_002.txt');

    $filename = $this->helper->uniqueFilename($this->tempDir, 'file_{counter}.txt');

    // The method should find the next available counter
    expect(file_exists($filename))->toBeFalse();
    expect(str_contains($filename, 'file_'))->toBeTrue();
});