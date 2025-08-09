<?php

declare(strict_types=1);

use Yalla\Application;

it('can create an application instance', function () {
    $app = new Application('Test CLI', '1.0.0');
    
    expect($app)->toBeInstanceOf(Application::class);
    expect($app->getName())->toBe('Test CLI');
    expect($app->getVersion())->toBe('1.0.0');
});

it('has default name and version', function () {
    $app = new Application();
    
    expect($app->getName())->toBe('Yalla CLI');
    expect($app->getVersion())->toBe('1.0.0');
});