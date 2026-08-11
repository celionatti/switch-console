<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class ClearCacheCommand extends Command
{
    protected string $signature = 'clear:cache';
    protected string $description = 'Clear compiled views, route cache, and application cache';
    protected string $category = 'Maintenance';

    public function handle(): int
    {
        $this->title("Clearing Application Cache...");

        $tempViewDir = sys_get_temp_dir() . '/switch_views';
        $clearedViews = 0;

        if (is_dir($tempViewDir)) {
            $files = glob($tempViewDir . '/*.php');
            if ($files) {
                foreach ($files as $f) {
                    unlink($f);
                    $clearedViews++;
                }
            }
        }

        $basePath = getcwd() ?: '.';
        $appCacheDir = $basePath . '/storage/framework/cache';
        if (is_dir($appCacheDir)) {
            $files = glob($appCacheDir . '/*');
            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f)) unlink($f);
                }
            }
        }

        $this->details([
            'View Cache' => "{$clearedViews} compiled templates cleared",
            'App Cache' => "Storage cache purged",
            'Status' => "Clean",
        ]);

        $this->success("Application cache cleared successfully!");
        return 0;
    }
}
