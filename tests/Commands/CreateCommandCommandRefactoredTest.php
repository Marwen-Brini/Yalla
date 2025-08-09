<?php

declare(strict_types=1);

use Yalla\Commands\CreateCommandCommand;
use Yalla\Output\Output;

class TestableCreateCommandCommand extends CreateCommandCommand
{
    private bool $fileExistsReturn = false;

    private bool $createDirectoryReturn = true;

    private bool $writeFileReturn = true;

    public function setFileExistsReturn(bool $value): void
    {
        $this->fileExistsReturn = $value;
    }

    public function setCreateDirectoryReturn(bool $value): void
    {
        $this->createDirectoryReturn = $value;
    }

    public function setWriteFileReturn(bool $value): void
    {
        $this->writeFileReturn = $value;
    }

    protected function fileExists(string $path): bool
    {
        return $this->fileExistsReturn;
    }

    protected function createDirectory(string $dir): bool
    {
        return $this->createDirectoryReturn;
    }

    protected function writeFile(string $path, string $content): bool
    {
        return $this->writeFileReturn;
    }
}

it('handles directory creation failure', function () {
    $command = new TestableCreateCommandCommand;
    $command->setCreateDirectoryReturn(false);

    $output = new Output;

    $input = [
        'command' => 'create:command',
        'arguments' => ['test'],
        'options' => [],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(1);
    expect($capturedOutput)->toContain('Failed to create directory');
});

it('handles file write failure', function () {
    $command = new TestableCreateCommandCommand;
    $command->setWriteFileReturn(false);

    $output = new Output;

    $input = [
        'command' => 'create:command',
        'arguments' => ['test'],
        'options' => [],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(1);
    expect($capturedOutput)->toContain('Failed to write file');
});

it('successfully creates command when all operations succeed', function () {
    $command = new TestableCreateCommandCommand;

    $output = new Output;

    $input = [
        'command' => 'create:command',
        'arguments' => ['test'],
        'options' => [],
    ];

    ob_start();
    $result = $command->execute($input, $output);
    $capturedOutput = ob_get_clean();

    expect($result)->toBe(0);
    expect($capturedOutput)->toContain('Command created successfully');
    expect($capturedOutput)->toContain('Next steps:');
});

it('tests the actual file system methods', function () {
    $command = new CreateCommandCommand;

    // Test fileExists with real file
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('fileExists');
    $method->setAccessible(true);

    expect($method->invoke($command, __FILE__))->toBeTrue();
    expect($method->invoke($command, '/nonexistent/file.php'))->toBeFalse();

    // Test createDirectory with temp directory
    $method = $reflection->getMethod('createDirectory');
    $method->setAccessible(true);

    $tempDir = sys_get_temp_dir().'/yalla_test_'.uniqid();
    expect($method->invoke($command, $tempDir))->toBeTrue();
    expect(is_dir($tempDir))->toBeTrue();

    // Test with existing directory
    expect($method->invoke($command, $tempDir))->toBeTrue();

    rmdir($tempDir);

    // Test writeFile
    $method = $reflection->getMethod('writeFile');
    $method->setAccessible(true);

    $tempFile = sys_get_temp_dir().'/yalla_test_file_'.uniqid().'.txt';
    expect($method->invoke($command, $tempFile, 'test content'))->toBeTrue();
    expect(file_get_contents($tempFile))->toBe('test content');

    unlink($tempFile);
});
