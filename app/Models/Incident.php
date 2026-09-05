<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref_number',
        'inmate_id',
        'type',
        'location',
        'severity',
        'occurred_at',
        'description',
        'status',
        'reported_by',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function inmate()
    {
        // Adjust to your actual Inmate model namespace if different.
        return $this->belongsTo(Inmate::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Generates the next ref number, e.g. INC-2026-004.
     * Called from the controller before creating a row.
     */
    public static function nextRefNumber(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('INC-%d-%03d', $year, $count);
    }
}
