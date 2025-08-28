<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('agent_name')
                ->references('name')
                ->on('agents')
                ->onDelete('cascade');
        });
    }
};
