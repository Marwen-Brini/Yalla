# Changelog

All notable changes to `yalla` will be documented in this file.

## v2.0.0 - 2025-10-01

### 🎉 Major Release - Production Ready

Version 2.0 represents a massive evolution of Yalla CLI, transforming it from a basic CLI framework into a production-ready, enterprise-grade command-line framework. This release introduces 10+ major features and architectural improvements.

### Added

#### Async Command Execution
- **SupportsAsync Trait**: Enable asynchronous command execution with promises
  - `executeAsync()` method for async command execution
  - `runParallel()` method for parallel operation execution
  - Configurable timeouts with `$asyncTimeout` property
  - Progress callbacks for long-running async tasks
  - Automatic --async option registration
  - Promise-based architecture with `Promise` class
  - Error handling with exception propagation
  - `AsyncCommandInterface` for async command contracts

#### Signal Handling (Unix/Linux)
- **HandlesSignals Trait**: Graceful shutdown and cleanup on interrupt signals
  - `onSignal()` method for custom signal handlers
  - `onInterrupt()` shortcut for SIGINT (Ctrl+C)
  - `onTerminate()` shortcut for SIGTERM
  - `onCommonSignals()` to register multiple handlers at once
  - `dispatchSignals()` for manual signal dispatch
  - `registerDefaultInterruptHandler()` for standard interrupt behavior
  - `registerGracefulShutdown()` for clean termination
  - `removeSignalHandler()` and `removeAllSignalHandlers()` for cleanup
  - Automatic cleanup on command completion
  - Platform detection (pcntl extension required)

#### Command Middleware System
- **HasMiddleware Trait**: Authentication, logging, timing, and custom middleware
  - `middleware()` method to add middleware to commands
  - `clearMiddleware()` to remove all middleware
  - `getMiddlewarePipeline()` to access the pipeline
- **MiddlewarePipeline Class**: Manages middleware execution order
  - Priority-based middleware execution
  - `add()`, `addMultiple()`, `remove()`, `clear()` methods
  - `execute()` method for running the middleware chain
  - Conditional middleware with `condition` parameter
  - Automatic sorting by priority (higher runs first)
- **Built-in Middleware**:
  - `TimingMiddleware`: Tracks command execution time
  - `LoggingMiddleware`: Logs command execution details
  - `AuthenticationMiddleware`: Token-based authentication example
- **MiddlewareInterface**: Contract for custom middleware
  - `handle()` method for middleware logic
  - `getPriority()` method for execution order

#### Dry Run Mode
- **DryRunnable Trait**: Preview operations without executing them
  - `setDryRun()` and `isDryRun()` for mode control
  - `executeOrSimulate()` to run or simulate operations
  - `simulateOperation()` for operation preview
  - `executeOperation()` for actual execution with timing
  - `getDryRunLog()` returns log of simulated operations
  - `getDryRunSummary()` returns formatted summary
  - `showDryRunSummary()` displays summary to user
  - `clearDryRunLog()` to reset the log
  - Context support for verbose dry run information
  - Execution time tracking for operations
  - Automatic --dry-run option support

#### Environment Management
- **Environment Class**: .env file support with variable expansion
  - Load multiple .env files (e.g., `.env`, `.env.local`)
  - Variable expansion: `${VAR_NAME}` syntax
  - Type-safe getters: `get()`, `getInt()`, `getFloat()`, `getBool()`, `getArray()`
  - Environment detection: `isProduction()`, `isDevelopment()`, `isStaging()`, `isDebug()`
  - `set()`, `has()`, `getAll()`, `clear()`, `reload()` methods
  - Support for quoted values and comments
  - Special value parsing: `true`, `false`, `null`, `empty`
  - Default values for missing variables
  - System environment variable integration
  - Required variable validation with `getRequired()`

#### File System Helpers
- **FileHelper Class**: Safe file operations and utilities
  - `safeWrite()`: Atomic writes with optional backup
  - `uniqueFilename()`: Generate unique filenames with patterns
    - Supports `{timestamp}`, `{date}`, `{unique}`, `{counter}` placeholders
    - Custom replacement support
  - `ensureDirectoryExists()`: Create directories with permissions
  - `copyDirectory()`: Recursive directory copying
  - `deleteDirectory()`: Recursive directory deletion
  - `findFiles()`: Pattern-based file search with recursion
  - `relativePath()`: Calculate relative paths between directories
  - `humanFilesize()`: Human-readable file sizes (B, KB, MB, GB, etc.)
  - `isAbsolutePath()`: Path type detection
  - `makeAbsolute()`: Convert relative to absolute paths
  - `getExtension()`, `getFilenameWithoutExtension()`: Path utilities
  - `readLines()`, `writeLines()`: Line-based file operations

