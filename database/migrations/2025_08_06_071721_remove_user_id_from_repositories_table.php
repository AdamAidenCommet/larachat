<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        Schema::table('repositories', function (Blueprint $table) {
            // First drop any foreign keys that reference user_id
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
            
            // Then drop the unique index if it exists
            try {
                $table->dropIndex('repositories_user_url_unique');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            // Finally drop the column
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
