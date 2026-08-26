<?php

declare(strict_types=1);

namespace Switch\Console\Tests;

use PHPUnit\Framework\TestCase;
use Switch\Console\Application;

class AuditCommandTest extends TestCase
{
    public function testDoctorCommandRunsSuccessfully(): void
    {
        $app = new Application();
        ob_start();
        $exitCode = $app->run(['switch', 'doctor']);
        $output = ob_get_clean();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('SWITCH SYSTEM DOCTOR', $output);
        $this->assertStringContainsString('PHP Runtime Version', $output);
        $this->assertStringContainsString('Required PHP Extensions', $output);
    }

    public function testAuditCommandRunsWithJsonOption(): void
    {
        $app = new Application();
        ob_start();
        $exitCode = $app->run(['switch', 'audit', '--json']);
        $output = ob_get_clean();

        $this->assertJson($output);
        $decoded = json_decode($output, true);
        $this->assertArrayHasKey('score', $decoded);
        $this->assertArrayHasKey('grade', $decoded);
        $this->assertArrayHasKey('results', $decoded);
    }

    public function testKeyGenerateCommandRunsWithShowOption(): void
    {
        $app = new Application();
        ob_start();
        $exitCode = $app->run(['switch', 'key:generate', '--show']);
        $output = ob_get_clean();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('base64:', $output);
    }
}
