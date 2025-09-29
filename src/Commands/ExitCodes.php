<?php

declare(strict_types=1);

namespace Yalla\Commands;

/**
 * Exit code constants for Yalla CLI commands
 *
 * Following POSIX standards and custom application codes
 *
 * @package Yalla\Commands
 */
interface ExitCodes
{
    // Standard POSIX exit codes
    const EXIT_SUCCESS = 0;           // Successful termination
    const EXIT_FAILURE = 1;           // General errors
    const EXIT_USAGE = 2;             // Misuse of shell command (e.g., invalid arguments)

    // Application-specific codes (64-113 reserved for custom use per sysexits.h)
    const EXIT_USAGE_ERROR = 64;      // Command line usage error
    const EXIT_DATAERR = 65;          // Data format error
    const EXIT_NOINPUT = 66;          // Cannot open input
    const EXIT_NOUSER = 67;           // Addressee unknown
    const EXIT_NOHOST = 68;           // Host name unknown
    const EXIT_UNAVAILABLE = 69;      // Service unavailable
    const EXIT_SOFTWARE = 70;         // Internal software error
    const EXIT_OSERR = 71;           // System error (e.g., can't fork)
    const EXIT_OSFILE = 72;          // Critical OS file missing
    const EXIT_CANTCREAT = 73;       // Can't create (user) output file
    const EXIT_IOERR = 74;           // Input/output error
    const EXIT_TEMPFAIL = 75;        // Temporary failure; user is invited to retry
    const EXIT_PROTOCOL = 76;        // Remote error in protocol
    const EXIT_NOPERM = 77;          // Permission denied
    const EXIT_CONFIG = 78;          // Configuration error

    // Custom application codes for migration system (80-88)
    const EXIT_LOCKED = 80;          // Resource is locked (e.g., concurrent migration running)
    const EXIT_TIMEOUT = 81;         // Operation timed out
    const EXIT_CANCELLED = 82;       // Operation cancelled by user
    const EXIT_VALIDATION = 83;      // Validation error
    const EXIT_MISSING_DEPS = 84;    // Missing dependencies
    const EXIT_NOT_FOUND = 85;       // Resource not found
    const EXIT_CONFLICT = 86;        // Resource conflict
    const EXIT_ROLLBACK = 87;        // Rollback occurred
    const EXIT_PARTIAL = 88;         // Partial completion

    // Command not found (bash standard)
    const EXIT_COMMAND_NOT_FOUND = 127;

    // Signal-related codes (128+n where n is signal number)
    const EXIT_SIGINT = 130;         // Script terminated by Control-C (128 + 2)
    const EXIT_SIGTERM = 143;        // Script terminated by SIGTERM (128 + 15)
}