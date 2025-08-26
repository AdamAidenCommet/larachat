<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScriptPermissionsTest extends TestCase
{
    /**
     * Test that all .sh scripts have proper 755 permissions
     * Faith and begorrah, let's make sure our scripts are executable!
     */
    public function test_all_shell_scripts_have_proper_permissions()
    {
        // Skip vendor directory to focus on project scripts
        $scriptPaths = [
            base_path('claude-wrapper.sh'),
            base_path('public/claude-session.sh'),
            base_path('test-webhook-modes.sh'),
            base_path('scripts/refresh-master.sh'),
            base_path('scripts/start-conversation.sh'),
            base_path('scripts/system-update.sh'),
            base_path('scripts/deploy.sh'),
            base_path('scripts/gh-create-pr.sh'),
        ];
        
        $failedScripts = [];
        
        foreach ($scriptPaths as $scriptPath) {
            if (!file_exists($scriptPath)) {
                continue; // Script might not exist in all environments, that's grand
            }
            
            $permissions = fileperms($scriptPath);
            $octalPermissions = substr(sprintf('%o', $permissions), -3);
            
            if ($octalPermissions !== '755') {
                $failedScripts[] = [
                    'path' => $scriptPath,
                    'current' => $octalPermissions,
                    'expected' => '755'
                ];
            }
        }
        
        $this->assertEmpty(
            $failedScripts,
            "The following scripts don't have proper 755 permissions:\n" .
            collect($failedScripts)->map(function ($script) {
                return "  - {$script['path']}: {$script['current']} (expected: {$script['expected']})";
            })->implode("\n")
        );
    }
    
    /**
     * Test that we have at least some shell scripts in the project
     * Sure, we don't want to be left without any scripts at all!
     */
    public function test_shell_scripts_exist()
    {
        $projectScripts = glob(base_path('scripts/*.sh'));
        
        $this->assertNotEmpty(
            $projectScripts,
            "No shell scripts found in the scripts directory. That's a bit odd, isn't it?"
        );
    }
}