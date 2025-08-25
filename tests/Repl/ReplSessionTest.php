<?php

declare(strict_types=1);

use Yalla\Repl\ReplSession;
use Yalla\Repl\ReplContext;
use Yalla\Repl\ReplConfig;
use Yalla\Output\Output;

test('can create ReplSession', function () {
    $config = new ReplConfig();
    $context = new ReplContext($config);
    $output = new Output();
    
    $session = new ReplSession($context, $output, $config);
    
    expect($session)->toBeInstanceOf(ReplSession::class);
});