<?php

declare(strict_types=1);

namespace Switch\Console\Signature;

class SignatureParser
{
    /**
     * Parse a command signature string.
     * Example: 'make:controller {name} {--resource} {--path=}'
     *
     * @return array{name: string, arguments: array<int, array>, options: array<string, array>}
     */
    public static function parse(string $signature): array
    {
        $parts = preg_split('/\s+/', trim($signature)) ?: [];
        $commandName = array_shift($parts) ?? '';

        $arguments = [];
        $options = [];

        foreach ($parts as $part) {
            if (!str_starts_with($part, '{') || !str_ends_with($part, '}')) {
                continue;
            }

            $token = trim($part, '{}');

            if (str_starts_with($token, '--')) {
                // Option
                $optionName = substr($token, 2);
                $shortcut = null;
                $default = false;

                if (str_contains($optionName, '|')) {
                    [$shortcut, $optionName] = explode('|', $optionName, 2);
                }

                if (str_contains($optionName, '=')) {
                    [$optionName, $default] = explode('=', $optionName, 2);
                }

                $options[$optionName] = [
                    'name' => $optionName,
                    'shortcut' => $shortcut,
                    'default' => $default,
                    'requiresValue' => str_contains($token, '='),
                ];
            } else {
                // Argument
                $argName = $token;
                $required = true;
                $default = null;

                if (str_ends_with($argName, '?')) {
                    $argName = rtrim($argName, '?');
                    $required = false;
                } elseif (str_contains($argName, '=')) {
                    [$argName, $default] = explode('=', $argName, 2);
                    $required = false;
                }

                $arguments[] = [
                    'name' => $argName,
                    'required' => $required,
                    'default' => $default,
                ];
            }
        }

        return [
            'name' => $commandName,
            'arguments' => $arguments,
            'options' => $options,
        ];
    }
}
