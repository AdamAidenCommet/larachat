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

        // For blank repository, ensure the base directory exists
        if (empty($this->conversation->repository)) {
            $baseDir = storage_path('app/private/repositories/base');

            // Create base directory if it doesn't exist
            if (! File::exists($baseDir)) {
                File::makeDirectory($baseDir, 0755, true);

                // Initialize as a git repository
                Process::path($baseDir)->run('git init');
                Process::path($baseDir)->run('git config user.name "Lara"');
                Process::path($baseDir)->run('git config user.email "lara@example.com"');

                // Create an initial commit
                File::put($baseDir.'/.gitkeep', '');
                Process::path($baseDir)->run('git add .');
                Process::path($baseDir)->run('git commit -m "Initial commit"');

                Log::info('InitializeConversationSessionJob: Created base directory for blank repository', [
                    'base_dir' => $baseDir,
                ]);
            }

            Log::info('InitializeConversationSessionJob: Using base directory for blank repository', [
                'project_directory' => $this->conversation->project_directory,
            ]);
            // For blank repositories, we're done - the base directory is already set up
        } else {
            // Handle non-blank repositories
            $from = storage_path('app/private/repositories/hot/'.$this->conversation->repository);

            if (! File::exists($from)) {
                // Check if the base repository exists before trying to copy
                $basePath = storage_path('app/private/repositories/base/'.$this->conversation->repository);
                if (! File::exists($basePath)) {
                    Log::error('InitializeConversationSessionJob: Base repository does not exist', [
                        'repository' => $this->conversation->repository,
                        'base_path' => $basePath,
                    ]);

                    // Mark conversation as failed with error message
                    $this->conversation->update([
                        'is_processing' => false,
                        'error_message' => "Repository '{$this->conversation->repository}' not found. It may have been deleted.",
                    ]);

                    // Clean up the repository record if it exists
                    $repository = \App\Models\Repository::where('name', $this->conversation->repository)->first();
                    if ($repository) {
                        $repository->delete();
                        Log::info('InitializeConversationSessionJob: Cleaned up orphaned repository record', [
                            'repository_name' => $this->conversation->repository,
                        ]);
                    }

                    return;
                }

                // Try to copy from base to hot
                try {
                    CopyRepositoryToHotJob::dispatchSync($this->conversation->repository);
                } catch (\Exception $e) {
                    Log::error('InitializeConversationSessionJob: Failed to copy repository to hot', [
                        'repository' => $this->conversation->repository,
                        'error' => $e->getMessage(),
                    ]);

                    $this->conversation->update([
                        'is_processing' => false,
                        'error_message' => 'Failed to prepare repository: '.$e->getMessage(),
                    ]);

                    return;
                }

                // Check again after the copy job
                if (! File::exists($from)) {
                    Log::error('InitializeConversationSessionJob: Repository not found after copy attempt', [
                        'repository' => $this->conversation->repository,
                        'from' => $from,
                    ]);

                    // Mark conversation as failed
                    $this->conversation->update([
                        'is_processing' => false,
                        'error_message' => 'Failed to prepare repository for conversation',
                    ]);

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
            if (! File::exists($parentDir)) {
                File::makeDirectory($parentDir, 0755, true);
            }

            File::moveDirectory($from, $to, true);

            // Verify the directory exists after move
            if (! File::exists($to)) {
                Log::error('InitializeConversationSessionJob: Failed to move repository directory', [
                    'from' => $from,
                    'to' => $to,
                    'repository' => $this->conversation->repository,
                ]);

                // Mark conversation as failed
                $this->conversation->update(['is_processing' => false]);

                return;
            }

            // Run the repository's deploy script from database if it exists
            $repository = Repository::where('name', $this->conversation->repository)->first();
            if ($repository && $repository->deploy_script) {
                Log::info('InitializeConversationSessionJob: Running deploy script from database', [
                    'repository' => $this->conversation->repository,
                    'project_directory' => $to,
                ]);

                try {
                    $result = Process::path($to)
                        ->timeout(300) // 5 minutes timeout
                        ->env([
                            'PATH' => '/Users/customer/Library/Application Support/Herd/bin:/Users/customer/Library/Application Support/Herd/config/nvm/versions/node/v20.19.4/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin',
                            'HOME' => '/Users/customer',
                            'USER' => 'customer',
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
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('InitializeConversationSessionJob: Successfully moved repository', [
                'repository' => $this->conversation->repository,
                'project_directory' => $this->conversation->project_directory,
            ]);
        }
    }
}
