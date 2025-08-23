<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AgentSyncService
{
    protected string $agentsPath;

    public function __construct()
    {
        $this->agentsPath = base_path('.claude/agents');
    }

    /**
     * Sync a single agent to filesystem
     */
    public function syncAgent(Agent $agent): void
    {
        $this->ensureDirectoryExists();
        
        $slug = $this->generateSlug($agent);
        $filePath = $this->getAgentFilePath($slug);
        $content = $this->generateAgentFileContent($agent);
        
        File::put($filePath, $content);
        
        // Update agent slug if not set
        if ($agent->slug !== $slug) {
            $agent->slug = $slug;
            $agent->saveQuietly();
        }
    }

    /**
     * Remove agent file from filesystem
     */
    public function removeAgent(Agent $agent): void
    {
        if ($agent->slug) {
            $filePath = $this->getAgentFilePath($agent->slug);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }

    /**
     * Sync all agents from database to filesystem
     */
    public function syncAll(): void
    {
        $this->ensureDirectoryExists();
        $this->cleanDirectory();
        
        Agent::all()->each(function (Agent $agent) {
            $this->syncAgent($agent);
        });
    }

    /**
     * Generate slug for agent
     */
    protected function generateSlug(Agent $agent): string
    {
        if ($agent->slug) {
            return $agent->slug;
        }
        
        return Str::slug($agent->name);
    }

    /**
     * Get file path for agent
     */
    protected function getAgentFilePath(string $slug): string
    {
        return $this->agentsPath . '/' . $slug . '.md';
    }

    /**
     * Generate markdown content for agent file
     */
    protected function generateAgentFileContent(Agent $agent): string
    {
        $frontmatter = [
            'name' => $this->generateSlug($agent),
            'description' => $agent->description ?? "AI assistant for {$agent->name}",
        ];
        
        if ($agent->tools) {
            $frontmatter['tools'] = $agent->tools;
        }
        
        $yaml = "---\n";
        foreach ($frontmatter as $key => $value) {
            $yaml .= "{$key}: {$value}\n";
        }
        $yaml .= "---\n\n";
        
        return $yaml . $agent->prompt;
    }

    /**
     * Ensure agents directory exists
     */
    protected function ensureDirectoryExists(): void
    {
        if (!File::exists($this->agentsPath)) {
            File::makeDirectory($this->agentsPath, 0755, true);
        }
    }

    /**
     * Clean the agents directory
     */
    protected function cleanDirectory(): void
    {
        $files = File::glob($this->agentsPath . '/*.md');
        foreach ($files as $file) {
            File::delete($file);
        }
    }
}