<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendClaudeMessageJob;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendClaudeMessageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated()
    {
        $conversation = Conversation::factory()->create();
        $job = new SendClaudeMessageJob($conversation, 'test message');

        $this->assertInstanceOf(SendClaudeMessageJob::class, $job);
    }
}
