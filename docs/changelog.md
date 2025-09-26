# Changelog

All notable changes to Yalla CLI will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- TBD

### Changed
- TBD

### Fixed
- TBD

## [1.4.0] - 2025-01-26

### Added
- **Advanced Table Formatting System**: Professional-grade table formatter with multiple border styles
- **Table Class**: New `Table` class with extensive formatting options
  - 7 border styles: Unicode, ASCII, Markdown, Double, Rounded, Compact, None
  - Column alignment support (left, center, right)
  - Emoji and Unicode character support with proper width calculation
  - Cell formatters for custom value transformation
  - Sorting and filtering capabilities
  - Row indexing with custom names
  - Fluent interface for method chaining
- **MigrationTable Class**: Specialized table for database migration systems
  - Built-in status formatting with emoji indicators
  - Batch filtering and status filtering
  - Migration summary reporting
  - Support for Laravel, Doctrine, and custom migration systems
- **Enhanced Output Methods**: New `createTable()` method for advanced table creation
- **Comprehensive Documentation**: Complete guides and API references for table formatting
- **100% Test Coverage**: Full test suite with 218+ tests covering all table features

### Enhanced
- **Table Rendering**: Upgraded from basic ASCII tables to professional formatting
- **Emoji Support**: Proper handling of emoji width in table cells and terminal display
- **Documentation**: Added dedicated table formatting guide and API references

### Technical Improvements
- **Width Calculation**: Smart emoji and Unicode character width detection
- **Alignment System**: Markdown-compatible alignment indicators for center and right alignment
- **Performance**: Optimized table rendering for large datasets
- **Memory Management**: Efficient cloning and data handling for table variations

### Breaking Changes
- None - all existing table functionality remains backward compatible

### Developer Experience
- **Fluent Interface**: Method chaining for clean, readable table building code
- **Type Safety**: Full type hints and strict types throughout
- **Examples**: Comprehensive example files and documentation samples

## [1.3.0] - 2025-01-14

### Added
- REPL (Read-Eval-Print-Loop) functionality
- Interactive PHP shell with history and autocomplete
- Multiple display modes (compact, verbose, JSON, dump)
- REPL extensions system
- Command history management
- Variable persistence in REPL sessions
- Shortcuts for frequently used classes
- Custom formatters for object display

### Changed
- Improved output formatting for better readability
- Enhanced Windows compatibility

### Fixed
- Color detection on various terminal emulators
- Table rendering with Unicode characters

## [1.2.0] - 2024-12-01

### Added
- Command scaffolding with `create:command`
- Automatic command generation from templates
- Custom directory support for generated commands
- Force overwrite option for existing files

### Changed
- Improved command registry performance
- Better error messages for missing commands

## [1.1.0] - 2024-11-15

### Added
- Progress bar support for long-running operations
- Spinner animation for indeterminate progress
- Tree display for hierarchical data
- Box drawing around important messages
- Section headers for organized output

### Changed
- Refactored Output class for better extensibility
- Improved table rendering algorithm

### Fixed
- Memory leak in long-running commands
- Color support detection on Windows

## [1.0.0] - 2024-10-01

### Added
- Initial release
- Core command system
- Argument and option parsing
- Colored output support
- Table rendering
- Built-in commands (help, list)
- Input validation
- Error handling
- 100% test coverage

### Features
- Zero dependencies
- PSR-12 compliant
- Cross-platform support (Windows, macOS, Linux)
- PHP 8.1+ support

## [0.9.0-beta] - 2024-09-15

### Added
- Beta release for testing
- Basic command structure
- Output formatting
- Initial test suite

### Known Issues
- Limited Windows support
- No REPL functionality
- Basic documentation only

---

## Version History Summary

- **1.3.0** - REPL and interactive features
- **1.2.0** - Command scaffolding
- **1.1.0** - Enhanced output formatting
- **1.0.0** - First stable release
- **0.9.0-beta** - Beta testing phase

## Upgrade Guide

### From 1.2.x to 1.3.0

1. No breaking changes
2. New REPL features are opt-in
3. Update composer dependency:
   ```bash
   composer update marwen-brini/yalla
   ```

### From 1.1.x to 1.2.0

1. No breaking changes
2. New `create:command` available immediately
3. Update composer dependency

### From 1.0.x to 1.1.0

1. No breaking changes
2. New output methods are backward compatible
3. Update composer dependency

## Deprecations

Currently, there are no deprecated features.

## Future Releases

### Planned for 1.4.0
- Event system for command lifecycle
- Middleware support
- Command aliases
- Performance improvements

### Planned for 2.0.0
- Async command execution
- Plugin marketplace
- GUI mode
- Breaking changes to improve API

## Support Policy

- Latest version: Full support
- Previous minor version: Security fixes only
- Older versions: No support

### PHP Version Support

| Yalla Version | PHP 8.1 | PHP 8.2 | PHP 8.3 | PHP 8.4 |
|---------------|---------|---------|---------|---------|
| 1.3.x         | ✅      | ✅      | ✅      | ✅      |
| 1.2.x         | ✅      | ✅      | ✅      | ❌      |
| 1.1.x         | ✅      | ✅      | ❌      | ❌      |
| 1.0.x         | ✅      | ❌      | ❌      | ❌      |

## Reporting Issues

Please report issues on [GitHub](https://github.com/marwen-brini/yalla/issues).

## Contributing

See [Contributing Guide](./contributing.md) for details on how to contribute.