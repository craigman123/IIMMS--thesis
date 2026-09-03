<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id(); // bigint, matches the old int8 id
            $table->string('key')->unique(); // e.g. "active_model"
            $table->string('value')->nullable();
            $table->timestamps();
        });

        // Restore the row that was in Supabase before the table was
        // dropped, so the assistant comes back up already pointed at
        // the model you had active. AiSetting::get() would fall back
        // to config('services.ollama.model') anyway if this weren't
        // here, but this keeps behavior identical to before.
        \DB::table('ai_settings')->insert([
            'key' => 'active_model',
            'value' => 'llama3.1:8b',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
