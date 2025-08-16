<?php

namespace App\Jobs;

use App\Models\Conversation;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class InitializeConversationSessionJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Conversation $conversation,
        protected string $message
    ) {}

    public function handle(): void
    {
        // Initialize session with the user's message (but no Claude response)
        $sessionData = [
            [
                "sessionId" => null,
                'role' => 'user',
                'userMessage' => $this->message,
                'timestamp' => now()->toIso8601String(),
                "isComplete" => false,
                "repositoryPath" => null,
            ]
        ];

        Storage::put($this->conversation->filename, json_encode($sessionData, JSON_PRETTY_PRINT));

        $from = storage_path('app/private/repositories/hot/' . $this->conversation->repository);

        if (!File::exists($from)) {
            CopyRepositoryToHotJob::dispatchSync($this->conversation->repository);
            
            // Check again after the copy job
            if (!File::exists($from)) {
                Log::error('InitializeConversationSessionJob: Repository not found after copy attempt', [
                    'repository' => $this->conversation->repository,
                    'from' => $from,
                ]);
                
                // Mark conversation as failed
                $this->conversation->update(['is_processing' => false]);
                return;
            }
        }

        // If project_directory starts with absolute path from PROJECT_DIRECTORY env, use it directly
        // Otherwise treat it as relative to storage_path
        $projectDir = $this->conversation->project_directory;
        if (str_starts_with($projectDir, '/')) {
            $to = $projectDir;
        } else {
            $to = storage_path($projectDir);
        }

        ray($from, $to);
        
        // Ensure the parent directory exists
        $parentDir = dirname($to);
        if (!File::exists($parentDir)) {
            File::makeDirectory($parentDir, 0755, true);
        }
        
        File::moveDirectory($from, $to, true);

        // Only run git commands if the directory exists after move
        if (!File::exists($to)) {
            Log::error('InitializeConversationSessionJob: Failed to move repository directory', [
                'from' => $from,
                'to' => $to,
                'repository' => $this->conversation->repository,
            ]);
            return;
        }

        // Update the Git repository in the moved directory using refresh-master.sh script
        try {
            $scriptPath = base_path('scripts/refresh-master.sh');
            
            // Check if the script exists
            if (!File::exists($scriptPath)) {
                Log::error('InitializeConversationSessionJob: refresh-master.sh script not found', [
                    'script_path' => $scriptPath,
                ]);
                throw new \RuntimeException('refresh-master.sh script not found');
            }
            
            // Run the refresh-master.sh script in the project directory
            $result = Process::path($to)
                ->timeout(300) // 5 minutes timeout for npm install and build
                ->run('bash ' . escapeshellarg($scriptPath));
            
            if (!$result->successful()) {
                throw new \RuntimeException("refresh-master.sh script failed. Output: " . $result->errorOutput());
            }
            
            Log::info('InitializeConversationSessionJob: Updated project repository using refresh-master.sh', [
                'repository' => $this->conversation->repository,
                'project_directory' => $this->conversation->project_directory,
                'output' => $result->output(),
            ]);
        } catch (\Exception $e) {
            Log::warning('InitializeConversationSessionJob: Could not update project repository', [
                'repository' => $this->conversation->repository,
                'project_directory' => $this->conversation->project_directory,
                'error' => $e->getMessage(),
            ]);
            
            // Continue with the conversation even if Git update fails
            // The repository is still functional, just might not be on latest
        }
    }
}