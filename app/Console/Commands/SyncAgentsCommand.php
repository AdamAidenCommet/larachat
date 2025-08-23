<?php

namespace App\Console\Commands;

use App\Services\AgentSyncService;
use Illuminate\Console\Command;

class SyncAgentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all agents from database to .claude/agents directory';

    /**
     * Execute the console command.
     */
    public function handle(AgentSyncService $service): int
    {
        $this->info('Starting agent synchronization...');
        
        try {
            $service->syncAll();
            $this->info('All agents have been synchronized to .claude/agents/');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to sync agents: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}