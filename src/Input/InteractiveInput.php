<?php

declare(strict_types=1);

namespace Yalla\Input;

use Yalla\Output\Output;

class InteractiveInput
{
    protected Output $output;

    protected bool $interactive = true;

    protected $inputStream = null;

    protected $outputStream = null;

    public function __construct(Output $output, $inputStream = null, $outputStream = null)
    {
        $this->output = $output;
        $this->inputStream = $inputStream ?? STDIN;
        $this->outputStream = $outputStream ?? STDOUT;

        // Check if we're in an interactive terminal
        $this->interactive = $this->isInteractive();
    }

    /**
     * Ask for confirmation (yes/no)
     */
    public function confirm(
        string $question,
        bool $default = false,
        ?string $yesText = null,
        ?string $noText = null
    ): bool {
        if (! $this->interactive) {
            return $default;
        }

        $yesText = $yesText ?? 'yes';
        $noText = $noText ?? 'no';

        $suffix = $default ? 'Y/n' : 'y/N';
        $question = rtrim($question, ' ?').' ['.$suffix.']: ';

        $this->output->write($question);

        $answer = $this->readLine();

        if (empty($answer)) {
            return $default;
        }

        $answer = strtolower(trim($answer));

        // Accept various forms of yes/no
        $yesAnswers = ['y', 'yes', 'true', '1', 'on'];
        $noAnswers = ['n', 'no', 'false', '0', 'off'];

        if (in_array($answer, $yesAnswers, true)) {
            return true;
        }

        if (in_array($answer, $noAnswers, true)) {
            return false;
        }

        // Invalid input, ask again
        $this->output->warning('Please answer with yes or no.');

        return $this->confirm($question, $default, $yesText, $noText);
    }

    /**
     * Ask for a single choice from a list
     */
    public function choice(
        string $question,
        array $choices,
        $default = null,
        int $maxAttempts = 3
    ): string {
        if (! $this->interactive) {
            if ($default !== null) {
                return is_int($default) ? ($choices[$default] ?? '') : $default;
            }

            return $choices[0] ?? '';
        }

        // Display question and choices
        $this->output->writeln($question);
        $this->output->writeln('');

        $indexedChoices = array_values($choices);
        foreach ($indexedChoices as $index => $choice) {
            $marker = ($default === $index || $default === $choice) ? '>' : ' ';
            $this->output->writeln(sprintf('  %s [%d] %s', $marker, $index, $choice));
        }

        $this->output->writeln('');

        // Build prompt
        $defaultText = '';
        if ($default !== null) {
            $defaultIndex = is_int($default) ? $default : array_search($default, $indexedChoices);
            if ($defaultIndex !== false) {
                $defaultText = " (default: $defaultIndex)";
            }
        }

        $this->output->write("Enter your choice$defaultText: ");

        $attempts = 0;
        while ($attempts < $maxAttempts) {
            $answer = $this->readLine();

            // Use default if empty
            if (empty($answer) && $default !== null) {
                return is_int($default) ? ($indexedChoices[$default] ?? '') : $default;
            }

            // Check if answer is a valid index
            if (is_numeric($answer)) {
                $index = (int) $answer;
                if (isset($indexedChoices[$index])) {
                    return $indexedChoices[$index];
                }
            }

            // Check if answer matches a choice directly
            if (in_array($answer, $indexedChoices, true)) {
                return $answer;
            }

            $attempts++;
            if ($attempts < $maxAttempts) {
                $this->output->warning('Invalid choice. Please enter a number between 0 and '.(count($indexedChoices) - 1));
                $this->output->write("Enter your choice$defaultText: ");
            }
        }

        throw new \RuntimeException('Maximum attempts exceeded for choice selection');
    }

