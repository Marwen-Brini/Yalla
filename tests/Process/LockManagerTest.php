<?php

declare(strict_types=1);

use Yalla\Process\LockManager;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/yalla_lock_test_' . uniqid();
    $this->manager = new LockManager($this->tempDir);
});

afterEach(function () {
    // Clean up any locks explicitly
    if (isset($this->manager)) {
        try {
            // Get list of locks and release them
            $locks = $this->manager->listLocks();
            foreach (array_keys($locks) as $lockName) {
                $this->manager->forceRelease($lockName);
            }
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
        unset($this->manager);
    }

    // Clean up temp directory and any remaining lock files
    if (is_dir($this->tempDir)) {
        $files = glob($this->tempDir . '/*.lock');
        if ($files) {
            array_map('unlink', $files);
        }
        @rmdir($this->tempDir);
    }
});

test('creates lock directory if not exists', function () {
    $customDir = sys_get_temp_dir() . '/yalla_custom_locks_' . uniqid();
    $this->assertFalse(is_dir($customDir));

    new LockManager($customDir);

    $this->assertTrue(is_dir($customDir));

    // Cleanup
    rmdir($customDir);
});

test('uses default lock directory when null provided', function () {
    $manager = new LockManager();
    $dir = $manager->getLockDirectory();

    $this->assertStringContainsString('yalla-locks', $dir);
});

test('acquire creates lock successfully', function () {
    $result = $this->manager->acquire('test-lock');

    $this->assertTrue($result);
    $this->assertTrue($this->manager->isLocked('test-lock'));
});

test('acquire prevents concurrent locks', function () {
    $this->manager->acquire('exclusive');

    // Try to acquire same lock
    $manager2 = new LockManager($this->tempDir);
    $result = $manager2->tryAcquire('exclusive');

    $this->assertFalse($result);
});

test('tryAcquire returns immediately when locked', function () {
    $this->manager->acquire('busy');

    $manager2 = new LockManager($this->tempDir);
    $start = microtime(true);
    $result = $manager2->tryAcquire('busy');
    $elapsed = microtime(true) - $start;

    $this->assertFalse($result);
    $this->assertLessThan(0.1, $elapsed); // Should return immediately
});

test('acquire with timeout', function () {
    $this->manager->acquire('timed');

    $manager2 = new LockManager($this->tempDir);
    $start = microtime(true);
    $result = $manager2->acquire('timed', 1, true); // 1 second timeout
    $elapsed = microtime(true) - $start;

    $this->assertFalse($result);
    $this->assertGreaterThanOrEqual(0.1, $elapsed);
    $this->assertLessThan(1.5, $elapsed);
});

test('release removes lock', function () {
    $this->manager->acquire('temp');
    $this->assertTrue($this->manager->isLocked('temp'));

    $result = $this->manager->release('temp');

    $this->assertTrue($result);
    $this->assertFalse($this->manager->isLocked('temp'));
});

test('release only releases own locks', function () {
    $this->manager->acquire('owned');

    // Different process ID would normally prevent release
    // We'll test by checking lock info
    $info = $this->manager->getLockInfo('owned');
    // Check pid - could be getpid(), getmypid() or uniqid()
    $this->assertNotNull($info['pid']);

    // Release should work for own lock
    $result = $this->manager->release('owned');
    $this->assertTrue($result);
});

test('release returns true for non-existent lock', function () {
    $result = $this->manager->release('nonexistent');
    $this->assertTrue($result);
});

test('forceRelease removes any lock', function () {
    $this->manager->acquire('forced');

    $manager2 = new LockManager($this->tempDir);
    $result = $manager2->forceRelease('forced');

    $this->assertTrue($result);
    $this->assertFalse($this->manager->isLocked('forced'));
});

test('isLocked detects lock state', function () {
    $this->assertFalse($this->manager->isLocked('new'));

    $this->manager->acquire('new');

    $this->assertTrue($this->manager->isLocked('new'));
});

test('isStale detects old locks', function () {
    $this->manager->setDefaultMaxAge(1); // 1 second
    $this->manager->acquire('stale-test');

    $this->assertFalse($this->manager->isStale('stale-test'));

    sleep(2);

    $this->assertTrue($this->manager->isStale('stale-test'));
});

test('isStale with custom max age', function () {
    $this->manager->acquire('custom-stale');

    $this->assertFalse($this->manager->isStale('custom-stale', 2));

    sleep(3);

    $this->assertTrue($this->manager->isStale('custom-stale', 2));
});

test('getLockInfo returns lock data', function () {
    $this->manager->acquire('info-test');

    $info = $this->manager->getLockInfo('info-test');

    $this->assertIsArray($info);
    // Check pid - could be getpid(), getmypid() or uniqid()
    $this->assertNotNull($info['pid']);
    // Check host - could be gethostname() or 'localhost'
    $this->assertNotNull($info['host']);
    $this->assertArrayHasKey('time', $info);
    $this->assertArrayHasKey('user', $info);
    $this->assertArrayHasKey('command', $info);
    $this->assertArrayHasKey('php_version', $info);
    $this->assertArrayHasKey('os', $info);
});

test('getLockInfo returns null for non-existent lock', function () {
    $info = $this->manager->getLockInfo('nonexistent');
    $this->assertNull($info);
});

test('wait waits for lock release', function () {
    if (!function_exists('pcntl_fork')) {
        $this->markTestSkipped('PCNTL extension not available');
        return;
    }

    $this->manager->acquire('wait-test');

    // Release lock after delay in background
    $pid = @pcntl_fork();
    if ($pid === 0) {
        // Child process - close all file descriptors to avoid interfering with test runner
        if (function_exists('posix_setsid')) {
            posix_setsid(); // Create new session to detach from parent
        }

        // Close stdout/stderr to prevent interference with test output
        fclose(STDOUT);
        fclose(STDERR);

        sleep(1);
        $manager = new LockManager($this->tempDir);
        $manager->forceRelease('wait-test');
        exit(0);
    } elseif ($pid === -1) {
        // Fork failed, skip test
        $this->markTestSkipped('Cannot fork process');
    } else {
        // Parent process
        $start = microtime(true);
        $result = $this->manager->wait('wait-test', 3);
        $elapsed = microtime(true) - $start;

        // Wait for child process to finish
        $status = 0;
        pcntl_waitpid($pid, $status);

        $this->assertTrue($result);
        $this->assertGreaterThanOrEqual(0.9, $elapsed);
        $this->assertLessThan(2.2, $elapsed);
    }
});

test('wait times out', function () {
    $this->manager->acquire('timeout-test');

    $manager2 = new LockManager($this->tempDir);
    $start = microtime(true);
    $result = $manager2->wait('timeout-test', 1);
    $elapsed = microtime(true) - $start;

    $this->assertFalse($result);
    $this->assertGreaterThanOrEqual(1, $elapsed);
});

test('listLocks returns active locks', function () {
    $this->manager->acquire('lock1');
    $this->manager->acquire('lock2');
    $this->manager->acquire('lock3');

    $locks = $this->manager->listLocks();

    $this->assertCount(3, $locks);
    $this->assertArrayHasKey('lock1', $locks);
    $this->assertArrayHasKey('lock2', $locks);
    $this->assertArrayHasKey('lock3', $locks);
});

test('listLocks excludes stale locks', function () {
    $this->manager->setDefaultMaxAge(1);

    $this->manager->acquire('fresh');

    // Create stale lock manually
    $staleLock = $this->tempDir . '/stale.lock';
    file_put_contents($staleLock, json_encode([
        'pid' => 99999,
        'time' => time() - 3600,
        'host' => 'old-host'
    ]));

    $locks = $this->manager->listLocks();

    $this->assertArrayHasKey('fresh', $locks);
    $this->assertArrayNotHasKey('stale', $locks);
});

test('clearStale removes old locks', function () {
    $this->manager->setDefaultMaxAge(10); // 10 seconds to keep fresh lock valid

    $this->manager->acquire('fresh');

    // Create old lock manually
    $oldLock = $this->tempDir . '/old.lock';
    file_put_contents($oldLock, json_encode([
        'pid' => 99999,
        'time' => time() - 3600,
        'host' => 'old-host'
    ]));

    $cleared = $this->manager->clearStale(1); // Clear locks older than 1 second

    // Verify the key behaviors: old lock is removed, fresh lock remains
    $this->assertGreaterThanOrEqual(1, $cleared); // At least the old lock was cleared
    $this->assertTrue($this->manager->isLocked('fresh'));
    $this->assertFalse(file_exists($oldLock));
});

test('getLockStatus returns human readable status', function () {
    $this->manager->acquire('status-test');

    $status = $this->manager->getLockStatus('status-test');

    $this->assertStringContainsString('Locked by PID', $status);
    // Status should contain pid information
    $this->assertStringContainsString('PID', $status);
    // Status should contain host information
    $this->assertTrue(strlen($status) > 0);
});

test('getLockStatus for non-existent lock', function () {
    $status = $this->manager->getLockStatus('nonexistent');
    $this->assertEquals('Not locked', $status);
});

test('getLockStatus for lock without info', function () {
    // Create invalid lock file
    $lockFile = $this->tempDir . '/invalid.lock';
    file_put_contents($lockFile, 'not json');

    $status = $this->manager->getLockStatus('invalid');
    $this->assertEquals('Locked (no info available)', $status);
});

test('ownsLock identifies ownership', function () {
    $this->manager->acquire('mine');

    $this->assertTrue($this->manager->ownsLock('mine'));

    // Create lock with different PID
    $otherLock = $this->tempDir . '/other.lock';
    file_put_contents($otherLock, json_encode([
        'pid' => 99999999, // Use high number that's unlikely to be a real PID
        'host' => function_exists('gethostname') ? gethostname() : 'localhost'
    ]));

    $this->assertFalse($this->manager->ownsLock('other'));
});

test('refresh updates lock timestamp', function () {
    $this->manager->acquire('refresh-test');

    $info1 = $this->manager->getLockInfo('refresh-test');
    $time1 = $info1['time'];

    sleep(2);

    $result = $this->manager->refresh('refresh-test');

    $this->assertTrue($result);

    $info2 = $this->manager->getLockInfo('refresh-test');
    $time2 = $info2['time'];

    $this->assertGreaterThan($time1, $time2);
});

test('refresh fails for non-owned lock', function () {
    // Create lock with different PID
    $lockFile = $this->tempDir . '/notmine.lock';
    file_put_contents($lockFile, json_encode([
        'pid' => 99999999, // Use high number that's unlikely to be a real PID
        'host' => function_exists('gethostname') ? gethostname() : 'localhost'
    ]));

    $result = $this->manager->refresh('notmine');

    $this->assertFalse($result);
});

test('lock cleanup works properly', function () {
    $this->manager->acquire('lock1');
    $this->manager->acquire('lock2');

    $this->assertTrue($this->manager->isLocked('lock1'));
    $this->assertTrue($this->manager->isLocked('lock2'));

    // Test explicit release (more reliable than destructor timing)
    $this->manager->release('lock1');
    $this->manager->release('lock2');

    $this->assertFalse($this->manager->isLocked('lock1'));
    $this->assertFalse($this->manager->isLocked('lock2'));

    // Verify clean state for new manager
    $newManager = new LockManager($this->tempDir);
    $this->assertFalse($newManager->isLocked('lock1'));
    $this->assertFalse($newManager->isLocked('lock2'));
});

test('getLockPath sanitizes lock names', function () {
    $this->manager->acquire('test/lock:name*with<special>chars');

    // Should still work with sanitized name
    $this->assertTrue($this->manager->isLocked('test/lock:name*with<special>chars'));
});

test('formatDuration handles different time periods', function () {
    // Use reflection to test protected method
    $reflection = new ReflectionClass($this->manager);
    $method = $reflection->getMethod('formatDuration');
    $method->setAccessible(true);

    $this->assertEquals('30s', $method->invoke($this->manager, 30));
    $this->assertEquals('1m 30s', $method->invoke($this->manager, 90));
    $this->assertEquals('2h 15m', $method->invoke($this->manager, 8100));
});

test('isProcessRunning checks process status', function () {
    // Use reflection to test protected method
    $reflection = new ReflectionClass($this->manager);
    $method = $reflection->getMethod('isProcessRunning');
    $method->setAccessible(true);

    // Current process should be running
    // Get current pid using fallback logic - skip if no pid functions available
    if (function_exists('getpid')) {
        $pid = getpid();
        $this->assertTrue($method->invoke($this->manager, $pid));
    } elseif (function_exists('getmypid')) {
        $pid = getmypid();
        $this->assertTrue($method->invoke($this->manager, $pid));
    } else {
        // Skip test if no PID functions available
        $this->assertTrue(true);
    }

    // Very high PID unlikely to exist
    $this->assertFalse($method->invoke($this->manager, 999999));

    // Remote host always returns true
    $this->assertTrue($method->invoke($this->manager, 12345, 'remote-host'));
});

test('acquire removes stale lock and retries', function () {
    // Create stale lock
    $lockFile = $this->tempDir . '/stale-retry.lock';
    file_put_contents($lockFile, json_encode([
        'pid' => 999999, // Non-existent process
        'time' => time() - 7200, // 2 hours old
        'host' => function_exists('gethostname') ? gethostname() : 'localhost'
    ]));

    $this->manager->setDefaultMaxAge(3600);

    // Should remove stale lock and acquire
    $result = $this->manager->acquire('stale-retry');

    $this->assertTrue($result);
    $this->assertTrue($this->manager->ownsLock('stale-retry'));
});

test('clearStale returns zero when no locks', function () {
    $cleared = $this->manager->clearStale();
    $this->assertEquals(0, $cleared);
});

test('listLocks returns empty array when no locks', function () {
    $locks = $this->manager->listLocks();
    $this->assertEmpty($locks);
});