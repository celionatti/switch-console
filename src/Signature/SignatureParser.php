<?php

declare(strict_types=1);

namespace Switch\Console\Signature;

class SignatureParser
{
    /**
     * Parse a command signature string.
     * Example: 'make:controller {name} {--resource} {--path=}' or 'audit {--json : JSON output}'
     *
     * @return array{name: string, arguments: array<int, array>, options: array<string, array>}
     */
    public static function parse(string $signature): array
    {
        $signature = trim($signature);
        $spacePos = strpos($signature, ' ');
        $commandName = $spacePos !== false ? substr($signature, 0, $spacePos) : $signature;

        $arguments = [];
        $options = [];

        preg_match_all('/\{([^}]+)\}/', $signature, $matches);

        foreach ($matches[1] as $token) {
            $token = trim($token);

            // Strip inline description if present (e.g. "name : description" or "--json : output json")
            $description = null;
            if (str_contains($token, ':')) {
                [$token, $description] = array_map('trim', explode(':', $token, 2));
            }

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
                    'description' => $description,
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
                    'description' => $description,
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
