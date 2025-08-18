<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RetryFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:retry-smart 
                            {--dry-run : Preview what would be retried without making changes}
                            {--clean : Remove jobs that cannot be retried}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Intelligently retry failed jobs, skipping those with missing resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $clean = $this->option('clean');
        
        $this->info('Analyzing failed jobs...');
        
        $failedJobs = DB::table('failed_jobs')->get();
        
        if ($failedJobs->isEmpty()) {
            $this->info('✓ No failed jobs found');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$failedJobs->count()} failed job(s)");
        $this->newLine();
        
        $retriable = [];
        $unretriable = [];
        
        foreach ($failedJobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobName = $payload['displayName'] ?? 'Unknown Job';
            
            // Check if job is retriable
            if ($this->isJobRetriable($job, $payload)) {
                $retriable[] = $job;
                $this->line("✓ Can retry: {$jobName} (ID: {$job->id})");
            } else {
                $unretriable[] = $job;
                $this->warn("✗ Cannot retry: {$jobName} (ID: {$job->id})");
                $this->line("  Reason: " . $this->getFailureReason($job, $payload));
            }
        }
        
        $this->newLine();
        
        // Handle retriable jobs
        if (!empty($retriable)) {
            $this->info("Retriable jobs: " . count($retriable));
            
            if (!$dryRun) {
                if ($this->confirm('Retry ' . count($retriable) . ' job(s)?', true)) {
                    foreach ($retriable as $job) {
                        try {
                            Artisan::call('queue:retry', ['id' => [$job->uuid ?? $job->id]]);
                            $this->line("  Retried job ID: {$job->id}");
                        } catch (\Exception $e) {
                            $this->error("  Failed to retry job ID {$job->id}: {$e->getMessage()}");
                        }
                    }
                    $this->info('✓ Jobs queued for retry');
                }
            }
        }
        
        // Handle unretriable jobs
        if (!empty($unretriable) && $clean) {
            $this->newLine();
            $this->warn("Unretriable jobs: " . count($unretriable));
            
            if (!$dryRun) {
                if ($this->confirm('Remove ' . count($unretriable) . ' unretriable job(s)?', false)) {
                    foreach ($unretriable as $job) {
                        DB::table('failed_jobs')->where('id', $job->id)->delete();
                        $this->line("  Removed job ID: {$job->id}");
                    }
                    $this->info('✓ Unretriable jobs removed');
                }
            }
        }
        
        // Summary
        $this->newLine();
        if ($dryRun) {
            $this->info('Dry run complete. No changes made.');
            $this->info('Run without --dry-run to execute changes');
        } else {
            $this->info('Job processing complete');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Check if a job is retriable based on resource availability
     */
    protected function isJobRetriable($job, array $payload): bool
    {
        $jobName = $payload['displayName'] ?? '';
        
        // Check CopyRepositoryToHotJob
        if (str_contains($jobName, 'CopyRepositoryToHotJob')) {
            try {
                $jobData = unserialize($payload['data']['command']);
                $repository = $jobData->repository ?? null;
                
                if ($repository) {
                    $basePath = storage_path('app/private/repositories/base/' . $repository);
                    return File::exists($basePath);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to check CopyRepositoryToHotJob retriability', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        // Check InitializeConversationSessionJob
        if (str_contains($jobName, 'InitializeConversationSessionJob')) {
            try {
                $jobData = unserialize($payload['data']['command']);
                $conversation = $jobData->conversation ?? null;
                
                if ($conversation && !empty($conversation->repository)) {
                    $basePath = storage_path('app/private/repositories/base/' . $conversation->repository);
                    return File::exists($basePath);
                }
                
                // For blank repositories, always retriable
                return true;
            } catch (\Exception $e) {
                Log::warning('Failed to check InitializeConversationSessionJob retriability', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        // Check SendClaudeMessageJob
        if (str_contains($jobName, 'SendClaudeMessageJob')) {
            try {
                $jobData = unserialize($payload['data']['command']);
                $conversation = $jobData->conversation ?? null;
                
                if ($conversation) {
                    // Check if conversation still exists
                    $conversationExists = DB::table('conversations')
                        ->where('id', $conversation->id)
                        ->exists();
                    
                    if (!$conversationExists) {
                        return false;
                    }
                    
                    // Check if project directory exists if specified
                    if ($conversation->project_directory) {
                        $projectPath = str_starts_with($conversation->project_directory, '/')
                            ? $conversation->project_directory
                            : storage_path($conversation->project_directory);
                        
                        return File::exists($projectPath);
                    }
                    
                    return true;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to check SendClaudeMessageJob retriability', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        // For other jobs, check exception message
        $exception = $job->exception ?? '';
        
        // Don't retry if it's a permanent failure
        $permanentErrors = [
            'does not exist',
            'not found',
            'permission denied',
            'access denied',
            'invalid credentials',
        ];
        
        foreach ($permanentErrors as $error) {
            if (stripos($exception, $error) !== false) {
                return false;
            }
        }
        
        // Default to retriable for unknown job types
        return true;
    }
    
    /**
     * Get a human-readable failure reason
     */
    protected function getFailureReason($job, array $payload): string
    {
        $jobName = $payload['displayName'] ?? '';
        
        // Check for repository jobs
        if (str_contains($jobName, 'CopyRepositoryToHotJob') || 
            str_contains($jobName, 'InitializeConversationSessionJob')) {
            try {
                $jobData = unserialize($payload['data']['command']);
                $repository = $jobData->repository ?? 
                             ($jobData->conversation->repository ?? null);
                
                if ($repository) {
                    $basePath = storage_path('app/private/repositories/base/' . $repository);
                    if (!File::exists($basePath)) {
                        return "Repository '{$repository}' not found in filesystem";
                    }
                }
            } catch (\Exception $e) {
                // Fall through to check exception
            }
        }
        
        // Check exception message
        $exception = $job->exception ?? '';
        if ($exception) {
            $lines = explode("\n", $exception);
            return $lines[0] ?? 'Unknown error';
        }
        
        return 'Unknown reason';
    }
}