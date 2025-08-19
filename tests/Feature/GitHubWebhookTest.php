<?php

namespace Tests\Feature;

use App\Jobs\ProcessGitHubWebhook;
use App\Models\GitHubWebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GitHubWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.github.webhook_secret' => 'test-secret']);
    }

    public function test_webhook_requires_valid_signature()
    {
        $payload = json_encode(['test' => 'data']);

        $response = $this->postJson('/api/github/webhook', json_decode($payload, true));

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_webhook_accepts_valid_signature()
    {
        Queue::fake();

        $payload = json_encode([
            'repository' => ['full_name' => 'test/repo'],
            'action' => 'opened',
        ]);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'test-secret');

        $response = $this->postJson('/api/github/webhook', json_decode($payload, true), [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'pull_request',
            'X-GitHub-Delivery' => '12345-67890',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'queued']);

        Queue::assertPushed(ProcessGitHubWebhook::class);

        $this->assertDatabaseHas('github_webhook_logs', [
            'event_type' => 'pull_request',
            'delivery_id' => '12345-67890',
            'repository' => 'test/repo',
            'status' => 'processing',
        ]);
    }

    public function test_push_event_is_logged()
    {
        Queue::fake();

        $payload = json_encode([
            'ref' => 'refs/heads/main',
            'repository' => ['full_name' => 'test/repo'],
            'pusher' => ['name' => 'testuser'],
            'commits' => [
                ['id' => 'abc123', 'message' => 'Test commit'],
            ],
        ]);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'test-secret');

        $response = $this->postJson('/api/github/webhook', json_decode($payload, true), [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'push',
            'X-GitHub-Delivery' => 'push-12345',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('github_webhook_logs', [
            'event_type' => 'push',
            'delivery_id' => 'push-12345',
            'repository' => 'test/repo',
        ]);
    }

    public function test_webhook_handles_missing_repository_info()
    {
        Queue::fake();

        $payload = json_encode([
            'action' => 'created',
        ]);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'test-secret');

        $response = $this->postJson('/api/github/webhook', json_decode($payload, true), [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'star',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('github_webhook_logs', [
            'event_type' => 'star',
            'repository' => null,
        ]);
    }

    public function test_webhook_handles_invalid_json()
    {
        $invalidPayload = 'invalid-json';
        $signature = 'sha256='.hash_hmac('sha256', $invalidPayload, 'test-secret');

        $response = $this->call('POST', '/api/github/webhook', [], [], [], [
            'HTTP_X-Hub-Signature-256' => $signature,
            'HTTP_X-GitHub-Event' => 'push',
            'HTTP_X-GitHub-Delivery' => 'invalid-json-test',
        ], $invalidPayload);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid JSON payload']);
    }

    public function test_webhook_logs_are_created_with_correct_data()
    {
        Queue::fake();

        $payload = [
            'action' => 'published',
            'release' => [
                'tag_name' => 'v1.0.0',
                'name' => 'Version 1.0.0',
                'author' => ['login' => 'testuser'],
            ],
            'repository' => ['full_name' => 'test/repo'],
        ];

        $payloadJson = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $payloadJson, 'test-secret');

        $response = $this->postJson('/api/github/webhook', $payload, [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'release',
            'X-GitHub-Delivery' => 'release-123',
        ]);

        $response->assertStatus(200);

        $log = GitHubWebhookLog::where('delivery_id', 'release-123')->first();

        $this->assertNotNull($log);
        $this->assertEquals('release', $log->event_type);
        $this->assertEquals('test/repo', $log->repository);
        $this->assertEquals('processing', $log->status);
        $this->assertEquals($payload, $log->payload);
    }
}
