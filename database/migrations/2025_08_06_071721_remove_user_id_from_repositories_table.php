<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip this migration for SQLite in testing environment
        // SQLite doesn't handle dropping columns with foreign keys well
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Check if the repositories table exists
        if (! Schema::hasTable('repositories')) {
            return;
        }

        // Check if the user_id column exists
        if (! Schema::hasColumn('repositories', 'user_id')) {
            return;
        }

        // For MySQL, we need to handle foreign keys and indexes more carefully
        // Sure, MySQL can be a bit temperamental about these things!
        
        // First, drop the foreign key constraint
        Schema::table('repositories', function (Blueprint $table) {
            try {
                // Try to drop the foreign key first
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // If it doesn't exist or has a different name, that's fine
                // We'll try to find it by querying the database
                if (config('database.default') === 'mysql') {
                    try {
                        $foreignKeys = DB::select("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'repositories' 
                            AND COLUMN_NAME = 'user_id' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL
                        ");
                        
                        foreach ($foreignKeys as $foreignKey) {
                            DB::statement("ALTER TABLE repositories DROP FOREIGN KEY {$foreignKey->CONSTRAINT_NAME}");
                        }
                    } catch (\Exception $e2) {
                        // Well, we tried!
                    }
                }
            }
        });
        
        // Then, drop the index in a separate operation
        Schema::table('repositories', function (Blueprint $table) {
            try {
                $table->dropIndex('repositories_user_url_unique');
            } catch (\Exception $e) {
                // If the index doesn't exist or has a different name, try to find it
                if (config('database.default') === 'mysql') {
                    try {
                        $indexes = DB::select("
                            SELECT DISTINCT INDEX_NAME 
                            FROM information_schema.STATISTICS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'repositories' 
                            AND COLUMN_NAME = 'user_id'
                            AND INDEX_NAME != 'PRIMARY'
                        ");
                        
                        foreach ($indexes as $index) {
                            DB::statement("ALTER TABLE repositories DROP INDEX {$index->INDEX_NAME}");
                        }
                    } catch (\Exception $e2) {
                        // Ah well, no worries!
                    }
                }
            }
        });
        
        // Finally, drop the column
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });
    }
};