#### Stub Generator
- **StubGenerator Class**: Template-based code generation
  - `registerStub()`: Register individual templates
  - `registerStubDirectory()`: Load all templates from directory
  - `render()`: Process templates with variables
  - `renderString()`: Process template strings directly
  - `generate()`: Create files from templates
  - **Template Features**:
    - Variable replacement: `{{ variable }}`
    - Conditionals: `@if(condition)...@endif`, `@unless(condition)...@endunless`
    - Loops: `@each(array as item)...@endeach`
    - Nested conditionals support
    - `@first` flag for first item in loops
    - `@index` for loop iteration number
    - Case transformations: `{{ variable|upper }}`, `{{ variable|lower }}`
  - Built-in stubs for commands, migrations, and models

#### Process Locking
- **LockManager Class**: Prevent concurrent command execution
  - `acquire()`: Acquire lock with timeout support
  - `tryAcquire()`: Non-blocking lock acquisition
  - `release()`: Release owned locks
  - `forceRelease()`: Force release any lock (admin)
  - `isLocked()`: Check if lock exists
  - `isStale()`: Detect abandoned locks
  - `getLockInfo()`: Get lock metadata (pid, host, timestamp)
  - `wait()`: Wait for lock to be released
  - `listLocks()`: Get all active locks
  - `clearStale()`: Remove old locks
  - `getLockStatus()`: Human-readable lock status
  - `ownsLock()`: Check lock ownership
  - `refresh()`: Update lock timestamp
  - Cross-platform process detection (Windows/Unix)
  - Automatic cleanup on destruction

#### Command Aliases
- **Command Aliasing**: Create shortcuts for commands
  - `setAliases()` method on Command class
  - `addAlias()` method to add individual aliases
  - `getAliases()`, `hasAlias()` methods
  - `Application::alias()` method for fluent API
  - CommandRegistry automatic alias resolution
  - Multiple aliases per command support

#### Exit Codes
- **ExitCodes Interface**: Standard exit codes with descriptions
  - Standard codes: SUCCESS (0), ERROR (1), INVALID_ARGUMENT (2)
  - Extended codes: UNAVAILABLE (69), PERMISSION_DENIED (77), CONFIG_ERROR (78)
  - `getExitCodeDescription()`: Get human-readable descriptions
  - `returnWithCode()`: Return with message and code
  - `returnSuccess()`, `returnError()`: Convenience methods
  - `mapExceptionToExitCode()`: Map exceptions to codes
  - `handleException()`: Exception handling with proper exit codes
  - Debug mode support for stack traces

#### Command Signatures
- **HasSignature Trait**: Laravel-style signature parsing
  - `$signature` property for defining command syntax
  - Automatic argument and option parsing
  - `argument()` and `option()` helper methods
  - Support for required/optional arguments
  - Support for argument default values
  - Support for array arguments: `{files*}`
  - Support for optional options: `{--force}`
  - Support for options with values: `{--tag=latest}`
  - Support for option shortcuts: `{--force|-f}`

#### Enhanced Output
- **Output Sections**: Dynamic output updates
  - `section()`: Create named output sections
  - `OutputSection::writeln()`: Write to section
  - `OutputSection::overwrite()`: Replace section content
  - `OutputSection::clear()`: Clear section
- **Semantic Output Methods**:
  - `success()`, `error()`, `warning()`, `info()` with icons
  - `verbose()`, `debug()` with verbosity level control
- **Enhanced Features**:
  - `withTimestamps()`: Add timestamps to output
  - `logQuery()`: SQL query logging with timing
  - `startGroup()`, `endGroup()`: Grouped output
  - Verbosity level support (quiet, normal, verbose, very verbose, debug)
  - `when()`: Conditional output
  - SQL interpolation for readable query logs

#### Additional Enhancements
- **Command Class Improvements**:
  - Support for multiple traits (SupportsAsync, HandlesSignals, HasMiddleware, DryRunnable, HasSignature)
  - Better error handling and exception mapping
  - Chainable method calls for fluent API
  - Enhanced argument and option handling
- **Application Class**:
  - Command alias support
  - Better command resolution
  - Enhanced error handling
- **Testing Infrastructure**:
  - 100% code coverage maintained
  - 685+ passing tests
  - Platform-specific test coverage
  - Comprehensive integration tests
  - Mock classes for testing platform-specific features

### Fixed
- `humanFilesize()` method now properly formats file sizes without trailing zeros
  - "1.0 KB" is now displayed as "1 KB"
  - "1.5 MB" correctly shows decimal when needed
  - Uses `rtrim()` to clean up number formatting

### Changed
- Version bumped to 2.0.0 for major release
- Updated all documentation with v2.0 features
- Enhanced examples with new functionality
- Improved test coverage across all new features

