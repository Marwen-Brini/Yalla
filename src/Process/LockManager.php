<?php

declare(strict_types=1);

namespace Yalla\Process;

use RuntimeException;

/**
 * Lock file manager for preventing concurrent operations
 *
 * Provides cross-platform lock management with stale lock detection
 * and automatic cleanup
 *
 * @package Yalla\Process
 */
class LockManager
{
    /**
     * Directory for storing lock files
     */
    protected string $lockDirectory;

    /**
     * Active locks held by this instance
     */
    protected array $locks = [];

    /**
     * Default maximum age for stale locks (seconds)
     */
    protected int $defaultMaxAge = 3600;

    /**
     * Create a new LockManager instance
     *
     * @param string|null $lockDirectory Directory for lock files
     */
    public function __construct(?string $lockDirectory = null)
    {
        $this->lockDirectory = $lockDirectory ?? sys_get_temp_dir() . '/yalla-locks';

        if (!is_dir($this->lockDirectory)) {
            if (!mkdir($this->lockDirectory, 0755, true) && !is_dir($this->lockDirectory)) {
                throw new RuntimeException("Failed to create lock directory: {$this->lockDirectory}");
            }
        }
    }

    /**
     * Acquire a lock with timeout
     *
     * @param string $name Lock name
     * @param int $timeout Timeout in seconds
     * @param bool $blocking Whether to wait for lock
     * @return bool Success status
     */
    public function acquire(string $name, int $timeout = 300, bool $blocking = true): bool
    {
        $lockFile = $this->getLockPath($name);
        $startTime = \time();

        while (true) {
            // Try to create lock file atomically with proper error handling
            $oldErrorHandler = set_error_handler(function() { return true; });
            $handle = fopen($lockFile, 'x');
            restore_error_handler();

            if ($handle !== false) {
                // Lock acquired successfully
                $lockData = $this->createLockData();
                fwrite($handle, json_encode($lockData, JSON_PRETTY_PRINT));
                fclose($handle);

                $this->locks[$name] = $lockFile;

                // Register shutdown function for cleanup
                register_shutdown_function([$this, 'release'], $name);

                return true;
            }

            // Check if existing lock is stale
            if ($this->isStale($name)) {
                $this->forceRelease($name);
                continue;
            }

            // Non-blocking mode returns immediately
            if (!$blocking) {
                return false;
            }

            // Check timeout
            if (\time() - $startTime >= $timeout) {
                return false;
            }

            // Wait before retry
            usleep(100000); // 100ms
        }
    }

    /**
     * Try to acquire lock (non-blocking)
     *
     * @param string $name Lock name
     * @return bool Success status
     */
    public function tryAcquire(string $name): bool
    {
        return $this->acquire($name, 0, false);
    }

    /**
     * Release a lock
     *
     * @param string $name Lock name
     * @return bool Success status
     */
    public function release(string $name): bool
    {
        $lockFile = $this->getLockPath($name);

        if (!file_exists($lockFile)) {
            unset($this->locks[$name]);
            return true;
        }

        // Verify we own the lock
        $data = $this->getLockInfo($name);
        $currentPid = function_exists('getpid') ? \getpid() :
                      (function_exists('getmypid') ? \getmypid() : null);

        if ($data && isset($data['pid']) && $data['pid'] === $currentPid) {
            if (@unlink($lockFile)) {
                unset($this->locks[$name]);
                return true;
            }
            return false;
        }

        // Not our lock
        return false;
    }

