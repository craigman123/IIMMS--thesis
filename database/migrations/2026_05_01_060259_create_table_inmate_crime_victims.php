<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores victims attached to a specific crime entry.
     * One crime can have many victims (one-to-many).
     *
     * Fields sourced from the JS victim row builder:
     * — vf-name, vf-age, vf-relation
     * Submitted as: crimes[n][victims][v][name|age|relation]
     */
    public function up(): void
    {
        Schema::create('table_inmate_crime_victims', function (Blueprint $table) {
            $table->id();

            // Foreign key — links to a specific crime record
            $table->foreignId('crime_id')
                  ->constrained('table_inmate_crimes')
                  ->cascadeOnDelete();

            $table->string('name')->nullable();                        // Victim's full name
            $table->unsignedTinyInteger('age')->nullable();           // Victim's age
            $table->string('relation')->nullable();                    // e.g. Stranger, Neighbor, Spouse

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_inmate_crime_victims');
    }
};