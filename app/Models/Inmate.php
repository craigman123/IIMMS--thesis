<?php

namespace App\Models;

use App\Models\InmateCrime;
use App\Models\InmatePersonalInformation;
use Illuminate\Database\Eloquent\Model;

class Inmate extends Model
{
    protected $table = 'table_inmate';

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'cell',
        'status',
        'detention_type',
        'admission_date',
        'release_date',
        'commitment_order',
        'court_branch',
    ];

    protected $casts = [
        'admitted_at'  => 'datetime',
        'release_date' => 'datetime',
    ];

    public function personalInformation()
    {
        return $this->hasOne(InmatePersonalInformation::class, 'inmate_id');
    }

    public function crimes()
    {
        return $this->hasMany(InmateCrime::class, 'inmate_id');
    }

    public function totalCrimes()
    {
        return $this->hasMany(InmateCrime::class);
    }

    
}