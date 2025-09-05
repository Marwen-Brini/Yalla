# Changelog

All notable changes to `yalla` will be documented in this file.

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