### Breaking Changes
None - v2.0 is fully backward compatible with v1.x. All new features are opt-in via traits and classes.

## v1.5.0 - 2025-01-27

### Added

- **Progress Indicators**: Comprehensive progress tracking for long-running tasks
  - **Progress Bars**: Multiple formats (normal, verbose, detailed, minimal, memory)
    - Automatic time estimation and elapsed time tracking
    - Memory usage monitoring
    - Custom format templates
    - Configurable redraw frequency for performance
  - **Spinners**: Animated indicators with 6 built-in frame styles
    - dots, line, pipe, arrow, bounce, box animations
    - Dynamic message updates during execution
    - Success, error, warning, and info completion states
  - **Step Indicators**: Multi-step process tracking
    - Visual step-by-step progress display
    - Individual step timing
    - Complete, fail, skip, and running states
    - Automatic summary with timing information

- **Output Integration**: Seamless integration with Output class
  - `$output->createProgressBar()`
  - `$output->createSpinner()`
  - `$output->steps()`

### Changed

- Updated version to 1.5.0 across all documentation
- Enhanced Output class with progress indicator factory methods

### Fixed

- Fixed progress bar display issue where `setMessage()` would output before `start()` was called
- Added `started` property to prevent premature display

## v1.4.0 - 2025-01-25

### Added

- **Advanced Table Formatting**: Professional table rendering system
  - Multiple border styles (classic, modern, none, double, rounded, heavy)
  - Column alignment options (left, right, center)
  - Cell formatters for custom data transformation
  - Row filtering and sorting capabilities
  - Markdown table export format
  - Emoji and Unicode support

- **Migration Tables**: Specialized formatter for database migrations
  - Status indicators with colors
  - Batch filtering
  - Summary statistics

## v1.3.0 - 2025-09-05

This version adds:

- Multiple display modes (compact, verbose, json, dump)
- Enhanced object display
- Semicolon support
- Better ORM handling

The REPL shows "Yalla REPL v1.3.0" in its welcome message.

## [Unreleased]

## 1.3.0 - 2025-01-10

### Added

- **Multiple Display Modes**: Added configurable output formats for REPL
  - `compact` mode: Default concise, colorized output
  - `verbose` mode: Detailed object and array information with metadata
  - `json` mode: JSON representation for data structures
  - `dump` mode: Traditional PHP var_dump() style output
  - New `:mode [mode]` command to switch modes dynamically
  
- **Enhanced Object Display**: Improved formatting for objects
  - Shows public properties inline for better readability
  - Displays `__toString()` results when available
  - Smart truncation for long strings
  
- **Better ORM Support**: Fixed display issues with protected/private properties
  - Arrays of ORM models now display correctly as lists
  - No more empty table boxes for objects without public properties
  

### Fixed

- **Semicolon Support**: Fixed parse errors when using trailing semicolons
  - Commands like `Post::count();` now work naturally
  - Variable assignments with semicolons (`$x = 5;`) are properly handled
  
- **Object Display**: Fixed `strrchr()` warning with objects without namespaces
- **Mixed Arrays**: Properly handles arrays containing both arrays and objects

### Changed

- Clarified PHP version support (8.1 to 8.4) in composer.json and documentation
- Optimized GitHub Actions workflows to prevent job cancellation
  - Reduced matrix combinations from 16 to 7 jobs
  - Added separate workflow for code coverage
  - Set max-parallel jobs to 4 to avoid GitHub limits
  

## 1.1.0 - 2025-01-09

### Added

- **Command Scaffolding**: New `create:command` to generate command boilerplate
  - Support for custom class names with `--class` option
  - Custom directory support with `--dir` option
  - Force overwrite with `--force` flag
  
- **100% Test Coverage**: Comprehensive test suite using Pest PHP
- **Improved Architecture**:
  - Refactored Output class for better testability
  - Extracted platform detection methods
  - Refactored CreateCommandCommand with isolated file operations
  
- **Enhanced Testing**:
  - All tests converted to Pest format
  - Added mock classes for testing platform-specific code
  - Fixed PHPUnit deprecation warnings
  - Resolved risky test warnings with proper output buffering
  

### Changed

- Improved cross-platform color support detection
- Updated PHPUnit configuration to latest schema
- Enhanced GitHub Actions workflow with coverage requirements

### Fixed

- Directory creation error handling
- Test coverage gaps in platform-specific code
- PHPUnit XML configuration deprecation

## 1.0.0 - 2025-01-08

### Added

- Initial release
- Command routing and registration
- Input parsing (commands, arguments, options)
- Colored terminal output with cross-platform support
- Table rendering with Unicode box drawing
- Built-in help and list commands
- Zero external dependencies
