<?php

declare(strict_types=1);

use Yalla\Commands\Command;
use Yalla\Commands\Traits\HasSignature;
use Yalla\Output\Output;

test('parse simple command', function () {
    $command = new class extends Command
    {
        use HasSignature;

        public function __construct()
        {
            $this->signature = 'test:command';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    expect($command->getName())->toBe('test:command');
});

test('parse command with argument', function () {
    $command = new class extends Command
    {
        use HasSignature;

        public function __construct()
        {
            $this->signature = 'test:command {name}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $arguments = $command->getArguments();
    expect($arguments)->toHaveCount(1);
    expect($arguments[0]['name'])->toBe('name');
    expect($arguments[0]['description'])->toBe('Name');
    expect($arguments[0]['required'])->toBeTrue();
});

test('parse command with optional argument', function () {
    $command = new class extends Command
    {
        use HasSignature;

        public function __construct()
        {
            $this->signature = 'test:command {name?}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $arguments = $command->getArguments();
    expect($arguments)->toHaveCount(1);
    expect($arguments[0]['name'])->toBe('name');
    expect($arguments[0]['required'])->toBeFalse();
});

test('parse command with argument default', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected array $argumentMetadata = [];

        public function __construct()
        {
            $this->signature = 'test:command {name=John}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $arguments = $command->getArguments();
    expect($arguments)->toHaveCount(1);
    expect($arguments[0]['name'])->toBe('name');
    expect($arguments[0]['required'])->toBeFalse();
});

test('parse command with array argument', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected array $argumentMetadata = [];

        public function __construct()
        {
            $this->signature = 'test:command {files*}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $arguments = $command->getArguments();
    expect($arguments)->toHaveCount(1);
    expect($arguments[0]['name'])->toBe('files');
    expect($arguments[0]['description'])->toContain('multiple values');
});

test('parse command with option', function () {
    $command = new class extends Command
    {
        use HasSignature;

        public function __construct()
        {
            $this->signature = 'test:command {--verbose}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $options = $command->getOptions();
    expect($options)->toHaveCount(1);
    expect($options[0]['name'])->toBe('verbose');
    expect($options[0]['description'])->toBe('Verbose');
});

test('parse command with option shortcut', function () {
    $command = new class extends Command
    {
        use HasSignature;

        public function __construct()
        {
            $this->signature = 'test:command {--verbose|v}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $options = $command->getOptions();
    expect($options)->toHaveCount(1);
    expect($options[0]['name'])->toBe('verbose');
    expect($options[0]['shortcut'])->toBe('v');
});

test('parse command with option value', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected array $optionMetadata = [];

        public function __construct()
        {
            $this->signature = 'test:command {--output=}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $options = $command->getOptions();
    expect($options)->toHaveCount(1);
    expect($options[0]['name'])->toBe('output');
    expect($options[0]['description'])->toContain('value required');
});

test('parse command with option default value', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected array $optionMetadata = [];

        public function __construct()
        {
            $this->signature = 'test:command {--output=file.txt}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    $options = $command->getOptions();
    expect($options)->toHaveCount(1);
    expect($options[0]['name'])->toBe('output');
    expect($options[0]['default'])->toBe('file.txt');
});

test('parse complex signature', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected array $argumentMetadata = [];

        protected array $optionMetadata = [];

        public function __construct()
        {
            $this->signature = 'make:migration {name} {table?} {--create} {--alter} {--path=database/migrations}';
            $this->configureUsingSignature();
        }

        public function execute(array $input, Output $output): int
        {
            return 0;
        }
    };

    expect($command->getName())->toBe('make:migration');

    $arguments = $command->getArguments();
    expect($arguments)->toHaveCount(2);
    expect($arguments[0]['name'])->toBe('name');
    expect($arguments[0]['required'])->toBeTrue();
    expect($arguments[1]['name'])->toBe('table');
    expect($arguments[1]['required'])->toBeFalse();

    $options = $command->getOptions();
    expect($options)->toHaveCount(3);
    expect($options[0]['name'])->toBe('create');
    expect($options[1]['name'])->toBe('alter');
    expect($options[2]['name'])->toBe('path');
    expect($options[2]['default'])->toBe('database/migrations');
});

test('parse default values', function () {
    $command = new class extends Command
    {
        use HasSignature;

        protected array $argumentMetadata = [];

        protected array $optionMetadata = [];

        public function __construct()
        {
            $this->signature = 'test {bool=true} {--int=42} {--float=3.14} {--null=null} {--array=[1,2,3]}';
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

    $argMeta = $command->getArgumentMetadata();
    expect($argMeta['bool']['default'])->toBe(true);

    $options = $command->getOptions();
    expect($options[0]['default'])->toBe(42);
    expect($options[1]['default'])->toBe(3.14);
    expect($options[2]['default'])->toBeNull();
    expect($options[3]['default'])->toBe(['1', '2', '3']);
});
