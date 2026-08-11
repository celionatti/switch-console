<?php

declare(strict_types=1);

namespace Switch\Console\Output;

class ConsoleOutput
{
    private bool $decorated;

    public function __construct(?bool $decorated = null)
    {
        // Auto-detect ANSI color support
        $this->decorated = $decorated ?? (
            DIRECTORY_SEPARATOR === '\\'
                ? (false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI') || 'xterm' === getenv('TERM') || (defined('PHP_WINDOWS_VT105') && PHP_WINDOWS_VT105))
                : (function_exists('posix_isatty') && @posix_isatty(STDOUT))
        );

        // Force colors on Windows 10+ PowerShell / Windows Terminal
        if (DIRECTORY_SEPARATOR === '\\' && getenv('TERM') !== 'dumb') {
            $this->decorated = true;
        }
    }

    public function isDecorated(): bool
    {
        return $this->decorated;
    }

    /**
     * Render the Futuristic Switch CLI Title Header Banner.
     */
    public function renderHeader(string $version = '1.0.0'): void
    {
        $banner = <<<ASCII

  ███████╗██╗...██╗██╗████████╗██╗..██╗
  ██╔════╝██║...██║██║╚══██╔══╝██║..██║
  ███████╗██║.█.██║██║...██║...███████║
  ╚════██║██║███║██║██║...██║...██╔══██║
  ███████║╚███╔███╔╝██║...██║...██║..██║
  ╚══════╝.╚══╝╚══╝.╚═╝...╚═╝...╚═╝..╚═╝
ASCII;

        if ($this->decorated) {
            // Gradient cyan to magenta
            $lines = explode("\n", $banner);
            $colors = ["\e[38;5;51m", "\e[38;5;45m", "\e[38;5;39m", "\e[38;5;141m", "\e[38;5;177m", "\e[38;5;207m"];
            echo "\n";
            foreach ($lines as $i => $line) {
                $color = $colors[$i % count($colors)];
                echo $color . $line . "\e[0m\n";
            }
            echo "  \e[1;36mSwitch Framework CLI\e[0m \e[38;5;244mv{$version}\e[0m — \e[3;33mFast, Modular & Futuristic PHP\e[0m\n\n";
        } else {
            echo "\n" . $banner . "\n  Switch Framework CLI v{$version}\n\n";
        }
    }

    public function line(string $message = ''): void
    {
        echo $message . PHP_EOL;
    }

    public function info(string $message): void
    {
        if ($this->decorated) {
            echo "  \e[44;37;1m INFO \e[0m \e[36m{$message}\e[0m" . PHP_EOL;
        } else {
            echo "  [INFO] {$message}" . PHP_EOL;
        }
    }

    public function success(string $message): void
    {
        if ($this->decorated) {
            echo "  \e[42;30;1m DONE \e[0m \e[32m{$message}\e[0m" . PHP_EOL;
        } else {
            echo "  [DONE] {$message}" . PHP_EOL;
        }
    }

    public function warning(string $message): void
    {
        if ($this->decorated) {
            echo "  \e[43;30;1m WARN \e[0m \e[33m{$message}\e[0m" . PHP_EOL;
        } else {
            echo "  [WARN] {$message}" . PHP_EOL;
        }
    }

    public function error(string $message): void
    {
        if ($this->decorated) {
            echo "  \e[41;37;1m FAIL \e[0m \e[31m{$message}\e[0m" . PHP_EOL;
        } else {
            echo "  [FAIL] {$message}" . PHP_EOL;
        }
    }

    public function title(string $title): void
    {
        if ($this->decorated) {
            echo PHP_EOL . "  \e[1;35m⚡ {$title}\e[0m" . PHP_EOL;
            echo "  \e[38;5;238m" . str_repeat('─', mb_strlen($title) + 4) . "\e[0m" . PHP_EOL;
        } else {
            echo PHP_EOL . "--- {$title} ---" . PHP_EOL;
        }
    }

    /**
     * Display key-value detail list.
     *
     * @param array<string, string> $details
     */
    public function details(array $details): void
    {
        $maxKeyLen = max(array_map('mb_strlen', array_keys($details)));

        foreach ($details as $key => $val) {
            $paddedKey = str_pad($key, $maxKeyLen + 2, ' ');
            if ($this->decorated) {
                echo "  \e[38;5;244m{$paddedKey}\e[0m \e[1;37m{$val}\e[0m" . PHP_EOL;
            } else {
                echo "  {$paddedKey} {$val}" . PHP_EOL;
            }
        }
    }

    /**
     * Render a clean formatted table.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, string>> $rows
     */
    public function table(array $headers, array $rows): void
    {
        $columnWidths = [];

        foreach ($headers as $i => $header) {
            $columnWidths[$i] = mb_strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $len = mb_strlen((string) $cell);
                if (!isset($columnWidths[$i]) || $len > $columnWidths[$i]) {
                    $columnWidths[$i] = $len;
                }
            }
        }

        // Top Border
        $borderLine = '  +';
        foreach ($columnWidths as $w) {
            $borderLine .= str_repeat('-', $w + 2) . '+';
        }

        echo $borderLine . PHP_EOL;

        // Headers
        $headerLine = '  |';
        foreach ($headers as $i => $header) {
            $padded = str_pad($header, $columnWidths[$i], ' ');
            if ($this->decorated) {
                $headerLine .= " \e[1;36m{$padded}\e[0m |";
            } else {
                $headerLine .= " {$padded} |";
            }
        }
        echo $headerLine . PHP_EOL;

        // Separator
        echo $borderLine . PHP_EOL;

        // Rows
        foreach ($rows as $row) {
            $rowLine = '  |';
            foreach ($row as $i => $cell) {
                $padded = str_pad((string) $cell, $columnWidths[$i], ' ');
                $rowLine .= " {$padded} |";
            }
            echo $rowLine . PHP_EOL;
        }

        // Bottom Border
        echo $borderLine . PHP_EOL;
    }

    /**
     * Render a sleek progress bar.
     */
    public function progressBar(int $current, int $total, string $label = 'Processing'): void
    {
        $percentage = (int) round(($current / max($total, 1)) * 100);
        $barLen = 30;
        $completed = (int) round(($percentage / 100) * $barLen);
        $remaining = $barLen - $completed;

        $bar = str_repeat('█', $completed) . str_repeat('░', $remaining);

        if ($this->decorated) {
            echo "\r  \e[36m{$label}\e[0m [\e[32m{$bar}\e[0m] \e[1;33m{$percentage}%\e[0m ({$current}/{$total})";
            if ($current >= $total) {
                echo PHP_EOL;
            }
        } else {
            echo "\r  {$label} [{$bar}] {$percentage}% ({$current}/{$total})";
            if ($current >= $total) {
                echo PHP_EOL;
            }
        }
    }
}
