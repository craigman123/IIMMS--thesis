<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('ref_number')->unique();

            // Adjust this FK to match your existing inmates table/model.
            $table->foreignId('inmate_id')->constrained('inmates')->cascadeOnDelete();

            $table->string('type');
            $table->string('location');
            $table->enum('severity', ['Low', 'Medium', 'High', 'Critical']);
            $table->dateTime('occurred_at');
            $table->text('description');
            $table->enum('status', ['Open', 'Under review', 'Resolved'])->default('Open');

            // Who filed it — adjust to match your users table if the column name differs.
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
