# Contributing to Yalla CLI

First off, thank you for considering contributing to Yalla CLI! It's people like you that make Yalla CLI such a great tool.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please be respectful and considerate in your interactions with other contributors.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

- **Use a clear and descriptive title** for the issue to identify the problem
- **Describe the exact steps which reproduce the problem** in as many details as possible
- **Provide specific examples to demonstrate the steps**
- **Describe the behavior you observed after following the steps**
- **Explain which behavior you expected to see instead and why**
- **Include your environment details** (PHP version, OS, etc.)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Use a clear and descriptive title** for the issue to identify the suggestion
- **Provide a step-by-step description of the suggested enhancement**
- **Provide specific examples to demonstrate the steps**
- **Describe the current behavior** and **explain which behavior you expected to see instead**
- **Explain why this enhancement would be useful**

### Pull Requests

1. Fork the repo and create your branch from `main`
2. If you've added code that should be tested, add tests
3. If you've changed APIs, update the documentation
4. Ensure the test suite passes
5. Make sure your code follows the existing code style
6. Issue that pull request!

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer 2.0+
- Node.js 20+ (for documentation)

### Setting Up Your Development Environment

1. **Clone your fork:**
   ```bash
   git clone https://github.com/your-username/yalla.git
   cd yalla
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install Node dependencies (for documentation):**
   ```bash
   npm install
   ```

4. **Run tests to verify setup:**
   ```bash
   composer test
   ```

### Development Workflow

1. **Create a feature branch:**
   ```bash
   git checkout -b feature/my-new-feature
   ```

2. **Make your changes:**
   - Write your code
   - Add or update tests
   - Update documentation if needed

3. **Run tests:**
   ```bash
   # Run all tests
   composer test

   # Run tests with coverage
   composer test-coverage

   # Run specific test file
   ./vendor/bin/pest tests/YourTest.php
   ```

4. **Format your code:**
   ```bash
   composer format
   ```

5. **Check code quality:**
   ```bash
   composer analyse
   ```

6. **Build documentation locally:**
   ```bash
   npm run docs:dev
   ```

7. **Commit your changes:**
   ```bash
   git add .
   git commit -m "feat: add amazing feature"
   ```

## Coding Standards

### PHP Style Guide

We use Laravel Pint for code formatting. Run `composer format` before committing.

Key conventions:
- PSR-12 coding standard
- Type declarations for parameters and return types
- Meaningful variable and method names
- DocBlocks for complex methods

### Example Code Style

```php
<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

class ExampleCommand extends Command
{
    public function __construct()
    {
        $this->name = 'example';
        $this->description = 'An example command';
    }

    public function execute(array $input, Output $output): int
    {
        $output->success('Example executed!');

        return 0;
    }
}
```

## Testing Guidelines

### Writing Tests

- Write tests for all new features
- Maintain 100% code coverage
- Use descriptive test names
- Test both success and failure cases

### Test Structure

```php
test('command executes successfully with valid input', function () {
    $command = new MyCommand();
    $output = Mockery::mock(Output::class);

    $output->shouldReceive('success')
        ->once()
        ->with('Operation completed');

    $input = [
        'command' => 'my:command',
        'arguments' => ['arg1'],
        'options' => []
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(0);
});
```

## Documentation

### Updating Documentation

When adding new features or changing existing ones:

1. Update the relevant guide pages in `docs/guide/`
2. Update API documentation in `docs/api/`
3. Add examples in `docs/examples/`
4. Update the README if necessary

### Documentation Style

- Use clear, concise language
- Include code examples
- Explain the "why" not just the "how"
- Keep formatting consistent

## Commit Message Guidelines

We follow the Conventional Commits specification:

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, etc.)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

### Examples

```bash
feat(repl): add syntax highlighting support

fix(output): correct color detection on Windows

docs(api): update Command class documentation

test(commands): add tests for deploy command

chore: update dependencies
```

## Release Process

1. Update version in `composer.json`
2. Update version in `package.json`
3. Update CHANGELOG.md
4. Create a git tag
5. Push to GitHub
6. Create GitHub release

## Where to Get Help

- Check the [documentation](https://marwen-brini.github.io/yalla/)
- Look through existing [issues](https://github.com/marwen-brini/yalla/issues)
- Create a new issue with the question label
- Contact the maintainer: brini.marwen@gmail.com

## Recognition

Contributors will be recognized in:
- The project README
- Release notes
- Documentation credits

## License

By contributing to Yalla CLI, you agree that your contributions will be licensed under its MIT license.

## Thank You!

Your contributions to open source, no matter how small, make projects like this possible. Thank you for taking the time to contribute!