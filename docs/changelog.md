# Changelog

All notable changes to Yalla CLI will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive VitePress documentation site
- GitHub Pages deployment workflow
- MIT License file
- Contributing guidelines
- API reference documentation for all core classes

### Fixed
- REPL test output handling to prevent risky test warnings

### Changed
- Documentation moved to VitePress from markdown files
- Updated README with documentation links

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