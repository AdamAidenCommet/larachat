<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CopyRepositoryToHotJob;
use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CopyRepositoryToHotJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated()
    {
        $repository = Repository::factory()->create();
        $job = new CopyRepositoryToHotJob($repository);

        $this->assertInstanceOf(CopyRepositoryToHotJob::class, $job);
    }

    public function test_job_deletes_repository_when_base_directory_missing()
    {
        // Create a repository in the database
        $repository = Repository::factory()->create([
            'name' => 'test-repo-missing',
            'local_path' => 'repositories/base/test-repo-missing',
        ]);

        // Ensure the base directory doesn't exist
        $basePath = storage_path('app/private/repositories/base/test-repo-missing');
        if (is_dir($basePath)) {
            File::deleteDirectory($basePath);
        }

        // Run the job - it should complete successfully but delete the repository
        $job = new CopyRepositoryToHotJob('test-repo-missing');
        $job->handle();

        // Check that the repository was deleted from the database
        $this->assertDatabaseMissing('repositories', [
            'id' => $repository->id,
        ]);
    }

    public function test_job_skips_blank_repository()
    {
        $job = new CopyRepositoryToHotJob('');

        // This should not throw an exception
        $job->handle();

        $this->assertTrue(true); // Just to have an assertion
    }
}
