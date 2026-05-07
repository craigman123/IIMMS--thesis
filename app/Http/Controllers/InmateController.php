<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inmate;
use App\Models\Cell;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InmateController extends Controller
{
    /**
     * Store a new inmate along with personal info, crimes, and victims.
     * Called via POST /admin/inmates (multipart/form-data from add-inmate.js)
     *
     * Mugshot storage:
     *   • Uploaded file is stored under  storage/app/public/mugshots/
     *   • The relative path (e.g. "mugshots/AbCdEf.jpg") is saved in table_inmate.mugshot_path
     *   • Public URL: Storage::url($inmate->mugshot_path)
     *   • Make sure you have run:  php artisan storage:link
     *
     * Cell assignment:
     *   • Optional from the form — if the user leaves it blank, the inmate is
     *     automatically placed in the "Holding Cell" (looked up by type).
     *   • If no Holding Cell record exists in the DB, cell_id stays null.
     */
    public function store(Request $request)
    {
        // ── Validate ──────────────────────────────────────────────────
        $request->validate([
            // Step 1 — Inmate Info
            'last_name'        => 'required|string|max:255',
            'first_name'       => 'required|string|max:255',
            'middle_name'      => 'nullable|string|max:255',
            'cell_id'          => 'nullable|integer|exists:cells,id',
            'status'           => 'required|string|max:50',
            'detention_type'   => 'required|string|max:50',
            'admission_date'   => 'required|date',
            'commitment_order' => 'required|string|max:100',
            'court_branch'     => 'nullable|string|max:255',
            'release_date'     => 'nullable|date',
            'security_lvl'     => 'required|string|max:50',

            // Mugshot — optional, images only, max 5 MB
            'mugshot'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

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

        // ── Resolve cell_id — fall back to Holding Cell if none chosen ─
        // The form treats cell assignment as optional. When the user submits
        // no cell, we automatically assign the inmate to the Holding Cell
        // record in the DB. If that record doesn't exist yet, cell_id stays null.
        $cellId = $request->input('cell_id') ?: Cell::where('type', 'Holding Cell')->value('id');

        // ── Handle mugshot upload ─────────────────────────────────────
        // Store before the transaction so we can roll back manually on DB failure.
        $mugshotPath = null;
        if ($request->hasFile('mugshot')) {
            // putFile() generates a unique filename automatically.
            // Stored at:  storage/app/public/mugshots/<hash>.<ext>
            // Relative path returned: "mugshots/<hash>.<ext>"
            $mugshotPath = $request->file('mugshot')->store('mugshots', 'public');
        }

        // ── Wrap everything in a transaction ─────────────────────────
        try {
            DB::transaction(function () use ($request, $mugshotPath, $cellId) {

                // 1. Create inmate record (includes mugshot_path and resolved cell_id)
                $inmate = Inmate::create(array_merge(
                    $request->only([
                        'last_name', 'first_name', 'middle_name',
                        'status', 'detention_type',
                        'admission_date', 'release_date',
                        'commitment_order', 'court_branch',
                        'security_lvl',
                    ]),
                    [
                        'mugshot_path' => $mugshotPath,
                        'cell_id'      => $cellId,   // user-chosen cell, or Holding Cell PK, or null
                    ]
                ));

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
        } catch (\Throwable $e) {
            // If DB failed, delete the uploaded mugshot so we don't leave orphaned files
            if ($mugshotPath) {
                Storage::disk('public')->delete($mugshotPath);
            }
            throw $e; // Re-throw so Laravel returns a 500 / validation response
        }

        return response()->json([
            'success' => true,
            'message' => 'Inmate record saved successfully.',
        ], 201);
    }
}