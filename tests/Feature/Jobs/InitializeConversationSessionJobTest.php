<?php

namespace Tests\Feature\Jobs;

use App\Jobs\InitializeConversationSessionJob;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitializeConversationSessionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated()
    {
        $conversation = Conversation::factory()->create();
        $job = new InitializeConversationSessionJob($conversation, 'test message');

        $this->assertInstanceOf(InitializeConversationSessionJob::class, $job);
    }
}
