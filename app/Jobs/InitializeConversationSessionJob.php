<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Repository;
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
                'sessionId' => null,
                'role' => 'user',
                'userMessage' => $this->message,
                'timestamp' => now()->toIso8601String(),
                'isComplete' => false,
                'repositoryPath' => null,
            ],
        ];

        Storage::put($this->conversation->filename, json_encode($sessionData, JSON_PRETTY_PRINT));

        if (empty($this->conversation->repository)) {
            return;
        }

        $hot = storage_path('app/private/repositories/hot/'.$this->conversation->repository);
        if (! File::exists($hot)) {
            CopyRepositoryToHotJob::dispatchSync($this->conversation->repository);
        }

        $projectDirectory = $this->conversation->project_directory;
        if (!str_starts_with($projectDirectory, '/')) {
            $projectDirectory = base_path($projectDirectory);
        }

        // Create parent directory first if it doesn't exist
        $parentDir = dirname($projectDirectory);
        if (! File::exists($parentDir)) {
            File::makeDirectory($parentDir, 0755, true);
            Log::info('InitializeConversationSessionJob: Created parent directory', ['parent_dir' => $parentDir]);
        }

        // Now move the repository
        File::moveDirectory($hot, $projectDirectory, true);

        // Verify the directory exists after move
        if (! File::exists($projectDirectory)) {
            Log::error('InitializeConversationSessionJob: Failed to move repository directory', [
                'from' => $hot,
                'to' => $projectDirectory,
                'repository' => $this->conversation->repository,
            ]);

            // Mark conversation as failed
            $this->conversation->update(['is_processing' => false]);

            return;
        }

        // Update conversation with absolute path if it was converted
        if ($projectDirectory !== $this->conversation->project_directory) {
            $this->conversation->update(['project_directory' => $projectDirectory]);
        }

        // Run the repository's deploy script from database if it exists
        $repository = Repository::where('name', $this->conversation->repository)->first();

        if ($repository && $repository->deploy_script) {
            Log::info('InitializeConversationSessionJob: Running deploy script from database', [
                'repository' => $this->conversation->repository,
                'project_directory' => $this->conversation->project_directory,
            ]);

            try {
                $result = Process::path($projectDirectory)
                    ->timeout(300) // 5 minutes timeout
                    ->env([
                        'PATH' => '/Users/arturhanusek/Library/Application Support/Herd/bin:/Users/arturhanusek/Library/Application Support/Herd/config/nvm/versions/node/v20.19.3/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin',
                        'HOME' => '/Users/arturhanusek',
                        'USER' => 'arturhanusek',
                    ])
                    ->run($repository->deploy_script);

                if ($result->successful()) {
                    Log::info('InitializeConversationSessionJob: Deploy script completed successfully', [
                        'repository' => $this->conversation->repository,
                        'output' => $result->output(),
                    ]);
                } else {
                    Log::warning('InitializeConversationSessionJob: Deploy script failed', [
                        'repository' => $this->conversation->repository,
                        'error' => $result->errorOutput(),
                        'exit_code' => $result->exitCode(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('InitializeConversationSessionJob: Exception running deploy script', [
                    'repository' => $this->conversation->repository,
                    '   error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('InitializeConversationSessionJob: Successfully moved repository', [
            'repository' => $this->conversation->repository,
            'project_directory' => $this->conversation->project_directory,
        ]);
    }
}
