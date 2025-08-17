<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\ClaudeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendClaudeMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Conversation $conversation;
    protected string $message;

    public function __construct(Conversation $conversation, string $message)
    {
        $this->conversation = $conversation;
        $this->message = $message;
    }

    public function handle(): void
    {
        try {
            // Validate conversation exists and is valid
            if (!$this->conversation->exists) {
                Log::warning('Conversation no longer exists', [
                    'conversation_id' => $this->conversation->id
                ]);
                return;
            }

            // Check if project directory exists (if specified)
            if ($this->conversation->project_directory) {
                // If project_directory starts with absolute path, use it directly
                // Otherwise treat it as relative to storage_path
                if (str_starts_with($this->conversation->project_directory, '/')) {
                    $projectPath = $this->conversation->project_directory;
                } else {
                    $projectPath = storage_path($this->conversation->project_directory);
                }
                
                if (!is_dir($projectPath)) {
                    Log::error('Project directory does not exist', [
                        'conversation_id' => $this->conversation->id,
                        'project_directory' => $this->conversation->project_directory,
                        'full_path' => $projectPath
                    ]);
                    
                    $this->conversation->update([
                        'is_processing' => false,
                        'error_message' => 'Project directory not found: ' . $this->conversation->project_directory
                    ]);
                    return;
                }
            }

            // Filename should already be set by the controller
            $filename = $this->conversation->filename;
            
            // If for some reason it's not set, generate it
            if (!$filename) {
                $timestamp = now()->format('Y-m-d\TH-i-s');
                $tempId = substr(uniqid(), -12);
                $filename = "claude-sessions/{$timestamp}-session-{$tempId}.json";
                $this->conversation->update(['filename' => $filename]);
                
                // Also save the user message if we had to generate filename
                ClaudeService::saveUserMessage(
                    $this->message,
                    $filename,
                    $this->conversation->claude_session_id,
                    $this->conversation->project_directory
                );
            }
            // Note: User message is already saved by the controller synchronously

            // Create a progress callback to update the conversation in real-time
            $progressCallback = function ($type, $data) {
                if ($type === 'sessionId' && !$this->conversation->claude_session_id) {
                    $this->conversation->update(['claude_session_id' => $data]);
                    Log::info('Updated conversation with session ID', [
                        'conversation_id' => $this->conversation->id,
                        'sessionId' => $data
                    ]);
                } elseif ($type === 'response') {
                    // Update the conversation's updated_at timestamp to signal new content
                    $this->conversation->touch();
                    
                    Log::debug('Progress update', [
                        'conversation_id' => $this->conversation->id,
                        'filename' => $data['filename'],
                        'responseCount' => $data['responseCount']
                    ]);
                }
            };

            $result = ClaudeService::processInBackground(
                $this->message,
                '--permission-mode bypassPermissions',
                $this->conversation->claude_session_id,
                $filename,
                $this->conversation->project_directory,
                $progressCallback
            );

            // Update conversation with the session ID if extracted (in case callback didn't catch it)
            if ($result['sessionId'] && !$this->conversation->fresh()->claude_session_id) {
                $this->conversation->update(['claude_session_id' => $result['sessionId']]);
            }

            // Filename is already set at the beginning, no need to update it again
            
            // Add a small delay to ensure the file has been completely written
            // This prevents the frontend from seeing is_processing = false before the response is ready
            sleep(1);
            
            // Mark conversation as no longer processing
            $this->conversation->update(['is_processing' => false]);

            Log::info('Background Claude processing completed', [
                'conversation_id' => $this->conversation->id,
                'success' => $result['success'],
                'sessionId' => $result['sessionId']
            ]);
        } catch (\Exception $e) {
            // In case of error, mark as not processing
            $this->conversation->update(['is_processing' => false]);

            Log::error('Error in SendClaudeMessageJob', [
                'conversation_id' => $this->conversation->id,
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw to let the queue handle retry logic
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendClaudeMessageJob failed permanently', [
            'conversation_id' => $this->conversation->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->conversation->update([
            'is_processing' => false,
            'error_message' => 'Failed to send message to Claude: ' . $exception->getMessage()
        ]);
    }
}
