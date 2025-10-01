<?php

declare(strict_types=1);

use Yalla\Commands\Traits\HasSignature;

// Test class without addArgument and addOption methods
class MinimalCommand
{
    use HasSignature;

    protected string $signature = '';

    protected string $name = 'test';

    protected string $description = 'Test command';

    protected array $arguments = [];

    protected array $options = [];

    protected array $argumentMetadata = [];

    protected array $optionMetadata = [];

    public function __construct(string $signature = '')
    {
        $this->signature = $signature;
        if ($signature) {
            $this->configureUsingSignature();
        }
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getArgumentMetadata(): array
    {
        return $this->argumentMetadata;
    }

    public function getOptionMetadata(): array
    {
        return $this->optionMetadata;
    }
}

test('fallback argument addition without addArgument method', function () {
    $command = new MinimalCommand('test {user}');

    $args = $command->getArguments();
    expect($args)->toHaveCount(1);
    expect($args[0]['name'])->toBe('user');
    expect($args[0]['description'])->toBe('User');
    expect($args[0]['required'])->toBeTrue();
});

test('fallback argument with optional and default', function () {
    $command = new MinimalCommand('test {name?} {count=5}');

    $args = $command->getArguments();
    expect($args)->toHaveCount(2);

    // Optional argument
    expect($args[0]['name'])->toBe('name');
    expect($args[0]['required'])->toBeFalse();

    // Argument with default
    expect($args[1]['name'])->toBe('count');
    expect($args[1]['required'])->toBeFalse();
});

test('fallback option addition without addOption method', function () {
    $command = new MinimalCommand('test {--verbose}');

    $opts = $command->getOptions();
    expect($opts)->toHaveCount(1);
    expect($opts[0]['name'])->toBe('verbose');
    expect($opts[0]['shortcut'])->toBeNull();
    expect($opts[0]['description'])->toBe('Verbose');
    expect($opts[0]['default'])->toBeNull();
});

test('option with single dash short format', function () {
    $command = new MinimalCommand('test {-v}');

    $opts = $command->getOptions();
    expect($opts)->toHaveCount(1);
    expect($opts[0]['name'])->toBe('v');
    expect($opts[0]['description'])->toBe('V');
});

test('option with optional value using question mark', function () {
    $command = new MinimalCommand('test {--output?}');

    $opts = $command->getOptions();
    expect($opts)->toHaveCount(1);
    expect($opts[0]['name'])->toBe('output');

    $metadata = $command->getOptionMetadata();
    expect($metadata['output']['isValueRequired'])->toBeFalse();
});

test('parseDefaultValue handles false boolean', function () {
    // Create a test class that exposes parseDefaultValue
    $command = new class
    {
        use HasSignature {
            parseDefaultValue as public;
        }
    };

    expect($command->parseDefaultValue('false'))->toBeFalse();
});

test('option with shortcut syntax', function () {
    $command = new MinimalCommand('test {--force|f}');

    $opts = $command->getOptions();
    expect($opts)->toHaveCount(1);
    expect($opts[0]['name'])->toBe('force');
    expect($opts[0]['shortcut'])->toBe('f');
});

test('option with default value', function () {
    $command = new MinimalCommand('test {--output=file.txt}');

    $opts = $command->getOptions();
    expect($opts)->toHaveCount(1);
    expect($opts[0]['name'])->toBe('output');
    expect($opts[0]['default'])->toBe('file.txt');
});

test('multiple options with different formats', function () {
    $command = new MinimalCommand('test {file} {-q} {--verbose}');

    $args = $command->getArguments();
    expect($args)->toHaveCount(1);
    expect($args[0]['name'])->toBe('file');

    $opts = $command->getOptions();
    expect($opts)->toHaveCount(2);

    // Should have both q and verbose options
    $optNames = array_column($opts, 'name');
    expect($optNames)->toContain('q');
    expect($optNames)->toContain('verbose');
});

test('argument metadata initialization when not set', function () {
    // Create command and unset argumentMetadata before parsing
    $command = new class
    {
        use HasSignature;

        protected string $name = 'test';

        protected string $description = 'Test';

        protected array $arguments = [];

        protected array $options = [];

        public function __construct()
        {
            $this->signature = 'test {items*}';
            // Explicitly don't set argumentMetadata
            $this->configureUsingSignature();
        }

        public function getArgumentMetadata(): array
        {
            return $this->argumentMetadata ?? [];
        }
    };

    $metadata = $command->getArgumentMetadata();
    expect($metadata)->toHaveKey('items');
    expect($metadata['items']['isArray'])->toBeTrue();
});

test('option metadata initialization when not set', function () {
    // Create command and unset optionMetadata before parsing
    $command = new class
    {
        use HasSignature;

        protected string $name = 'test';

        protected string $description = 'Test';

        protected array $arguments = [];

        protected array $options = [];

        public function __construct()
        {
            $this->signature = 'test {--limit=10}';
            // Explicitly don't set optionMetadata
            $this->configureUsingSignature();
        }

        public function getOptionMetadata(): array
        {
            return $this->optionMetadata ?? [];
        }
    };

    $metadata = $command->getOptionMetadata();
    expect($metadata)->toHaveKey('limit');
    expect($metadata['limit']['isValueRequired'])->toBeTrue();
});
