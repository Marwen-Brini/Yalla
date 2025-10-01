<?php

declare(strict_types=1);

namespace Yalla\Filesystem;

use RuntimeException;

/**
 * File system helper utilities
 *
 * Provides common file operations with safety features
 */
class FileHelper
{
    /**
     * Ensure directory exists with proper permissions
     *
     * @param  string  $path  Directory path
     * @param  int  $mode  Directory permissions
     * @throws RuntimeException If directory cannot be created
     */
    public function ensureDirectoryExists(string $path, int $mode = 0755): void
    {
        if (is_dir($path)) {
            return;
        }

        if (! mkdir($path, $mode, true) && ! is_dir($path)) {
            throw new RuntimeException("Failed to create directory: {$path}");
        }
    }

    /**
     * Generate unique filename with pattern
     *
     * @param  string  $directory  Directory path
     * @param  string  $pattern  Filename pattern (can include {timestamp}, {unique}, {counter})
     * @param  array  $replacements  Additional replacements for pattern
     * @return string Unique file path
     * @throws RuntimeException If directory doesn't exist
     */
    public function uniqueFilename(string $directory, string $pattern, array $replacements = []): string
    {
        $this->ensureDirectoryExists($directory);

        // Apply custom replacements first
        foreach ($replacements as $key => $value) {
            $pattern = str_replace('{'.$key.'}', (string) $value, $pattern);
        }

        // Handle special placeholders
        if (strpos($pattern, '{timestamp}') !== false) {
            $pattern = str_replace('{timestamp}', date('Y_m_d_His'), $pattern);
        }

        if (strpos($pattern, '{date}') !== false) {
            $pattern = str_replace('{date}', date('Y-m-d'), $pattern);
        }

        if (strpos($pattern, '{unique}') !== false) {
            $pattern = str_replace('{unique}', uniqid(), $pattern);
        }

        // Initial path
        $basePath = $directory.'/'.$pattern;
        $path = $basePath;

        // Handle {counter} placeholder or add counter if file exists
        if (strpos($pattern, '{counter}') !== false) {
            $counter = 1;
            do {
                $path = str_replace('{counter}', (string) $counter, $basePath);
                $counter++;
            } while (file_exists($path));
        } else {
            // Add counter suffix if file exists
            $counter = 1;
            while (file_exists($path)) {
                $info = pathinfo($basePath);
                $path = $info['dirname'].'/'.$info['filename'].'_'.$counter;
                if (isset($info['extension']) && $info['extension'] !== '') {
                    $path .= '.'.$info['extension'];
                }
                $counter++;
            }
        }

        return $path;
    }

    /**
     * Safe file write with backup option
     *
     * @param  string  $path  File path
     * @param  string  $content  File content
     * @param  bool  $backup  Create backup if file exists
     * @return bool Success status
     * @throws RuntimeException If write fails
     */
    public function safeWrite(string $path, string $content, bool $backup = true): bool
    {
        $directory = dirname($path);
        $this->ensureDirectoryExists($directory);

        // Create backup if requested and file exists
        if ($backup && file_exists($path)) {
            $backupPath = $this->createBackupPath($path);
            if (! copy($path, $backupPath)) {
                throw new RuntimeException("Failed to create backup: {$backupPath}");
            }
        }

        // Write to temporary file first for atomicity
        $tempPath = $path.'.tmp.'.uniqid();
        $result = file_put_contents($tempPath, $content, LOCK_EX);

        if ($result === false) {
            @unlink($tempPath);

            throw new RuntimeException("Failed to write temporary file: {$tempPath}");
        }

        // Atomic move
        if (! rename($tempPath, $path)) {
            @unlink($tempPath);

            throw new RuntimeException("Failed to move file to: {$path}");
        }

        return true;
    }

    /**
     * Create backup path for a file
     *
     * @param  string  $path  Original file path
     * @return string Backup file path
     */
    protected function createBackupPath(string $path): string
    {
        $directory = dirname($path);
        $filename = basename($path);
        $timestamp = date('YmdHis');

        return $directory.'/.'.$filename.'.backup.'.$timestamp;
    }

    /**
     * Find files matching pattern
     *
     * @param  string  $directory  Directory to search
     * @param  string  $pattern  Glob pattern (e.g., "*.php", "test_*.json")
     * @param  bool  $recursive  Search subdirectories
     * @return array Array of file paths
     */
    public function findFiles(string $directory, string $pattern = '*', bool $recursive = true): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $globPath = $directory.'/'.$pattern;
        $foundFiles = glob($globPath);

        if ($foundFiles !== false) {
            $files = $foundFiles;
        }

        // Recursive search
        if ($recursive) {
            $subdirs = glob($directory.'/*', GLOB_ONLYDIR);
            if ($subdirs !== false) {
                foreach ($subdirs as $subdir) {
                    $files = array_merge($files, $this->findFiles($subdir, $pattern, true));
                }
            }
        }

