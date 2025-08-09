# Changelog

All notable changes to `yalla` will be documented in this file.

## [Unreleased]

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