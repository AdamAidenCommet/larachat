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
     * Faith and begorrah! This migration properly handles the MySQL foreign key
     * constraint issue that's been giving us grief. We'll make sure to drop
     * the constraints in the right order, so we will!
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
        
        // Check if the user_id column still exists
        if (! Schema::hasColumn('repositories', 'user_id')) {
            // Grand! The column's already been dropped
            return;
        }
        
        // For MySQL, we need to be extra careful with the order of operations
        Schema::table('repositories', function (Blueprint $table) {
            // First, let's find ALL foreign keys on the user_id column
            // MySQL can be a bit particular about this, so it can!
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'repositories' 
                    AND COLUMN_NAME = 'user_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                // Drop each foreign key constraint
                foreach ($foreignKeys as $foreignKey) {
                    try {
                        DB::statement("ALTER TABLE repositories DROP FOREIGN KEY {$foreignKey->CONSTRAINT_NAME}");
                    } catch (\Exception $e) {
                        // If it fails, try Laravel's way
                        try {
                            $table->dropForeign([$foreignKey->CONSTRAINT_NAME]);
                        } catch (\Exception $e2) {
                            // Sure, if it doesn't exist, no worries!
                        }
                    }
                }
            } catch (\Exception $e) {
                // Try the standard Laravel foreign key name
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e2) {
                    // No foreign key? That's grand!
                }
            }
        });
        
        // Now handle the indexes in a separate schema operation
        // This prevents MySQL from getting confused about dependencies
        Schema::table('repositories', function (Blueprint $table) {
            // Drop any indexes that include the user_id column
            try {
                $indexes = DB::select("
                    SELECT DISTINCT INDEX_NAME 
                    FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'repositories' 
                    AND COLUMN_NAME = 'user_id'
                ");
                
                foreach ($indexes as $index) {
                    // Skip primary key
                    if ($index->INDEX_NAME === 'PRIMARY') {
                        continue;
                    }
                    
                    try {
                        DB::statement("ALTER TABLE repositories DROP INDEX {$index->INDEX_NAME}");
                    } catch (\Exception $e) {
                        // Try Laravel's way
                        try {
                            $table->dropIndex($index->INDEX_NAME);
                        } catch (\Exception $e2) {
                            // Ah well, we tried!
                        }
                    }
                }
            } catch (\Exception $e) {
                // Try dropping the specific unique index we know about
                try {
                    $table->dropIndex('repositories_user_url_unique');
                } catch (\Exception $e2) {
                    // If it doesn't exist, that's fine
                }
            }
        });
        
        // Finally, drop the column itself
        Schema::table('repositories', function (Blueprint $table) {
            try {
                $table->dropColumn('user_id');
            } catch (\Exception $e) {
                // If we can't drop it, log the error for debugging
                \Log::error('Failed to drop user_id column from repositories table: ' . $e->getMessage());
                throw $e; // Re-throw to fail the migration
            }
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Putting everything back the way it was, to be sure!
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        Schema::table('repositories', function (Blueprint $table) {
            // Only add the column back if it doesn't exist
            if (! Schema::hasColumn('repositories', 'user_id')) {
                $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
                
                // Check if we have a url column to recreate the unique constraint
                if (Schema::hasColumn('repositories', 'url')) {
                    $table->unique(['user_id', 'url'], 'repositories_user_url_unique');
                }
            }
        });
    }
};