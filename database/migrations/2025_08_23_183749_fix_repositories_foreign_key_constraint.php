<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Top o' the mornin'! This migration fixes the foreign key constraint issue
     * that prevents dropping the user_id column in MySQL environments.
     * Sure, it's a bit of craic dealing with these constraints!
     */
    public function up(): void
    {
        // Skip for SQLite as it doesn't have the same constraint issues
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        // Check if the repositories table exists
        if (! Schema::hasTable('repositories')) {
            return;
        }
        
        // Check if the user_id column still exists (migration might have already run)
        if (! Schema::hasColumn('repositories', 'user_id')) {
            return;
        }
        
        Schema::table('repositories', function (Blueprint $table) {
            // For MySQL, we need to handle foreign key constraints properly
            // Drop the foreign key constraint first (if it exists)
            try {
                // The foreign key name might be different, let's check
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'repositories' 
                    AND COLUMN_NAME = 'user_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $foreignKey) {
                    $table->dropForeign($foreignKey->CONSTRAINT_NAME);
                }
            } catch (\Exception $e) {
                // If the foreign key doesn't exist, that's grand
            }
            
            // Now drop the unique index if it exists
            try {
                $table->dropIndex('repositories_user_url_unique');
            } catch (\Exception $e) {
                // If the index doesn't exist, no bother
            }
            
            // Finally, drop the user_id column
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Ah, to be sure, we're putting everything back the way it was!
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        Schema::table('repositories', function (Blueprint $table) {
            // Add the user_id column back
            if (! Schema::hasColumn('repositories', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                
                // Recreate the unique constraint
                $table->unique(['user_id', 'url'], 'repositories_user_url_unique');
            }
        });
    }
};
