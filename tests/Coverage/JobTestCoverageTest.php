<?php

namespace Tests\Coverage;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class JobTestCoverageTest extends TestCase
{
    public function test_all_jobs_have_tests(): void
    {
        $jobClasses = $this->getJobClasses();
        $testClasses = $this->getTestClasses();
        $missingTests = [];

        foreach ($jobClasses as $jobClass) {
            $expectedTestClass = $this->getExpectedTestClassName($jobClass);

            if (! in_array($expectedTestClass, $testClasses)) {
                $missingTests[] = $jobClass;
            }
        }

        $this->assertEmpty(
            $missingTests,
            "The following jobs do not have tests:\n".implode("\n", $missingTests)
        );
    }

    private function getJobClasses(): array
    {
        $jobsPath = base_path('app/Jobs');
        $jobs = [];

        if (! is_dir($jobsPath)) {
            return $jobs;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($jobsPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace(base_path().'/', '', $file->getPathname());
                $className = $this->pathToClassName($relativePath);

                if (class_exists($className)) {
                    $jobs[] = $className;
                }
            }
        }

        return $jobs;
    }

    private function getTestClasses(): array
    {
        $testPaths = [
            base_path('tests/Unit'),
            base_path('tests/Feature'),
        ];

        $tests = [];

        foreach ($testPaths as $testPath) {
            if (! is_dir($testPath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($testPath)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace(base_path().'/', '', $file->getPathname());
                    $className = $this->pathToClassName($relativePath);

                    if (class_exists($className)) {
                        $tests[] = $className;
                    }
                }
            }
        }

        return $tests;
    }

    private function pathToClassName(string $path): string
    {
        $path = str_replace('/', '\\', $path);
        $path = str_replace('.php', '', $path);

        if (str_starts_with($path, 'app\\')) {
            $path = 'App\\'.substr($path, 4);
        } elseif (str_starts_with($path, 'tests\\')) {
            $path = 'Tests\\'.substr($path, 6);
        }

        return $path;
    }

    private function getExpectedTestClassName(string $jobClassName): string
    {
        $jobName = class_basename($jobClassName);

        $possibleTestNames = [
            'Tests\\Unit\\Jobs\\'.$jobName.'Test',
            'Tests\\Feature\\Jobs\\'.$jobName.'Test',
            'Tests\\Unit\\'.$jobName.'Test',
            'Tests\\Feature\\'.$jobName.'Test',
        ];

        foreach ($possibleTestNames as $testName) {
            if (class_exists($testName)) {
                return $testName;
            }
        }

        return 'Tests\\Unit\\Jobs\\'.$jobName.'Test';
    }
}
