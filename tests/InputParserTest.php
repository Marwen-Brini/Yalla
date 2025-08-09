<?php

declare(strict_types=1);

use Yalla\Input\InputParser;

it('parses command correctly', function () {
    $parser = new InputParser;
    $result = $parser->parse(['serve']);

    expect($result['command'])->toBe('serve');
    expect($result['arguments'])->toBeEmpty();
    expect($result['options'])->toBeEmpty();
});

it('parses command with arguments', function () {
    $parser = new InputParser;
    $result = $parser->parse(['make:model', 'User', 'Post']);

    expect($result['command'])->toBe('make:model');
    expect($result['arguments'])->toBe(['User', 'Post']);
});

it('parses long options', function () {
    $parser = new InputParser;
    $result = $parser->parse(['serve', '--port', '8080', '--host=localhost']);

    expect($result['command'])->toBe('serve');
    expect($result['options']['port'])->toBe('8080');
    expect($result['options']['host'])->toBe('localhost');
});

it('parses short options', function () {
    $parser = new InputParser;
    $result = $parser->parse(['test', '-v', '-f']);

    expect($result['command'])->toBe('test');
    expect($result['options']['v'])->toBeTrue();
    expect($result['options']['f'])->toBeTrue();
});

it('parses mixed input', function () {
    $parser = new InputParser;
    $result = $parser->parse(['deploy', 'production', '--force', '-v', '--timeout=300']);

    expect($result['command'])->toBe('deploy');
    expect($result['arguments'])->toBe(['production']);
    expect($result['options']['force'])->toBeTrue();
    expect($result['options']['v'])->toBeTrue();
    expect($result['options']['timeout'])->toBe('300');
});
