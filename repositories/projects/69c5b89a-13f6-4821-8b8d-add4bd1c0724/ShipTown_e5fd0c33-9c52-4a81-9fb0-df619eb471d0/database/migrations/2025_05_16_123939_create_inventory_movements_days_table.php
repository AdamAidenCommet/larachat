<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->boolean('recalc_required')->default(true)->index();
            $table->unsignedBigInteger('max_inventory_id_checked')->default(0);
            $table->timestamps();
        });
    }
};