    /**
     * Force release a lock (dangerous - use with caution!)
     *
     * @param string $name Lock name
     * @return bool Success status
     */
    public function forceRelease(string $name): bool
    {
        $lockFile = $this->getLockPath($name);

        if (file_exists($lockFile)) {
            if (@unlink($lockFile)) {
                unset($this->locks[$name]);
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * Check if lock exists and is valid
     *
     * @param string $name Lock name
     * @return bool
     */
    public function isLocked(string $name): bool
    {
        $lockFile = $this->getLockPath($name);

        if (!file_exists($lockFile)) {
            return false;
        }

        // Check if lock is stale
        if ($this->isStale($name)) {
            $this->forceRelease($name);
            return false;
        }

        return true;
    }

    /**
     * Check if lock is stale
     *
     * @param string $name Lock name
     * @param int|null $maxAge Maximum age in seconds
     * @return bool
     */
    public function isStale(string $name, ?int $maxAge = null): bool
    {
        $data = $this->getLockInfo($name);

        if (!$data) {
            return true;
        }

        $maxAge = $maxAge ?? $this->defaultMaxAge;

        // Check age
        if (isset($data['time']) && (\time() - $data['time'] > $maxAge)) {
            return true;
        }

        // Check if process is still running
        if (isset($data['pid'])) {
            return !$this->isProcessRunning($data['pid'], $data['host'] ?? null);
        }

        return false;
    }

    /**
     * Check if process is running
     *
     * @param int $pid Process ID
     * @param string|null $host Hostname
     * @return bool
     */
    protected function isProcessRunning(int $pid, ?string $host = null): bool
    {
        // Check hostname if provided
        if ($host !== null && $host !== \gethostname()) {
            // Can't check process on remote host, assume it's running
            return true;
        }

        // Unix/Linux/Mac
        if (function_exists('posix_kill')) {
            return posix_kill($pid, 0);
        }

        // Windows
        // @codeCoverageIgnoreStart
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec("tasklist /FI \"PID eq {$pid}\" 2>nul", $output);
            return count($output) > 1;
        }

        // Fallback: check /proc filesystem
        if (file_exists("/proc/{$pid}")) {
            return true;
        }

        // Can't determine, assume running
        return true;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get lock information
     *
     * @param string $name Lock name
     * @return array|null Lock data or null if not found
     */
    public function getLockInfo(string $name): ?array
    {
        $lockFile = $this->getLockPath($name);

        if (!file_exists($lockFile)) {
            return null;
        }

        $content = @file_get_contents($lockFile);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Wait for lock to be released
     *
     * @param string $name Lock name
     * @param int $timeout Timeout in seconds
     * @return bool True if lock was released, false if timeout
     */
    public function wait(string $name, int $timeout = 300): bool
    {
        $startTime = \time();

        while ($this->isLocked($name)) {
            if (\time() - $startTime >= $timeout) {
                return false;
            }

            sleep(1);
        }

        return true;
    }

    /**
     * List all active locks
     *
     * @return array Array of lock name => lock info
     */
    public function listLocks(): array
    {
        $locks = [];
        $files = glob($this->lockDirectory . '/*.lock');

        // @codeCoverageIgnoreStart
        if ($files === false) {
            return [];
        }
        // @codeCoverageIgnoreEnd

        foreach ($files as $file) {
            $name = basename($file, '.lock');
            $info = $this->getLockInfo($name);

            if ($info && !$this->isStale($name)) {
                $locks[$name] = $info;
            }
        }

        return $locks;
    }

    /**
     * Clear all stale locks
     *
     * @param int|null $maxAge Maximum age in seconds
     * @return int Number of locks cleared
     */
    public function clearStale(?int $maxAge = null): int
    {
        $cleared = 0;
        $files = glob($this->lockDirectory . '/*.lock');

        // @codeCoverageIgnoreStart
        if ($files === false) {
            return 0;
        }
        // @codeCoverageIgnoreEnd

        foreach ($files as $file) {
            $name = basename($file, '.lock');

            if ($this->isStale($name, $maxAge)) {
                if ($this->forceRelease($name)) {
                    $cleared++;
                }
            }
        }

        return $cleared;
    }

    /**
     * Get lock file path
     *
     * @param string $name Lock name
     * @return string Lock file path
     */
    protected function getLockPath(string $name): string
    {
        // Sanitize name for filesystem
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        return $this->lockDirectory . '/' . $safeName . '.lock';
    }

    /**
     * Create lock data
     *
     * @return array Lock metadata
     */
    protected function createLockData(): array
    {
        // Use getmypid() as fallback if getpid() doesn't exist
        $pid = function_exists('getpid') ? \getpid() :
               (function_exists('getmypid') ? \getmypid() : \uniqid());

        return [
            'pid' => $pid,
            'time' => \time(),
            'host' => function_exists('gethostname') ? \gethostname() : 'localhost',
            'user' => function_exists('get_current_user') ? \get_current_user() : 'unknown',
            'command' => $_SERVER['argv'] ?? [],
            'php_version' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
        ];
    }

    /**
     * Release all locks on destruction
     */
    public function __destruct()
    {
        foreach ($this->locks as $name => $path) {
            $this->release($name);
        }
    }

    /**
     * Get the lock directory path
     *
     * @return string
     */
    public function getLockDirectory(): string
    {
        return $this->lockDirectory;
    }

    /**
     * Set default max age for stale locks
     *
     * @param int $seconds Max age in seconds
     * @return void
     */
    public function setDefaultMaxAge(int $seconds): void
    {
        $this->defaultMaxAge = $seconds;
    }

    /**
     * Get human-readable lock status
     *
     * @param string $name Lock name
     * @return string Status description
     */
    public function getLockStatus(string $name): string
    {
        $lockFile = $this->getLockPath($name);

        // Check if lock file exists first (before isLocked which may clean stale files)
        if (!file_exists($lockFile)) {
            return 'Not locked';
        }

        // Try to get lock info
        $info = $this->getLockInfo($name);
        if (!$info) {
            return 'Locked (no info available)';
        }

        // If we have info but isLocked says it's not locked (stale), it's been cleaned up
        if (!$this->isLocked($name)) {
            return 'Not locked';
        }

        $age = \time() - ($info['time'] ?? 0);
        $ageStr = $this->formatDuration($age);

        return sprintf(
            'Locked by PID %d on %s (%s ago)',
            $info['pid'] ?? 0,
            $info['host'] ?? 'unknown',
            $ageStr
        );
    }

    /**
     * Format duration in human-readable format
     *
     * @param int $seconds Duration in seconds
     * @return string Formatted duration
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $seconds = $seconds % 60;
            return "{$minutes}m {$seconds}s";
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return "{$hours}h {$minutes}m";
    }

    /**
     * Check if we own a lock
     *
     * @param string $name Lock name
     * @return bool
     */
    public function ownsLock(string $name): bool
    {
        if (!$this->isLocked($name)) {
            return false;
        }

        $info = $this->getLockInfo($name);
        // @codeCoverageIgnoreStart
        if (!$info) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        $currentPid = function_exists('getpid') ? \getpid() :
                      (function_exists('getmypid') ? \getmypid() : null);
        $currentHost = function_exists('gethostname') ? \gethostname() : 'localhost';

        return $info['pid'] === $currentPid &&
               $info['host'] === $currentHost;
    }

    /**
     * Refresh lock timestamp to prevent it from becoming stale
     *
     * @param string $name Lock name
     * @return bool Success status
     */
    public function refresh(string $name): bool
    {
        if (!$this->ownsLock($name)) {
            return false;
        }

        $lockFile = $this->getLockPath($name);
        $lockData = $this->createLockData();

        return file_put_contents($lockFile, json_encode($lockData, JSON_PRETTY_PRINT)) !== false;
    }
}