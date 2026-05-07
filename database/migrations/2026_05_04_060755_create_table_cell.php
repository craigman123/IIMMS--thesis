<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cells', function (Blueprint $table) {
            $table->id();
            $table->string('cell_id', 10)->unique();        // e.g. A-1, B-3
            $table->char('block', 2);                       // e.g. A, B, C
            $table->unsignedTinyInteger('block_number');    // e.g. 1, 2, 3 within block
            $table->string('type', 30);                     // Luxury | Standard | Dormitory | Solitary
            $table->unsignedTinyInteger('capacity');        // max occupants
            $table->unsignedTinyInteger('occupancy')->default(0); // current occupants
            $table->enum('status', ['available', 'full', 'maintenance', 'condemned'])->default('available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cells');
    }
};