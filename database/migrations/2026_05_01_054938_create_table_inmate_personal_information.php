<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Covers Step 2 of the Add Inmate form — Personal Profile:
     * — Demographics (DOB, age, sex, nationality, religion, civil status)
     * — Contact & address (phone, email, home address)
     * — Government IDs (SSS, PhilHealth, Pag-IBIG)
     * — Emergency contact (name, relationship, phone)
     */
    public function up(): void
    {
        Schema::create('table_inmate_personal_information', function (Blueprint $table) {
            $table->id();

            // Foreign key — links back to the inmate record
            $table->foreignId('inmate_id')
                  ->constrained('table_inmate')
                  ->cascadeOnDelete();

            // ── Demographics ──────────────────────────────────
            $table->date('dob')->nullable();                            // Date of Birth
            $table->unsignedTinyInteger('age')->nullable();            // Auto-calculated on the front end
            $table->string('sex', 10)->nullable();                     // male | female
            $table->string('nationality')->nullable();                  // e.g. Filipino
            $table->string('religion')->nullable();                     // e.g. Roman Catholic
            $table->string('civil_status', 20)->nullable();            // single | married | widowed | separated

            // ── Contact & Address ─────────────────────────────
            $table->string('phone', 30)->nullable();                   // e.g. 09XX-XXX-XXXX
            $table->string('email')->nullable();
            $table->text('home_address')->nullable();                  // Street, Brgy, City, Province

            // ── Government IDs ────────────────────────────────
            $table->string('sss_number', 30)->nullable();
            $table->string('philhealth_number', 30)->nullable();
            $table->string('pagibig_number', 30)->nullable();

            // ── Emergency Contact ─────────────────────────────
            $table->string('ec_name')->nullable();                     // Full name
            $table->string('ec_relation')->nullable();                 // e.g. Spouse, Parent
            $table->string('ec_phone', 30)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_inmate_personal_information');
    }
};