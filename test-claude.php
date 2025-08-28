#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Conversation;
use App\Jobs\SendClaudeMessageJob;

// Find or create a test conversation
$conversation = Conversation::latest()->first();

if (!$conversation) {
    echo "No conversations found. Creating a test conversation...\n";
    $conversation = Conversation::create([
        'user_id' => 1,
        'title' => 'Test CLI',
        'message' => 'Test message from CLI',
        'repository' => 'LaraChat',
        'project_directory' => '/Users/arturhanusek/PhpstormProjects/LaraChat',
        'filename' => 'claude-sessions/test-' . date('Y-m-d-H-i-s') . '.json',
        'is_processing' => true,
        'mode' => 'bypassPermissions',
        'agent_id' => null,
    ]);
}

echo "Using conversation ID: {$conversation->id}\n";
echo "Project directory: {$conversation->project_directory}\n";
echo "Filename: {$conversation->filename}\n";

// Dispatch the job
echo "Dispatching SendClaudeMessageJob...\n";
SendClaudeMessageJob::dispatchSync($conversation, "Hello Claude, this is a test message");

echo "Job completed!\n";
echo "Check storage/logs/laravel.log for details\n";