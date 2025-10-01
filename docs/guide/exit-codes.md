# Exit Codes

::: tip New in v2.0
Standard exit codes with descriptions and exception mapping.
:::

## Overview

The `ExitCodes` interface provides standard exit codes for CLI commands, making your applications behave consistently with Unix/Linux conventions.

## Available Exit Codes

| Code | Constant | Description |
|------|----------|-------------|
| 0 | `EXIT_SUCCESS` | Successful execution |
| 1 | `EXIT_ERROR` | General error |
| 2 | `EXIT_INVALID_ARGUMENT` | Invalid argument/option |
| 69 | `EXIT_UNAVAILABLE` | Service unavailable |
| 77 | `EXIT_PERMISSION_DENIED` | Permission denied |
| 78 | `EXIT_CONFIG_ERROR` | Configuration error |

## Basic Usage

```php
<?php

use Yalla\Commands\Command;
use Yalla\Commands\ExitCodes;
use Yalla\Output\Output;

class DatabaseCommand extends Command implements ExitCodes
{
    public function execute(array $input, Output $output): int
    {
        if (!$this->canConnect()) {
            return $this->returnWithCode(
                self::EXIT_UNAVAILABLE,
                'Database unavailable',
                $output
            );
        }

        if (!$this->hasPermission()) {
            return $this->returnWithCode(
                self::EXIT_PERMISSION_DENIED,
                'Permission denied',
                $output
            );
        }

        try {
            $this->performOperation();
            return $this->returnSuccess($output);
        } catch (\Exception $e) {
            return $this->handleException($e, $output, debug: true);
        }
    }
}
```

## Helper Methods

### Return with Code

```php
return $this->returnWithCode(
    self::EXIT_ERROR,
    'Operation failed',
    $output
);
```

### Return Success/Error

```php
// Success
return $this->returnSuccess($output);

// Error
return $this->returnError('Something went wrong', $output);
```

### Handle Exceptions

```php
try {
    $this->riskyOperation();
} catch (\Exception $e) {
    return $this->handleException($e, $output, debug: true);
}
```

## Best Practices

1. **Use standard codes** for consistency with Unix/Linux tools
2. **Provide clear messages** when returning error codes
3. **Map exceptions** to appropriate exit codes
4. **Enable debug mode** in development for stack traces

## See Also

- [Commands](/guide/commands) - Creating commands
- [Error Handling](/guide/error-handling) - Exception handling