        return $files;
    }

    /**
     * Get relative path from one path to another
     *
     * @param  string  $from  Source path
     * @param  string  $to  Target path
     * @return string Relative path
     */
    public function relativePath(string $from, string $to): string
    {
        // Normalize paths
        $from = rtrim(str_replace('\\', '/', $from), '/');
        $to = rtrim(str_replace('\\', '/', $to), '/');

        // If same path
        if ($from === $to) {
            return '.';
        }

        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);

        // Find common path length
        $common = 0;
        foreach ($fromParts as $i => $part) {
            if (isset($toParts[$i]) && $part === $toParts[$i]) {
                $common++;
            } else {
                break;
            }
        }

        // Build relative path
        $relativeParts = [];

        // Add .. for each directory we need to go up
        $upCount = count($fromParts) - $common;
        for ($i = 0; $i < $upCount; $i++) {
            $relativeParts[] = '..';
        }

        // Add remaining path parts
        for ($i = $common; $i < count($toParts); $i++) {
            $relativeParts[] = $toParts[$i];
        }

        return implode('/', $relativeParts) ?: '.';
    }

    /**
     * Copy directory recursively
     *
     * @param  string  $source  Source directory
     * @param  string  $destination  Destination directory
     * @param  bool  $overwrite  Overwrite existing files
     * @return bool Success status
     */
    public function copyDirectory(string $source, string $destination, bool $overwrite = false): bool
    {
        if (! is_dir($source)) {
            return false;
        }

        $this->ensureDirectoryExists($destination);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $destination.'/'.$iterator->getSubPathname();

            if ($item->isDir()) {
                $this->ensureDirectoryExists($targetPath);
            } else {
                if (! $overwrite && file_exists($targetPath)) {
                    continue;
                }

                if (! copy($item->getPathname(), $targetPath)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Delete directory recursively
     *
     * @param  string  $directory  Directory to delete
     * @return bool Success status
     */
    public function deleteDirectory(string $directory): bool
    {
        if (! is_dir($directory)) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $action = $fileinfo->isDir() ? 'rmdir' : 'unlink';
            if (! $action($fileinfo->getRealPath())) {
                return false;
            }
        }

        return rmdir($directory);
    }

    /**
     * Get file size in human-readable format
     *
     * @param  string  $path  File path
     * @param  int  $precision  Decimal precision
     * @return string Formatted size
     */
    public function humanFilesize(string $path, int $precision = 2): string
    {
        if (! file_exists($path)) {
            return '0 B';
        }

        $size = filesize($path);
        // @codeCoverageIgnoreStart
        if ($size === false) {
            return '0 B';
        }
        // @codeCoverageIgnoreEnd

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        $rounded = round($size, $precision);
        // Remove unnecessary trailing zeros and decimal point
        $formatted = rtrim(rtrim(number_format($rounded, $precision, '.', ''), '0'), '.');

        return $formatted.' '.$units[$i];
    }

    /**
     * Check if path is absolute
     *
     * @param  string  $path  Path to check
     */
    public function isAbsolutePath(string $path): bool
    {
        // Unix absolute path
        if (strpos($path, '/') === 0) {
            return true;
        }

        // Windows absolute path
        if (preg_match('/^[A-Z]:\\\\/i', $path)) {
            return true;
        }

        // Windows UNC path
        if (strpos($path, '\\\\') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Make path absolute
     *
     * @param  string  $path  Path to make absolute
     * @param  string|null  $base  Base directory (defaults to cwd)
     * @return string Absolute path
     */
    public function makeAbsolute(string $path, ?string $base = null): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        if ($base === null) {
            $base = getcwd();
        }

        return rtrim($base, '/\\').DIRECTORY_SEPARATOR.$path;
    }

    /**
     * Get file extension
     *
     * @param  string  $path  File path
     * @return string File extension (without dot)
     */
    public function getExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get filename without extension
     *
     * @param  string  $path  File path
     * @return string Filename without extension
     */
    public function getFilenameWithoutExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Read file lines as array
     *
     * @param  string  $path  File path
     * @param  bool  $skipEmpty  Skip empty lines
     * @return array Array of lines
     */
    public function readLines(string $path, bool $skipEmpty = false): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $flags = FILE_IGNORE_NEW_LINES;
        if ($skipEmpty) {
            $flags |= FILE_SKIP_EMPTY_LINES;
        }

        $lines = file($path, $flags);

        return $lines !== false ? $lines : [];
    }

    /**
     * Write lines to file
     *
     * @param  string  $path  File path
     * @param  array  $lines  Lines to write
     * @param  bool  $append  Append to file instead of overwriting
     * @return bool Success status
     */
    public function writeLines(string $path, array $lines, bool $append = false): bool
    {
        $content = implode(PHP_EOL, $lines);
        if (! empty($lines)) {
            $content .= PHP_EOL;
        }

        $flags = LOCK_EX;
        if ($append) {
            $flags |= FILE_APPEND;
        }

        return file_put_contents($path, $content, $flags) !== false;
    }
}
