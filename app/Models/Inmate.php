<?php

namespace App\Models;

use App\Models\Cell;
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
        'cell_id',
        'status',
        'detention_type',
        'admission_date',
        'release_date',
        'commitment_order',
        'court_branch',
        'security_lvl',
        'mugshot_path',
    ];

    protected $casts = [
        'admitted_at'  => 'datetime',
        'release_date' => 'datetime',
    ];

    public function cell()
    {
        return $this->belongsTo(Cell::class, 'cell_id');
    }

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