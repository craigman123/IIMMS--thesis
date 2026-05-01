<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inmate;
use Illuminate\Support\Facades\DB;

class InmateController extends Controller
{
    /**
     * Store a new inmate along with personal info, crimes, and victims.
     * Called via POST /admin/inmates (fetch from add-inmate.js)
     */
    public function store(Request $request)
    {
        // ── Validate ──────────────────────────────────────────────────
        $request->validate([
            // Step 1 — Inmate Info
            'last_name'        => 'required|string|max:255',
            'first_name'       => 'required|string|max:255',
            'middle_name'      => 'nullable|string|max:255',
            'cell'             => 'required|string|max:50',
            'status'           => 'required|string|max:50',
            'detention_type'   => 'required|string|max:50',
            'admission_date'   => 'required|date',
            'commitment_order' => 'required|string|max:100',
            'court_branch'     => 'nullable|string|max:255',
            'release_date'     => 'nullable|date',

            // Step 2 — Personal Info
            'dob'               => 'required|date',
            'sex'               => 'required|string|max:10',
            'home_address'      => 'required|string|max:500',
            'age'               => 'nullable|integer|min:1|max:120',
            'nationality'       => 'nullable|string|max:100',
            'religion'          => 'nullable|string|max:100',
            'civil_status'      => 'nullable|string|max:20',
            'phone'             => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:255',
            'sss_number'        => 'nullable|string|max:30',
            'philhealth_number' => 'nullable|string|max:30',
            'pagibig_number'    => 'nullable|string|max:30',
            'ec_name'           => 'nullable|string|max:255',
            'ec_relation'       => 'nullable|string|max:100',
            'ec_phone'          => 'nullable|string|max:30',

            // Step 3 — Crimes (at least 1 required)
            'crimes'                        => 'required|array|min:1',
            'crimes.*.crime_name'           => 'required|string|max:255',
            'crimes.*.law_offended'         => 'required|string|max:255',
            'crimes.*.crime_date'           => 'required|date',
            'crimes.*.sentence_years'       => 'required|integer|min:0',
            'crimes.*.crime_location'       => 'nullable|string|max:255',
            'crimes.*.crime_description'    => 'nullable|string',
            'crimes.*.sentence_months'      => 'nullable|integer|min:0|max:12',
            'crimes.*.verdict_date'         => 'nullable|date',
            'crimes.*.case_number'          => 'nullable|string|max:100',
            'crimes.*.prosecutor'           => 'nullable|string|max:255',
            'crimes.*.judge'                => 'nullable|string|max:255',
            'crimes.*.victims'              => 'nullable|array',
            'crimes.*.victims.*.name'       => 'nullable|string|max:255',
            'crimes.*.victims.*.testifiers' => 'nullable|string|max:255',
            'crimes.*.victims.*.age'        => 'nullable|integer|min:0',
            'crimes.*.victims.*.relation'   => 'nullable|string|max:100',
        ]);

        // ── Wrap everything in a transaction ─────────────────────────
        DB::transaction(function () use ($request) {

            // 1. Create inmate record
            $inmate = Inmate::create($request->only([
                'last_name', 'first_name', 'middle_name',
                'cell', 'status', 'detention_type',
                'admission_date', 'release_date',
                'commitment_order', 'court_branch',
            ]));

            // 2. Create personal information (via relationship)
            $inmate->personalInformation()->create($request->only([
                'dob', 'age', 'sex', 'nationality', 'religion',
                'civil_status', 'phone', 'email', 'home_address',
                'sss_number', 'philhealth_number', 'pagibig_number',
                'ec_name', 'ec_relation', 'ec_phone',
            ]));

            // 3. Create each crime + its victims (via relationships)
            foreach ($request->crimes as $crimeData) {
                $crime = $inmate->crimes()->create([
                    'crime_name'        => $crimeData['crime_name'],
                    'crime_date'        => $crimeData['crime_date'],
                    'crime_location'    => $crimeData['crime_location']    ?? null,
                    'law_offended'      => $crimeData['law_offended'],
                    'crime_description' => $crimeData['crime_description'] ?? null,
                    'sentence_years'    => $crimeData['sentence_years'],
                    'sentence_months'   => $crimeData['sentence_months']   ?? null,
                    'verdict_date'      => $crimeData['verdict_date']      ?? null,
                    'case_number'       => $crimeData['case_number']       ?? null,
                    'prosecutor'        => $crimeData['prosecutor']        ?? null,
                    'judge'             => $crimeData['judge']             ?? null,
                ]);

                // 4. Create victims for this crime (skip empty rows)
                if (!empty($crimeData['victims'])) {
                    foreach ($crimeData['victims'] as $victimData) {
                        if (empty($victimData['name'])) continue;

                        $crime->victims()->create([
                            'name'       => $victimData['name']       ?? null,
                            'age'        => $victimData['age']        ?? null,
                            'testifiers' => $victimData['testifiers'] ?? null,
                            'relation'   => $victimData['relation']   ?? null,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Inmate record saved successfully.',
        ], 201);
    }
}