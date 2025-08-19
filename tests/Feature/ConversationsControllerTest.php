<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConversationsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test repository directories
        $basePath = storage_path('app/private/repositories/base/ShipTown');
        $hotPath = storage_path('app/private/repositories/hot/ShipTown');

        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        if (! is_dir($hotPath)) {
            mkdir($hotPath, 0755, true);
        }

        // Initialize base as a git repository
        exec('cd '.escapeshellarg($basePath).' && git init 2>/dev/null');
        exec('cd '.escapeshellarg($basePath).' && git config user.email "test@example.com" 2>/dev/null');
        exec('cd '.escapeshellarg($basePath).' && git config user.name "Test User" 2>/dev/null');
        exec('cd '.escapeshellarg($basePath).' && touch README.md && git add . && git commit -m "Initial commit" 2>/dev/null');
        exec('cd '.escapeshellarg($basePath).' && git branch -M main 2>/dev/null');

        // Initialize hot as a git repository too (it will be moved later)
        exec('cd '.escapeshellarg($hotPath).' && git init 2>/dev/null');
        exec('cd '.escapeshellarg($hotPath).' && git config user.email "test@example.com" 2>/dev/null');
        exec('cd '.escapeshellarg($hotPath).' && git config user.name "Test User" 2>/dev/null');
        exec('cd '.escapeshellarg($hotPath).' && touch README.md && git add . && git commit -m "Initial commit" 2>/dev/null');
        exec('cd '.escapeshellarg($hotPath).' && git branch -M main 2>/dev/null');

        // Add fake remote to avoid fetch errors
        exec('cd '.escapeshellarg($hotPath).' && git remote add origin https://github.com/test/test.git 2>/dev/null');
        exec('cd '.escapeshellarg($hotPath).' && git symbolic-ref refs/remotes/origin/HEAD refs/remotes/origin/main 2>/dev/null');
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $basePath = storage_path('app/private/repositories/base/ShipTown');
        $hotPath = storage_path('app/private/repositories/hot/ShipTown');

        if (is_dir($basePath)) {
            exec('rm -rf '.escapeshellarg($basePath));
        }
        if (is_dir($hotPath)) {
            exec('rm -rf '.escapeshellarg($hotPath));
        }

        parent::tearDown();
    }

    public function test_example(): void
    {
        $user = User::factory()->create();

        $number = rand(1, 1000);

        $message = 'Hi! Multiply me by 2 this number: '.$number;
        $response = $this->actingAs($user)
            ->get('/claude/new?repository=ShipTown&message='.Str::toBase64($message));

        $conversation = Conversation::query()->latest()->first();

        $this->assertNotNull($conversation, 'Conversation should be created');
        $this->assertEquals($message, $conversation->message);
        $this->assertEquals('ShipTown', $conversation->repository);

        $response->assertStatus(302);
    }
}
