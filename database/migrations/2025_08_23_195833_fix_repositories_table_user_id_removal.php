<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if user_id column exists before trying to drop it
        if (Schema::hasColumn('repositories', 'user_id')) {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite doesn't support dropping columns easily, so we need to recreate the table
                // Create new table without user_id
                Schema::create('repositories_new', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('slug')->unique();
                    $table->string('url');
                    $table->string('local_path');
                    $table->string('branch')->default('main');
                    $table->text('deploy_script')->nullable();
                    $table->timestamp('last_pulled_at')->nullable();
                    $table->timestamps();
                });

                // Copy data from old table to new table (excluding user_id)
                $columns = ['id', 'name', 'slug', 'url', 'local_path', 'branch', 'last_pulled_at', 'created_at', 'updated_at'];
                
                // Check if deploy_script exists and add it to columns list
                if (Schema::hasColumn('repositories', 'deploy_script')) {
                    $columns[] = 'deploy_script';
                }
                
                $columnsList = implode(', ', $columns);
                DB::statement("INSERT INTO repositories_new ($columnsList) 
                              SELECT $columnsList FROM repositories");

                // Drop old table
                Schema::dropIfExists('repositories');

                // Rename new table to repositories
                Schema::rename('repositories_new', 'repositories');
            } else {
                // For MySQL and other databases
                Schema::table('repositories', function (Blueprint $table) {
                    // Check and drop foreign key if it exists
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_NAME = 'repositories' 
                        AND COLUMN_NAME = 'user_id' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                        AND TABLE_SCHEMA = DATABASE()
                    ");
                    
                    foreach ($foreignKeys as $key) {
                        $table->dropForeign($key->CONSTRAINT_NAME);
                    }
                    
                    // Check and drop index if it exists
                    $indexes = DB::select("
                        SELECT DISTINCT INDEX_NAME 
                        FROM information_schema.STATISTICS 
                        WHERE TABLE_NAME = 'repositories' 
                        AND COLUMN_NAME = 'user_id'
                        AND TABLE_SCHEMA = DATABASE()
                    ");
                    
                    foreach ($indexes as $index) {
                        if ($index->INDEX_NAME !== 'PRIMARY') {
                            $table->dropIndex($index->INDEX_NAME);
                        }
                    }
                    
                    // Finally drop the column
                    $table->dropColumn('user_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't reverse this migration as it's a fix
        // The original migration should handle the down() logic
    }
};
