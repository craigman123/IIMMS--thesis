<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Covers Step 3 of the Add Inmate form — Criminal Offense Record.
     * One inmate can have many crimes (one-to-many).
     *
     * Fields sourced from the JS crime form panel:
     * — Offense details (name, date, location, law, description)
     * — Sentencing (years, months, verdict date)
     * — Case info (case number, prosecutor, judge)
     *
     * Victims are stored in a separate table: table_inmate_crime_victims
     */
    public function up(): void
    {
        Schema::create('table_inmate_crimes', function (Blueprint $table) {
            $table->id();

            // Foreign key — links back to the inmate record
            $table->foreignId('inmate_id')
                  ->constrained('table_inmate')
                  ->cascadeOnDelete();

            // ── Offense Details ───────────────────────────────
            $table->string('crime_name');                              // e.g. Robbery, Homicide
            $table->date('crime_date')->nullable();                    // Date of offense
            $table->string('crime_location')->nullable();              // City / Municipality
            $table->string('law_offended');                            // e.g. RPC Art. 249, RA 9165
            $table->text('crime_description')->nullable();             // Brief summary

            // ── Sentencing ────────────────────────────────────
            $table->unsignedSmallInteger('sentence_years')->nullable();
            $table->unsignedTinyInteger('sentence_months')->nullable();
            $table->date('verdict_date')->nullable();

            // ── Case Info ─────────────────────────────────────
            $table->string('case_number')->nullable();                 // e.g. Crim. Case No. 2024-001
            $table->string('prosecutor')->nullable();
            $table->string('judge')->nullable();                       // Presiding judge

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_inmate_crimes');
    }
};