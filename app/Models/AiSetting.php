<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    // Matches the existing Supabase table (id, key, value, created_at, updated_at) —
    // no migration needed, the table and its data are still intact.
    protected $table = 'ai_settings';

    protected $fillable = ['key', 'value'];

    /**
     * Read a setting by key. Falls back to $default if the row doesn't
     * exist yet (e.g. on a fresh install before anything has been set).
     */
    public static function get(string $key, $default = null)
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * Create or update a setting by key.
     */
    public static function set(string $key, $value): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
