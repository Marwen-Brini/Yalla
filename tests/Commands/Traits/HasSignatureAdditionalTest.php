<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\Traits\HasSignature;
use Yalla\Output\Output;

test('parseSignature returns early when signature is empty', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected string $name = 'default:name'; // Initialize to avoid error

        public function __construct()
        {
            $this->signature = '';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    // Should return early without changing the default name
    expect($command->getName())->toBe('default:name');
});

test('parseSignature initializes arrays when not set', function () {
    $command = new class extends Command
    {
        use HasSignature;

        // Don't initialize these arrays to test the initialization logic
        public function __construct()
        {
            $this->signature = 'test:command {arg}';
            // Unset any arrays that might be initialized by parent
            unset($this->arguments);
            unset($this->options);
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    expect($command->getArguments())->toBeArray();
    expect($command->getOptions())->toBeArray();
});

test('parseArgument without addArgument method', function () {
    $command = new class extends Command
    {
        use HasSignature {
            parseArgument as protected;
        }

        protected array $arguments = [];

        protected array $argumentMetadata = [];

        public function __construct()
        {
            // Don't call configureUsingSignature to test parseArgument directly
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function testParseArgument(string $part): void
        {
            $this->parseArgument($part);
        }

        public function getArgumentsForTest(): array
        {
            return $this->arguments;
        }
    };

    // Test direct array addition when addArgument method doesn't exist
    $command->testParseArgument('{name}');

    $args = $command->getArgumentsForTest();
    expect($args)->toHaveCount(1);
    expect($args[0]['name'])->toBe('name');
    expect($args[0]['description'])->toBe('Name'); // Default description
    expect($args[0]['required'])->toBeTrue();
});

test('parseArgument initializes argumentMetadata when not set', function () {
    $command = new class extends Command
    {
        use HasSignature;

        public function __construct()
        {
            $this->signature = 'test:command {items*}';
            // Make sure argumentMetadata is not set
            unset($this->argumentMetadata);
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function getArgumentMetadata(): array
        {
            return $this->argumentMetadata ?? [];
        }
    };

    $metadata = $command->getArgumentMetadata();
    expect($metadata)->toBeArray();
    expect($metadata)->toHaveKey('items');
    expect($metadata['items']['isArray'])->toBeTrue();
});

test('parseOption without addOption method', function () {
    $command = new class extends Command
    {
        use HasSignature {
            parseOption as protected;
        }

        protected array $options = [];

        protected array $optionMetadata = [];

        public function __construct()
        {
            // Don't call configureUsingSignature to test parseOption directly
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function testParseOption(string $part): void
        {
            $this->parseOption($part);
        }

        public function getOptionsForTest(): array
        {
            return $this->options;
        }
    };

    // Test direct array addition when addOption method doesn't exist
    $command->testParseOption('{--force}');

    $opts = $command->getOptionsForTest();
    expect($opts)->toHaveCount(1);
    expect($opts[0]['name'])->toBe('force');
    expect($opts[0]['description'])->toBe('Force'); // Default description
    expect($opts[0]['default'])->toBeNull();
});

test('parseOption initializes optionMetadata when not set', function () {
    $command = new class extends Command
    {
        use HasSignature;

        public function __construct()
        {
            $this->signature = 'test:command {--verbose}';
            // Make sure optionMetadata is not set
            unset($this->optionMetadata);
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function getOptionMetadata(): array
        {
            return $this->optionMetadata ?? [];
        }
    };

    $metadata = $command->getOptionMetadata();
    expect($metadata)->toBeArray();
    expect($metadata)->toHaveKey('verbose');
});

test('parseDefaultValue handles quoted strings', function () {
    $command = new class extends Command
    {
        use HasSignature {
            parseDefaultValue as public;
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    // Test double quotes
    expect($command->parseDefaultValue('"hello world"'))->toBe('hello world');

    // Test single quotes
    expect($command->parseDefaultValue("'hello world'"))->toBe('hello world');
});

test('parseDefaultValue handles boolean true', function () {
    $command = new class extends Command
    {
        use HasSignature {
            parseDefaultValue as public;
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    expect($command->parseDefaultValue('true'))->toBeTrue();
});

test('parseDefaultValue handles empty array', function () {
    $command = new class extends Command
    {
        use HasSignature {
            parseDefaultValue as public;
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    expect($command->parseDefaultValue('[]'))->toBe([]);
});

test('parse complex signature with all features', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected array $argumentMetadata = [];

        protected array $optionMetadata = [];

        public function __construct()
        {
            $this->signature = 'test:command {source} {dest?} {items*} {--f|force} {--limit=10}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }

        public function getArgumentMetadata(): array
        {
            return $this->argumentMetadata;
        }

        public function getOptionMetadata(): array
        {
            return $this->optionMetadata;
        }
    };

    expect($command->getName())->toBe('test:command');

    $arguments = $command->getArguments();
    expect($arguments)->toHaveCount(3);

    $options = $command->getOptions();
    expect($options)->toHaveCount(2);

    $argMeta = $command->getArgumentMetadata();
    expect($argMeta['items']['isArray'])->toBeTrue();

    $optMeta = $command->getOptionMetadata();
    expect($optMeta['limit']['isValueRequired'])->toBeTrue();
});
