<?php

declare(strict_types=1);

namespace Yalla\Commands;

use Yalla\Output\Output;

abstract class Command implements ExitCodes
{
    protected string $name;

    protected string $description;

    protected array $arguments = [];

    protected array $options = [];

    protected ?Output $output = null;

    abstract public function execute(array $input, Output $output): int;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function addArgument(string $name, string $description, bool $required = false): self
    {
        $this->arguments[] = [
            'name' => $name,
            'description' => $description,
            'required' => $required,
        ];

        return $this;
    }

    protected function addOption(string $name, ?string $shortcut, string $description, $default = null): self
    {
        $this->options[] = [
            'name' => $name,
            'shortcut' => $shortcut,
            'description' => $description,
            'default' => $default,
        ];

        return $this;
    }

    protected function getArgument(array $input, string $name, $default = null)
    {
        $index = array_search($name, array_column($this->arguments, 'name'));

        return $input['arguments'][$index] ?? $default;
    }

    protected function getOption(array $input, string $name, $default = null)
    {
        return $input['options'][$name] ?? $default;
    }

    /**
     * Return with specific code and optional message
     * This method outputs a message and returns the code for the command to exit with
     *
     * @param  int  $code  Exit code
     * @param  string|null  $message  Optional message to display
     * @return int The exit code
     */
    protected function returnWithCode(int $code = self::EXIT_SUCCESS, ?string $message = null): int
    {
        if ($message !== null && $this->output !== null) {
            if ($code === self::EXIT_SUCCESS) {
                $this->output->writeln($this->output->color($message, Output::GREEN));
            } else {
                $this->output->writeln($this->output->color($message, Output::RED));
            }
        }

        return $code;
    }

    /**
     * Return with success code and optional message
     *
     * @param  string|null  $message  Optional success message
     * @return int Success exit code (0)
     */
    protected function returnSuccess(?string $message = null): int
    {
        return $this->returnWithCode(self::EXIT_SUCCESS, $message);
    }

    /**
     * Return with error code and message
     *
     * @param  string  $message  Error message
     * @param  int  $code  Exit code (defaults to EXIT_FAILURE)
     * @return int Error exit code
     */
    protected function returnError(string $message, int $code = self::EXIT_FAILURE): int
    {
        return $this->returnWithCode($code, $message);
    }

    /**
     * Get human-readable description of an exit code
     *
     * @param  int  $code  Exit code
     * @return string Description of the exit code
     */
    public static function getExitCodeDescription(int $code): string
    {
        return match ($code) {
            self::EXIT_SUCCESS => 'Success',
            self::EXIT_FAILURE => 'General failure',
            self::EXIT_USAGE => 'Incorrect usage',
            self::EXIT_USAGE_ERROR => 'Command line usage error',
            self::EXIT_DATAERR => 'Data format error',
            self::EXIT_NOINPUT => 'Cannot open input',
            self::EXIT_NOUSER => 'Addressee unknown',
            self::EXIT_NOHOST => 'Host name unknown',
            self::EXIT_UNAVAILABLE => 'Service unavailable',
            self::EXIT_SOFTWARE => 'Internal software error',
            self::EXIT_OSERR => 'System error',
            self::EXIT_OSFILE => 'Critical OS file missing',
            self::EXIT_CANTCREAT => 'Cannot create output',
            self::EXIT_IOERR => 'I/O error',
            self::EXIT_TEMPFAIL => 'Temporary failure',
            self::EXIT_PROTOCOL => 'Remote error',
            self::EXIT_NOPERM => 'Permission denied',
            self::EXIT_CONFIG => 'Configuration error',
            self::EXIT_LOCKED => 'Resource locked',
            self::EXIT_TIMEOUT => 'Operation timed out',
            self::EXIT_CANCELLED => 'Cancelled by user',
            self::EXIT_VALIDATION => 'Validation failed',
            self::EXIT_MISSING_DEPS => 'Missing dependencies',
            self::EXIT_NOT_FOUND => 'Resource not found',
            self::EXIT_CONFLICT => 'Resource conflict',
            self::EXIT_ROLLBACK => 'Operation rolled back',
            self::EXIT_PARTIAL => 'Operation partially completed',
            self::EXIT_COMMAND_NOT_FOUND => 'Command not found',
            self::EXIT_SIGINT => 'Interrupted by Ctrl+C',
            self::EXIT_SIGTERM => 'Terminated',
            default => "Unknown error (code: {$code})",
        };
    }

