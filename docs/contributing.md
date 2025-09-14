# Contributing

Thank you for your interest in contributing to Yalla CLI! We welcome contributions from the community.

## Quick Start

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a feature branch
4. Make your changes
5. Run tests
6. Submit a pull request

## Development Setup

### Requirements

- PHP 8.1 or higher
- Composer 2.0+
- Node.js 20+ (for documentation)

### Installation

```bash
# Clone your fork
git clone https://github.com/your-username/yalla.git
cd yalla

# Install dependencies
composer install
npm install

# Run tests
composer test
```

## Making Changes

### Code Changes

1. **Create a feature branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Write your code following our standards:**
   - PSR-12 coding standard
   - Type declarations required
   - 100% test coverage for new code

3. **Run tests:**
   ```bash
   composer test
   composer test-coverage
   ```

4. **Format code:**
   ```bash
   composer format
   ```

5. **Check code quality:**
   ```bash
   composer analyse
   ```

### Documentation Changes

1. **Edit documentation files** in `docs/`

2. **Preview changes:**
   ```bash
   npm run docs:dev
   ```

3. **Build documentation:**
   ```bash
   npm run docs:build
   ```

## Submitting Changes

### Commit Messages

We use Conventional Commits:

```bash
feat: add new feature
fix: resolve bug
docs: update documentation
test: add tests
chore: update dependencies
```

Examples:
```bash
feat(repl): add autocomplete for class methods
fix(output): correct table rendering on Windows
docs(guide): add section on error handling
test(commands): increase coverage for deploy command
```

### Pull Request Process

1. **Update your branch:**
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Push to your fork:**
   ```bash
   git push origin feature/your-feature-name
   ```

3. **Create Pull Request on GitHub** with:
   - Clear title describing the change
   - Description of what and why
   - Reference to any related issues
   - Screenshots if applicable

4. **Address review feedback** promptly

## Testing

### Running Tests

```bash
# All tests
composer test

# With coverage
composer test-coverage

# Specific file
./vendor/bin/pest tests/Commands/DeployCommandTest.php

# Watch mode
./vendor/bin/pest --watch
```

### Writing Tests

```php
test('command handles invalid input gracefully', function () {
    $command = new MyCommand();
    $output = Mockery::mock(Output::class);

    $output->shouldReceive('error')
        ->once()
        ->with('Invalid input provided');

    $input = [
        'command' => 'my:command',
        'arguments' => ['invalid'],
        'options' => []
    ];

    $result = $command->execute($input, $output);

    expect($result)->toBe(1);
});
```

## Areas to Contribute

### Good First Issues

Look for issues labeled `good first issue` on GitHub. These are ideal for newcomers.

### Feature Requests

Check issues labeled `enhancement` for feature ideas.

### Documentation

- Improve existing documentation
- Add more examples
- Translate documentation
- Fix typos and clarity issues

### Testing

- Increase test coverage
- Add edge case tests
- Improve test performance

### Performance

- Optimize slow operations
- Reduce memory usage
- Improve startup time

## Code Style

### PHP Standards

```php
<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

/**
 * Example command demonstrating code style
 */
class ExampleCommand extends Command
{
    public function __construct()
    {
        $this->name = 'example';
        $this->description = 'Example command';

        $this->addArgument('input', 'Input file', true);
        $this->addOption('verbose', 'v', 'Verbose output', false);
    }

    public function execute(array $input, Output $output): int
    {
        $inputFile = $this->getArgument($input, 'input');
        $verbose = $this->getOption($input, 'verbose', false);

        if (!file_exists($inputFile)) {
            $output->error("File not found: $inputFile");
            return 1;
        }

        if ($verbose) {
            $output->info("Processing: $inputFile");
        }

        // Process file...

        $output->success('Done!');

        return 0;
    }
}
```

### Documentation Standards

- Use clear, concise language
- Include code examples
- Explain complex concepts
- Keep consistent formatting

## Getting Help

### Resources

- [Documentation](https://marwen-brini.github.io/Yalla/)
- [GitHub Issues](https://github.com/marwen-brini/yalla/issues)
- [Discussions](https://github.com/marwen-brini/yalla/discussions)

### Contact

- Create an issue on GitHub
- Email: brini.marwen@gmail.com

## Recognition

Contributors are recognized in:
- README.md contributors section
- Release notes
- Documentation credits

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Code of Conduct

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone.

### Our Standards

Examples of behavior that contributes to a positive environment:

- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community

Examples of unacceptable behavior:

- Trolling, insulting/derogatory comments
- Public or private harassment
- Publishing others' private information
- Other conduct which could be considered inappropriate

### Enforcement

Project maintainers will remove, edit, or reject contributions that do not follow this Code of Conduct.

## Thank You!

Every contribution matters, no matter how small. Thank you for helping make Yalla CLI better!