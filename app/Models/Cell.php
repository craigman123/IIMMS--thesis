<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cell extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cell_id',
        'block',
        'block_number',
        'type',
        'capacity',
        'occupancy',
        'status',
    ];

    protected $casts = [
        'capacity'     => 'integer',
        'occupancy'    => 'integer',
        'block_number' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function inmates()
    {
        return $this->hasMany(\App\Models\Inmate::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Returns the next available block letter (A, B, C, …)
     * based on the highest block already stored.
     */
    public static function nextBlock(): string
    {
        $last = static::withTrashed()
            ->where('block_number', '!=', 0)
            ->orderByRaw("LENGTH(block) DESC, block DESC")
            ->value('block');

        if (! $last) return 'A';

        // Increment: A→B, Z→AA, etc.
        return self::incrementLetter($last);
    }

    private static function incrementLetter(string $letter): string
    {
        $len = strlen($letter);
        $chars = str_split($letter);

        for ($i = $len - 1; $i >= 0; $i--) {
            if ($chars[$i] < 'Z') {
                $chars[$i] = chr(ord($chars[$i]) + 1);
                return implode('', $chars);
            }
            $chars[$i] = 'A';
        }

        return 'A' . implode('', $chars);
    }

    public function isFull(): bool
    {
        return $this->occupancy >= $this->capacity;
    }

    public function availableSlots(): int
    {
        return max(0, $this->capacity - $this->occupancy);
    }

    public function occupancyLabel(): string
    {
        return "{$this->occupancy}/{$this->capacity}";
    }

    /**
     * Sync status automatically based on occupancy.
     * Call after any occupancy change.
     */
    public function syncStatus(): void
    {
        if ($this->status === 'maintenance' || $this->status === 'condemned') {
            return; // don't auto-override manual statuses
        }
        $this->status = $this->isFull() ? 'full' : 'available';
        $this->save();
    }
}