    /**
     * Handle exception and return appropriate exit code
     *
     * @param  \Throwable  $exception  The exception to handle
     * @return int Exit code based on exception type
     */
    protected function handleException(\Throwable $exception): int
    {
        $code = $this->mapExceptionToExitCode($exception);
        $message = $exception->getMessage();

        if ($this->output !== null && method_exists($this->output, 'isDebug') && $this->output->isDebug()) {
            $message .= "\n\nStack trace:\n".$exception->getTraceAsString();
        }

        return $this->returnError($message, $code);
    }

    /**
     * Map exception types to appropriate exit codes
     *
     * @param  \Throwable  $exception  The exception to map
     * @return int Appropriate exit code
     */
    protected function mapExceptionToExitCode(\Throwable $exception): int
    {
        // Check for exact class match or inheritance (more specific first)
        return match (true) {
            $exception instanceof \InvalidArgumentException => self::EXIT_USAGE,
            $exception instanceof \BadMethodCallException => self::EXIT_SOFTWARE,
            $exception instanceof \DomainException => self::EXIT_CONFIG,
            $exception instanceof \RangeException => self::EXIT_DATAERR,
            $exception instanceof \UnexpectedValueException => self::EXIT_DATAERR,
            $exception instanceof \LengthException => self::EXIT_DATAERR,
            $exception instanceof \OutOfBoundsException => self::EXIT_DATAERR,
            $exception instanceof \OverflowException => self::EXIT_TEMPFAIL,
            $exception instanceof \UnderflowException => self::EXIT_TEMPFAIL,
            $exception instanceof \RuntimeException => self::EXIT_SOFTWARE,
            $exception instanceof \LogicException => self::EXIT_SOFTWARE,
            str_contains(get_class($exception), 'ValidationException') => self::EXIT_VALIDATION,
            str_contains(get_class($exception), 'NotFoundException') => self::EXIT_NOT_FOUND,
            str_contains(get_class($exception), 'PermissionException') => self::EXIT_NOPERM,
            str_contains(get_class($exception), 'TimeoutException') => self::EXIT_TIMEOUT,
            str_contains(get_class($exception), 'LockException') => self::EXIT_LOCKED,
            str_contains(get_class($exception), 'ConfigException') => self::EXIT_CONFIG,
            str_contains(get_class($exception), 'IOException') => self::EXIT_IOERR,
            default => self::EXIT_FAILURE,
        };
    }

    /**
     * Set the output instance for this command
     *
     * @param  Output  $output  The output instance
     */
    protected function setOutput(Output $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * @deprecated Use returnWithCode() instead
     * @codeCoverageIgnore
     */
    protected function exit(int $code = self::EXIT_SUCCESS, ?string $message = null): void
    {
        if ($message !== null && $this->output !== null) {
            if ($code === self::EXIT_SUCCESS) {
                $this->output->writeln($this->output->color($message, Output::GREEN));
            } else {
                $this->output->writeln($this->output->color($message, Output::RED));
            }
        }
        exit($code);
    }

    /**
     * @deprecated Use returnSuccess() instead
     * @codeCoverageIgnore
     */
    protected function exitSuccess(?string $message = null): void
    {
        $this->exit(self::EXIT_SUCCESS, $message);
    }

    /**
     * @deprecated Use returnError() instead
     * @codeCoverageIgnore
     */
    protected function exitError(string $message, int $code = self::EXIT_FAILURE): void
    {
        $this->exit($code, $message);
    }
}
