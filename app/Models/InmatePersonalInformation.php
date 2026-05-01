<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InmatePersonalInformation extends Model
{
    protected $table = 'table_inmate_personal_information';

    protected $fillable = [
        'inmate_id',
        'dob',
        'age',
        'sex',
        'nationality',
        'religion',
        'civil_status',
        'phone',
        'email',
        'home_address',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'ec_name',
        'ec_relation',
        'ec_phone',
    ];

    public function inmate()
    {
        return $this->belongsTo(Inmate::class, 'inmate_id');
    }
}
