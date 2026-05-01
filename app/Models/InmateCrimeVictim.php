<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmateCrimeVictim extends Model
{
    protected $table = 'table_inmate_crime_victims';

    protected $fillable = [
        'crime_id',
        'name',
        'age',
        'testifiers',
        'relation',
    ];

    public function crime()
    {
        return $this->belongsTo(InmateCrime::class, 'crime_id');
    }
}
