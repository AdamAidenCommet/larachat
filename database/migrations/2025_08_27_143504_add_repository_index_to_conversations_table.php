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
        Schema::table('conversations', function (Blueprint $table) {
            $table->index('repository', 'conversations_repository_index');
            $table->index(['user_id', 'repository'], 'conversations_user_repository_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_repository_index');
            $table->dropIndex('conversations_user_repository_index');
        });
    }
};
