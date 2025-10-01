<?php

declare(strict_types=1);

use Yalla\Output\Output;
use Yalla\Output\OutputSection;

// Helper function to create testable output instance
function createTestableOutput()
{
    return new class extends Output
    {
        private array $buffer = [];

        public function write(string $message, bool $newline = false): void
        {
            $this->buffer[] = $message.($newline ? PHP_EOL : '');
        }

        public function getBuffer(): array
        {
            return $this->buffer;
        }

        public function clearBuffer(): void
        {
            $this->buffer = [];
        }

        protected function hasColorSupport(): bool
        {
            return false; // Disable colors for testing
        }
    };
}

beforeEach(function () {
    $this->output = createTestableOutput();
});

test('section() returns OutputSection instance', function () {
    $section = $this->output->section('Test Section');

    expect($section)->toBeInstanceOf(OutputSection::class);
    expect($section->getTitle())->toBe('Test Section');
});

test('createSection() returns OutputSection instance', function () {
    $section = $this->output->createSection('Test Section');

    expect($section)->toBeInstanceOf(OutputSection::class);
    expect($section->getTitle())->toBe('Test Section');
});

test('section can write lines', function () {
    $section = $this->output->section('Progress');

    $section->writeln('First line');
    $section->writeln('Second line');

    expect($section->getContent())->toBe(['First line', 'Second line']);
});

test('section can be cleared', function () {
    $section = $this->output->section('Progress');

    $section->writeln('Line 1');
    $section->writeln('Line 2');
    expect($section->getContent())->toHaveCount(2);

    $section->clear();
    expect($section->getContent())->toBeEmpty();
    expect($section->isCleared())->toBeTrue();
});

test('section can be overwritten', function () {
    $section = $this->output->section('Progress');

    $section->writeln('Old content');
    expect($section->getContent())->toBe(['Old content']);

    $section->overwrite('New content');
    expect($section->getContent())->toBe(['New content']);
});

test('multiple sections can coexist', function () {
    $section1 = $this->output->section('Section 1');
    $section2 = $this->output->section('Section 2');

    $section1->writeln('Content 1');
    $section2->writeln('Content 2');

    expect($section1->getContent())->toBe(['Content 1']);
    expect($section2->getContent())->toBe(['Content 2']);
});

test('section respects output instance', function () {
    $section = $this->output->section('Test');

    $section->writeln('Test message');

    $buffer = $this->output->getBuffer();
    expect($buffer)->not->toBeEmpty();
});

test('section title is accessible', function () {
    $section = $this->output->section('Migration Progress');

    expect($section->getTitle())->toBe('Migration Progress');
});

test('section content is trackable', function () {
    $section = $this->output->section('Test');

    expect($section->getContent())->toBeEmpty();

    $section->writeln('Line 1');
    expect($section->getContent())->toHaveCount(1);

    $section->writeln('Line 2');
    $section->writeln('Line 3');
    expect($section->getContent())->toHaveCount(3);

    $section->clear();
    expect($section->getContent())->toBeEmpty();
});

test('section overwrite clears then writes', function () {
    $section = $this->output->section('Test');

    $section->writeln('Line 1');
    $section->writeln('Line 2');
    $section->writeln('Line 3');

    expect($section->getContent())->toHaveCount(3);

    $section->overwrite('Single line');

    expect($section->getContent())->toHaveCount(1);
    expect($section->getContent())->toBe(['Single line']);
});

test('section cleared status is tracked', function () {
    $section = $this->output->section('Test');

    expect($section->isCleared())->toBeFalse();

    $section->writeln('Content');
    expect($section->isCleared())->toBeFalse();

    $section->clear();
    expect($section->isCleared())->toBeTrue();

    $section->writeln('New content');
    // After writing new content, cleared status should remain true
    expect($section->isCleared())->toBeTrue();
});
