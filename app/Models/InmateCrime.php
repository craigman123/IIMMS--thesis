<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmateCrime extends Model
{
    protected $table = 'table_inmate_crimes';

    protected $fillable = [
        'inmate_id',
        'crime_name',
        'crime_date',
        'crime_location',
        'law_offended',
        'crime_description',
        'sentence_years',
        'sentence_months',
        'verdict_date',
        'case_number',
        'prosecutor',
        'judge',
    ];

    public function inmate()
    {
        return $this->belongsTo(Inmate::class, 'inmate_id');
    }

    public function victims()
    {
        return $this->hasMany(InmateCrimeVictim::class, 'crime_id');
    }
}
