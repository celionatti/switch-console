<?php

declare(strict_types=1);

namespace Switch\Console\Command;

class StorageLinkCommand extends Command
{
    protected string $signature = 'storage:link';
    protected string $description = 'Create the symbolic link from "public/storage" to "storage/app/public"';
    protected string $category = 'Setup';

    public function handle(): int
    {
        $basePath = getcwd() ?: '.';
        $target = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public';
        $link = $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'storage';

        if (!is_dir($target)) {
            @mkdir($target, 0777, true);
        }

        if (file_exists($link) || is_link($link)) {
            $this->warning('The "public/storage" link already exists.');
            return 0;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $mode = is_dir($target) ? 'J' : 'H';
            exec(sprintf('mklink /%s "%s" "%s"', $mode, $link, $target), $outputLines, $returnCode);
            if ($returnCode === 0) {
                $this->success('The [public/storage] directory junction has been connected to [storage/app/public].');
                return 0;
            }

            // Fallback to PHP symlink if permissions allow
            if (@symlink($target, $link)) {
                $this->success('The [public/storage] link has been connected to [storage/app/public].');
                return 0;
            }

            $this->error('Failed to create symbolic link. Please run as Administrator or check directory permissions.');
            return 1;
        }

        if (@symlink($target, $link)) {
            $this->success('The [public/storage] link has been connected to [storage/app/public].');
            return 0;
        }

        $this->error('Failed to create symbolic link from "public/storage" to "storage/app/public".');
        return 1;
    }
}
