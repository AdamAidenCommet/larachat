#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Conversation;
use App\Jobs\InitializeConversationSessionJob;
use App\Jobs\SendClaudeMessageJob;

$timestamp = date('Y-m-d\TH-i-s') . '-' . uniqid();

// Create a new test conversation
echo "Creating a new test conversation...\n";
$conversation = Conversation::create([
    'user_id' => 1,
    'title' => 'Test CLI New',
    'message' => 'Hello Claude from test',
    'repository' => 'LaraChat',
    'project_directory' => './../../subdomains/' . $timestamp,
    'filename' => 'claude-sessions/' . $timestamp . '.json',
    'is_processing' => true,
    'mode' => 'bypassPermissions',
    'agent_id' => null,
]);

echo "Created conversation ID: {$conversation->id}\n";
echo "Project directory: {$conversation->project_directory}\n";
echo "Filename: {$conversation->filename}\n";

// Initialize the conversation session first
echo "Initializing conversation session...\n";
InitializeConversationSessionJob::dispatchSync($conversation, "Hello Claude from test");

// Now send the message
echo "Dispatching SendClaudeMessageJob...\n";
SendClaudeMessageJob::dispatchSync($conversation, "Hello Claude from test");

echo "Job completed!\n";
echo "Check storage/logs/laravel.log for details\n";

// Check if session file was created
$sessionPath = storage_path('app/' . $conversation->filename);
if (file_exists($sessionPath)) {
    echo "Session file created at: $sessionPath\n";
    $content = json_decode(file_get_contents($sessionPath), true);
    echo "Session has " . count($content) . " conversation(s)\n";
} else {
    echo "WARNING: Session file not found at: $sessionPath\n";
}