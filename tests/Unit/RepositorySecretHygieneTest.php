<?php

namespace Tests\Unit;

use Tests\TestCase;

class RepositorySecretHygieneTest extends TestCase
{
    public function test_only_example_env_files_may_be_tracked(): void
    {
        if (! function_exists('exec')) {
            $this->markTestSkipped('exec() is required to inspect tracked files.');
        }

        $trackedFiles = [];
        $exitCode = 0;

        exec('git ls-files -- .env .env.* .env*', $trackedFiles, $exitCode);

        $this->assertSame(0, $exitCode, 'Unable to inspect tracked env files.');

        $trackedFiles = array_values(array_filter(array_map('trim', $trackedFiles)));
        $allowedFiles = ['.env.example', '.env.production.example'];

        foreach ($trackedFiles as $trackedFile) {
            $this->assertContains($trackedFile, $allowedFiles);
        }

        $this->assertFileExists(base_path('.env.example'));
        $this->assertFileExists(base_path('.env.production.example'));
    }
}
