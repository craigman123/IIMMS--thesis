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
        Schema::table('table_inmate', function (Blueprint $table) {
            $table->string('mugshot_path')->nullable()->after('security_lvl');
        });
    }

    public function down(): void
    {
        Schema::table('table_inmate', function (Blueprint $table) {
            $table->dropColumn('mugshot_path');
        });
    }
};
