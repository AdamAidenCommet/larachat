<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessGitHubWebhook;
use App\Models\GitHubWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProcessGitHubWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_event_is_processed()
    {
        Log::shouldReceive('info')
            ->withAnyArgs()
            ->atLeast()->once();

        $webhookLog = GitHubWebhookLog::factory()->create([
            'event_type' => 'push',
            'status' => 'processing',
        ]);

        $data = [
            'repository' => ['full_name' => 'test/repo'],
            'pusher' => ['name' => 'testuser'],
            'commits' => [
                ['id' => 'abc123', 'message' => 'Test commit'],
            ],
            'ref' => 'refs/heads/main',
        ];

        $job = new ProcessGitHubWebhook($webhookLog, 'push', $data);
        $job->handle();

        $this->assertEquals('success', $webhookLog->fresh()->status);
    }

    public function test_pull_request_event_is_processed()
    {
        Log::shouldReceive('info')
            ->withAnyArgs()
            ->atLeast()->once();

        $webhookLog = GitHubWebhookLog::factory()->create([
            'event_type' => 'pull_request',
            'status' => 'processing',
        ]);

        $data = [
            'action' => 'opened',
            'repository' => ['full_name' => 'test/repo'],
            'pull_request' => [
                'number' => 123,
                'title' => 'Test PR',
                'user' => ['login' => 'testuser'],
            ],
        ];

        $job = new ProcessGitHubWebhook($webhookLog, 'pull_request', $data);
        $job->handle();

        $this->assertEquals('success', $webhookLog->fresh()->status);
    }

    public function test_issues_event_is_processed()
    {
        Log::shouldReceive('info')
            ->withAnyArgs()
            ->atLeast()->once();

        $webhookLog = GitHubWebhookLog::factory()->create([
            'event_type' => 'issues',
            'status' => 'processing',
        ]);

        $data = [
            'action' => 'opened',
            'repository' => ['full_name' => 'test/repo'],
            'issue' => [
                'number' => 456,
                'title' => 'Test Issue',
                'user' => ['login' => 'testuser'],
            ],
        ];

        $job = new ProcessGitHubWebhook($webhookLog, 'issues', $data);
        $job->handle();

        $this->assertEquals('success', $webhookLog->fresh()->status);
    }

    public function test_release_event_is_processed()
    {
        Log::shouldReceive('info')
            ->withAnyArgs()
            ->atLeast()->once();

        $webhookLog = GitHubWebhookLog::factory()->create([
            'event_type' => 'release',
            'status' => 'processing',
        ]);

        $data = [
            'action' => 'published',
            'repository' => ['full_name' => 'test/repo'],
            'release' => [
                'tag_name' => 'v1.0.0',
                'name' => 'Version 1.0.0',
                'author' => ['login' => 'testuser'],
            ],
        ];

        $job = new ProcessGitHubWebhook($webhookLog, 'release', $data);
        $job->handle();

        $this->assertEquals('success', $webhookLog->fresh()->status);
    }

    public function test_unhandled_event_is_logged()
    {
        Log::shouldReceive('info')
            ->withAnyArgs()
            ->atLeast()->once();

        $webhookLog = GitHubWebhookLog::factory()->create([
            'event_type' => 'unknown_event',
            'status' => 'processing',
        ]);

        $data = [];

        $job = new ProcessGitHubWebhook($webhookLog, 'unknown_event', $data);
        $job->handle();

        $this->assertEquals('success', $webhookLog->fresh()->status);
    }
}
