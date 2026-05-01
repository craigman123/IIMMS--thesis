<?php

namespace App\Http\Controllers;

use App\Models\Inmate;
use App\Models\InmateCrime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use nationalities;

class AdminController extends Controller
{
    public function adminDashboard()
    {
        $nationalities = config('nationalities');
        $inmates = Inmate::with('totalCrimes')->get();
        $crimes = InmateCrime::all();

        $total_crimes = DB::table('table_inmate_crimes')->count();
        $total_years = DB::table('table_inmate_crimes')->sum('sentence_years');

        $inmates_json = $inmates->map(function($i) {
            return [
                'id'       => $i->id,
                'name'     => $i->name,
                'cell'     => $i->cell ?? '—',
                'status'   => $i->status ?? 'unknown',
                'admitted' => optional($i->admitted_at)->format('M d, Y') ?? '—',
                'release'  => optional($i->release_date)->format('M d, Y') ?? '—',
            ];
        });

        return view('admin.admin_dashboard', compact(
            'nationalities',
            'crimes',
            'inmates',
            'inmates_json',
            'total_crimes',
            'total_years'
        ));
    }
}
