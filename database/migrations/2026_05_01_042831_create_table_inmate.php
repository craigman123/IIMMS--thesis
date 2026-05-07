<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Covers Step 1 of the Add Inmate form:
     * — Name, cell, status, detention type
     * — Admission / release dates
     * — Commitment order, issuing court
     */
    public function up(): void
    {
        Schema::create('table_inmate', function (Blueprint $table) {
            $table->id();

            // ── Name ──────────────────────────────────────────
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();

            // ── Facility details ──────────────────────────────
            $table->foreignId('cell_id')->nullable()->constrained('cells')->nullOnDelete(); // FK → cells.id
            $table->string('status')->nullable();                       // new | active | transferred | hold | pending
            $table->string('detention_type')->nullable();               // sentenced | detained | transferred

            // ── Dates ─────────────────────────────────────────
            $table->date('admission_date')->nullable();
            $table->date('release_date')->nullable();

            // ── Legal commitment ──────────────────────────────
            $table->string('commitment_order')->nullable();             // e.g. CO-2024-00123
            $table->string('court_branch')->nullable();                 // e.g. RTC Branch 14, Cebu City

            $table->string('security_lvl')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_inmate');
    }
};