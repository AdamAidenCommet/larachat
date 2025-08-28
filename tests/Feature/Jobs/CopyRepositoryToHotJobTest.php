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

    public function test_job_skips_blank_repository()
    {
        $job = new CopyRepositoryToHotJob('');

        // This should not throw an exception
        $job->handle();

        $this->assertTrue(true); // Just to have an assertion
    }
}
