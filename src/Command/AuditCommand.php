<?php

declare(strict_types=1);

namespace Switch\Console\Command;

use Switch\Foundation\Sentinel\Sentinel;

class AuditCommand extends Command
{
    protected string $signature = 'audit {--json : Output audit report as JSON} {--strict : Exit with error code if any warnings exist}';
    protected string $description = 'Run Switch Sentinel security, threat, SQL, and production health audit';
    protected string $category = 'Security';

    public function handle(): int
    {
        $basePath = defined('SWITCH_BASE_PATH') ? constant('SWITCH_BASE_PATH') : getcwd();

        $config = [];
        if (class_exists(\Switch\Config\Config::class)) {
            try {
                $config = [
                    'app' => [
                        'env' => config('app.env', getenv('APP_ENV') ?: 'development'),
                        'debug' => config('app.debug', getenv('APP_DEBUG') === 'true'),
                        'key' => config('app.key', getenv('APP_KEY') ?: ''),
                    ],
                    'session' => [
                        'http_only' => config('session.http_only', true),
                        'same_site' => config('session.same_site', 'lax'),
                    ],
                ];
            } catch (\Throwable) {
                // Default fallback
            }
        }

        $report = Sentinel::audit($basePath, $config);

        if ($this->hasOption('json')) {
            $formatted = [
                'score' => $report['score'],
                'grade' => $report['grade'],
                'is_healthy' => $report['is_healthy'],
                'counts' => $report['counts'],
                'results' => array_map(fn($r) => $r->toArray(), $report['results']),
            ];
            echo json_encode($formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            return $report['is_healthy'] ? 0 : 1;
        }

        $this->title('SWITCH SENTINEL — SECURITY & HEALTH AUDIT');

        $rows = [];
        foreach ($report['results'] as $res) {
            $badge = match ($res->level) {
                'CRITICAL' => "\e[41;97m CRITICAL \e[0m",
                'WARNING' => "\e[43;30m WARNING  \e[0m",
                'INFO' => "\e[44;97m INFO     \e[0m",
                default => "\e[42;30m PASS     \e[0m",
            };

            $remediation = $res->remediation ? "\e[36m" . $res->remediation . "\e[0m" : "\e[90m—\e[0m";
            $rows[] = [$badge, $res->category, $res->title, $remediation];
        }

        $this->table(['Status', 'Category', 'Check Title', 'Remediation / Fix'], $rows);

        $this->line();
        $scoreColor = $report['score'] >= 90 ? '32' : ($report['score'] >= 75 ? '33' : '31');
        $this->line("  \e[1mSecurity & Health Score:\e[0m \e[{$scoreColor};1m{$report['score']}/100 ({$report['grade']})\e[0m");
        $this->line("  \e[90mCritical: {$report['counts']['critical']} | Warnings: {$report['counts']['warning']} | Info: {$report['counts']['info']} | Passed: {$report['counts']['pass']}\e[0m");
        $this->line();

        if ($report['counts']['critical'] > 0) {
            $this->error('Audit failed: Critical security/production vulnerabilities detected!');
            return 1;
        }

        if ($this->hasOption('strict') && $report['counts']['warning'] > 0) {
            $this->warning('Audit completed with warnings in --strict mode.');
            return 1;
        }

        $this->success('System and security checks passed successfully!');
        return 0;
    }
}
