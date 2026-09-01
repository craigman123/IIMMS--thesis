<?php

namespace App\Http\Controllers;

use App\Models\Cell;
use App\Models\Inmate;
use App\Models\InmateCrime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function adminDashboard()
    {   
        $nationalities = config('nationalities');
        $active_inmates = Inmate::where('status', 'active')->count();
        $inmates = Inmate::with(['totalCrimes', 'cell'])->get();

        $crimes = InmateCrime::all();
        $nextBlock = Cell::nextBlock();

        $total_crimes = DB::table('table_inmate_crimes')->count();
        $total_years = DB::table('table_inmate_crimes')->sum('sentence_years');

        $inmates_json = $inmates->map(function ($i) {
            $total_years = $i->totalCrimes->sum('sentence_years');

            $release = ($i->admission_date && $total_years > 0)
                ? Carbon::parse($i->admission_date)->addYears($total_years)->format('M d, Y')
                : null;

            $admitted = $i->admission_date
                ? Carbon::parse($i->admission_date)->format('M d, Y')
                : null;

            $row = [
                'id'   => $i->id,
                'name' => trim($i->last_name . ', ' . $i->first_name . ' ' . $i->middle_name) ?: null,
                'cell' => $i->cell?->cell_id ? strtoupper($i->cell->cell_id) : null,
            ];

            // Only include optional fields if they have meaningful values
            if ($i->status)      $row['status']   = $i->status;
            if ($admitted)       $row['admitted'] = $admitted;
            if ($release)        $row['release']  = $release;
            if ($i->security_lvl) $row['security'] = $i->security_lvl;

            return array_filter($row, fn($v) => !is_null($v) && $v !== '');
        });

        return view('admin.admin_dashboard', compact(
            'nationalities',
            'active_inmates',
            'crimes',
            'inmates',
            'inmates_json',
            'total_crimes',
            'total_years',
            'nextBlock',
        ));
    }

    public function pendingAccounts()
    {
        $pending = User::where('status', 'pending')->latest()->get();
        return view('admin.accounts', compact('pending'));
    }

    public function approveAccount(User $user)
    {
        $user->update(['status' => 'approved']);
        return back()->with('status', "{$user->name}'s account approved.");
    }

    public function rejectAccount(User $user)
    {
        $user->update(['status' => 'rejected']);
        return back()->with('status', "{$user->name}'s account rejected.");
    }
}