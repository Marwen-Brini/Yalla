<?php

declare(strict_types=1);

namespace Yalla\Input;

class InputParser
{
    public function parse(array $argv): array
    {
        $result = [
            'command' => null,
            'arguments' => [],
            'options' => [],
        ];

        $isOption = false;
        $currentOption = null;

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $isOption = true;
                $optionName = substr($arg, 2);
                
                if (str_contains($optionName, '=')) {
                    [$name, $value] = explode('=', $optionName, 2);
                    $result['options'][$name] = $value;
                    $isOption = false;
                } else {
                    $currentOption = $optionName;
                    $result['options'][$optionName] = true;
                }
            } elseif (str_starts_with($arg, '-')) {
                $isOption = true;
                $flags = substr($arg, 1);
                
                for ($i = 0; $i < strlen($flags); $i++) {
                    $result['options'][$flags[$i]] = true;
                }
            } elseif ($isOption && $currentOption) {
                $result['options'][$currentOption] = $arg;
                $isOption = false;
                $currentOption = null;
            } elseif ($result['command'] === null) {
                $result['command'] = $arg;
            } else {
                $result['arguments'][] = $arg;
            }
        }

        return $result;
    }
}