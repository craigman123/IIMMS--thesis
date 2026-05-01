<?php

namespace App\Http\Controllers;

use App\Models\Inmate;
use App\Models\InmateCrime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use nationalities;

class AdminController extends Controller
{
    public function adminDashboard()
    {
        $nationalities = config('nationalities');
        $active_inmates = Inmate::where('status', 'active')->count();
        $inmates = Inmate::with('totalCrimes')->get();
        $crimes = InmateCrime::all();

        $total_crimes = DB::table('table_inmate_crimes')->count();
        $total_years = DB::table('table_inmate_crimes')->sum('sentence_years');

        $inmates_json = $inmates->map(function($i) {
        $total_years = $i->totalCrimes->sum('sentence_years');

        if ($i->admission_date && $total_years > 0) {
            $release = Carbon::parse($i->admission_date)->addYears($total_years)->format('M d, Y');
        } else {
            $release = '—';
        }

            return [
                'id'       => $i->id,
                'name'     => trim($i->last_name . ', ' . $i->first_name . ' ' . $i->middle_name),
                'cell'     => strtoupper($i->cell) ?? '—',
                'status'   => $i->status ?? 'unknown',
                'admitted' => $i->admission_date ? Carbon::parse($i->admission_date)->format('M d, Y') : '—',
                'release'  => $release,
            ];
        });

        return view('admin.admin_dashboard', compact(
            'nationalities',
            'active_inmates',
            'crimes',
            'inmates',
            'inmates_json',
            'total_crimes',
            'total_years'
        ));
    }
}