    /**
     * Ask for multiple choices from a list
     */
    public function multiChoice(
        string $question,
        array $choices,
        array $defaults = [],
        int $maxAttempts = 3
    ): array {
        if (! $this->interactive) {
            if (! empty($defaults)) {
                $result = [];
                foreach ($defaults as $default) {
                    if (is_int($default) && isset($choices[$default])) {
                        $result[] = $choices[$default];
                    } elseif (in_array($default, $choices, true)) {
                        $result[] = $default;
                    }
                }

                return $result;
            }

            return [];
        }

        // Display question and choices
        $this->output->writeln($question);
        $this->output->writeln('(Use comma-separated numbers or "all" for all choices)');
        $this->output->writeln('');

        $indexedChoices = array_values($choices);
        foreach ($indexedChoices as $index => $choice) {
            $isDefault = in_array($index, $defaults, true) || in_array($choice, $defaults, true);
            $marker = $isDefault ? '✓' : ' ';
            $this->output->writeln(sprintf('  %s [%d] %s', $marker, $index, $choice));
        }

        $this->output->writeln('');

        // Build default text
        $defaultText = '';
        if (! empty($defaults)) {
            $defaultIndices = [];
            foreach ($defaults as $default) {
                if (is_int($default)) {
                    $defaultIndices[] = $default;
                } else {
                    $index = array_search($default, $indexedChoices);
                    if ($index !== false) {
                        $defaultIndices[] = $index;
                    }
                }
            }
            if (! empty($defaultIndices)) {
                $defaultText = ' (default: '.implode(',', $defaultIndices).')';
            }
        }

        $this->output->write("Enter your choices$defaultText: ");

        $attempts = 0;
        while ($attempts < $maxAttempts) {
            $answer = $this->readLine();

            // Use defaults if empty
            if (empty($answer) && ! empty($defaults)) {
                $result = [];
                foreach ($defaults as $default) {
                    if (is_int($default) && isset($indexedChoices[$default])) {
                        $result[] = $indexedChoices[$default];
                    } elseif (in_array($default, $choices, true)) {
                        $result[] = $default;
                    }
                }

                return array_unique($result);
            }

            // Handle "all" selection
            if (strtolower(trim($answer)) === 'all') {
                return $indexedChoices;
            }

            // Parse comma-separated input
            $parts = array_map('trim', explode(',', $answer));
            $selected = [];
            $valid = true;

            foreach ($parts as $part) {
                if (is_numeric($part)) {
                    $index = (int) $part;
                    if (isset($indexedChoices[$index])) {
                        $selected[] = $indexedChoices[$index];
                    } else {
                        $valid = false;

                        break;
                    }
                } else {
                    $valid = false;

                    break;
                }
            }

            if ($valid && ! empty($selected)) {
                return array_unique($selected);
            }

            $attempts++;
            if ($attempts < $maxAttempts) {
                $this->output->warning('Invalid selection. Please enter comma-separated numbers (e.g., 0,2,3)');
                $this->output->write("Enter your choices$defaultText: ");
            }
        }

        throw new \RuntimeException('Maximum attempts exceeded for multi-choice selection');
    }

    /**
     * Ask for text input
     */
    public function ask(string $question, ?string $default = null): string
    {
        if (! $this->interactive && $default !== null) {
            return $default;
        }

        $question = rtrim($question, ' :').': ';
        if ($default !== null) {
            $question = str_replace(': ', " [$default]: ", $question);
        }

        $this->output->write($question);

        $answer = $this->readLine();

        if (empty($answer) && $default !== null) {
            return $default;
        }

        return $answer;
    }

    /**
     * Ask for text input with validation
     */
    public function askValid(
        string $question,
        callable $validator,
        string $error = 'Invalid input. Please try again.',
        ?string $default = null,
        int $maxAttempts = 3
    ): string {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $answer = $this->ask($question, $default);

            try {
                if ($validator($answer)) {
                    return $answer;
                }

                $this->output->error($error);
            } catch (\Exception $e) {
                $this->output->error($e->getMessage());
            }

            $attempts++;
        }

        throw new \RuntimeException('Maximum attempts exceeded for validated input');
    }

    /**
     * Ask for hidden input (e.g., passwords)
     */
    public function askHidden(string $question): string
    {
        if (! $this->interactive) {
            return '';
        }

        $question = rtrim($question, ' :').': ';
        $this->output->write($question);

        if ($this->isWindows()) {
            return $this->askHiddenWindows();
        }

        return $this->askHiddenUnix();
    }

    /**
     * Read hidden input on Unix-like systems
     */
    protected function askHiddenUnix(): string
    {
        $command = '/bin/stty -echo';
        exec($command);

        $value = $this->readLine();

        $command = '/bin/stty echo';
        exec($command);

        $this->output->writeln(''); // New line after hidden input

        return $value;
    }

    /**
     * Read hidden input on Windows systems
     */
    protected function askHiddenWindows(): string
    {
        $exe = __DIR__.'/../../bin/hiddeninput.exe';

        // Fallback to visible input if hiddeninput.exe is not available
        if (! file_exists($exe)) {
            $this->output->warning('Hidden input not available, input will be visible');

            return $this->readLine();
        }

        $value = rtrim(shell_exec($exe));
        $this->output->writeln(''); // New line after hidden input

        return $value;
    }

    /**
     * Read a line from input stream
     */
    protected function readLine(): string
    {
        $line = fgets($this->inputStream);

        if ($line === false) {
            return '';
        }

        return rtrim($line, "\n\r");
    }

    /**
     * Check if the environment is interactive
     */
    protected function isInteractive(): bool
    {
        if (PHP_SAPI === 'cli') {
            // Check if input/output streams are TTY
            if (function_exists('posix_isatty')) {
                // Check stream types to avoid warnings with memory streams in tests
                $inputMeta = stream_get_meta_data($this->inputStream);
                $outputMeta = stream_get_meta_data($this->outputStream);

                // Memory streams (used in tests) are not TTY
                if ($inputMeta['stream_type'] === 'MEMORY' || $outputMeta['stream_type'] === 'MEMORY') {
                    return false;
                }

                return @posix_isatty($this->inputStream) && @posix_isatty($this->outputStream);
            }

            // Windows fallback
            // @codeCoverageIgnoreStart
            if ($this->isWindows()) {
                return true; // Assume interactive on Windows CLI
            }

            return true;
        }

        return false;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Check if running on Windows
     */
    protected function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Set whether to allow interactive input
     */
    public function setInteractive(bool $interactive): self
    {
        $this->interactive = $interactive;

        return $this;
    }

    /**
     * Check if interactive mode is enabled
     */
    public function isInteractiveMode(): bool
    {
        return $this->interactive;
    }
}
