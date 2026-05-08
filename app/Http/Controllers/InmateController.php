<?php

namespace App\Http\Controllers;

use App\Models\Cell;
use App\Models\Inmate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InmateController extends Controller
{
    /**
     * Calculate total release date from admission date + summed sentence across all crimes.
     * Returns a date string (Y-m-d) or null if it cannot be determined.
     */
    private function computeReleaseDate(string $admissionDate, array $crimes): ?string
    {
        if (empty($crimes) || empty($admissionDate)) {
            return null;
        }

        $totalMonths = 0;
        foreach ($crimes as $crime) {
            $totalMonths += (int) ($crime['sentence_years']  ?? 0) * 12;
            $totalMonths += (int) ($crime['sentence_months'] ?? 0);
        }

        if ($totalMonths <= 0) {
            return null;
        }

        $date = new \DateTime($admissionDate);
        $date->modify("+{$totalMonths} months");
        return $date->format('Y-m-d');
    }

    public function store(Request $request)
    {
        $validated = $this->validateInmatePayload($request, false);

        $cellId = $request->input('cell_id') ?: Cell::where('type', 'Holding Cell')->value('id');

        // Always derive release date from admission date + total sentence
        $computed = $this->computeReleaseDate(
            $validated['admission_date'],
            $validated['crimes'] ?? []
        );
        if ($computed !== null) {
            $validated['release_date'] = $computed;
        }

        $mugshotPath = null;
        if ($request->hasFile('mugshot')) {
            $mugshotPath = $request->file('mugshot')->store('mugshots', 'public');
        }

        try {
            DB::transaction(function () use ($request, $validated, $mugshotPath, $cellId) {
                $inmate = Inmate::create(array_merge(
                    $this->extractInmateFields($validated),
                    [
                        'mugshot_path' => $mugshotPath,
                        'cell_id'      => $cellId,
                    ]
                ));

                $inmate->personalInformation()->create($this->extractPersonalFields($validated));
                $this->syncCrimes($inmate, $validated['crimes'] ?? []);
            });
        } catch (\Throwable $e) {
            if ($mugshotPath) {
                Storage::disk('public')->delete($mugshotPath);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Inmate record saved successfully.',
        ], 201);
    }

    public function show(Inmate $inmate)
    {
        $inmate->load(['cell', 'personalInformation', 'crimes.victims']);

        return response()->json([
            'success' => true,
            'inmate'  => $this->serializeInmate($inmate),
        ]);
    }

    public function update(Request $request, Inmate $inmate)
    {
        $validated = $this->validateInmatePayload($request, true);

        $cellId = $request->has('cell_id')
            ? ($request->input('cell_id') ?: null)
            : $inmate->cell_id;

        // Always recompute release date from the updated crimes
        $computed = $this->computeReleaseDate(
            $validated['admission_date'],
            $validated['crimes'] ?? []
        );
        if ($computed !== null) {
            $validated['release_date'] = $computed;
        }

        // ── Mugshot handling ─────────────────────────────────────────────
        $mugshotPath = $inmate->mugshot_path; // keep existing by default

        if ($request->hasFile('mugshot')) {
            // Delete old photo and store the new one
            if ($mugshotPath) {
                Storage::disk('public')->delete($mugshotPath);
            }
            $mugshotPath = $request->file('mugshot')->store('mugshots', 'public');
        } elseif ($request->input('remove_mugshot')) {
            // User explicitly removed the photo
            if ($mugshotPath) {
                Storage::disk('public')->delete($mugshotPath);
            }
            $mugshotPath = null;
        }

        DB::transaction(function () use ($inmate, $validated, $cellId, $mugshotPath) {
            $inmate->update(array_merge(
                $this->extractInmateFields($validated),
                [
                    'cell_id'      => $cellId,
                    'mugshot_path' => $mugshotPath,
                ]
            ));

            $inmate->personalInformation()->updateOrCreate(
                ['inmate_id' => $inmate->id],
                $this->extractPersonalFields($validated)
            );

            $this->syncCrimes($inmate, $validated['crimes'] ?? []);
        });

        $inmate->load(['cell', 'personalInformation', 'crimes.victims']);

        return response()->json([
            'success' => true,
            'message' => 'Inmate record updated successfully.',
            'inmate'  => $this->serializeInmate($inmate),
            'summary' => $this->serializeSummary($inmate),
        ]);
    }

    private function validateInmatePayload(Request $request, bool $isUpdate): array
    {
        $crimeRule = $isUpdate ? 'nullable|array' : 'required|array|min:1';

        return $request->validate([
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
            'mugshot'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

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

            'crimes'                        => $crimeRule,
            'crimes.*.crime_name'           => 'required_with:crimes|string|max:255',
            'crimes.*.law_offended'         => 'required_with:crimes|string|max:255',
            'crimes.*.crime_date'           => 'required_with:crimes|date',
            'crimes.*.sentence_years'       => 'required_with:crimes|integer|min:0',
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
    }

    private function extractInmateFields(array $validated): array
    {
        return collect($validated)->only([
            'last_name',
            'first_name',
            'middle_name',
            'status',
            'detention_type',
            'admission_date',
            'release_date',
            'commitment_order',
            'court_branch',
            'security_lvl',
        ])->toArray();
    }

    private function extractPersonalFields(array $validated): array
    {
        return collect($validated)->only([
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
        ])->toArray();
    }

    private function syncCrimes(Inmate $inmate, array $crimes): void
    {
        $inmate->crimes()->each(function ($crime) {
            $crime->victims()->delete();
            $crime->delete();
        });

        foreach ($crimes as $crimeData) {
            $crime = $inmate->crimes()->create([
                'crime_name'        => $crimeData['crime_name'],
                'crime_date'        => $crimeData['crime_date'],
                'crime_location'    => $crimeData['crime_location'] ?? null,
                'law_offended'      => $crimeData['law_offended'],
                'crime_description' => $crimeData['crime_description'] ?? null,
                'sentence_years'    => $crimeData['sentence_years'],
                'sentence_months'   => $crimeData['sentence_months'] ?? null,
                'verdict_date'      => $crimeData['verdict_date'] ?? null,
                'case_number'       => $crimeData['case_number'] ?? null,
                'prosecutor'        => $crimeData['prosecutor'] ?? null,
                'judge'             => $crimeData['judge'] ?? null,
            ]);

            foreach (($crimeData['victims'] ?? []) as $victimData) {
                if (empty($victimData['name'])) {
                    continue;
                }

                $crime->victims()->create([
                    'name'       => $victimData['name'] ?? null,
                    'age'        => $victimData['age'] ?? null,
                    'testifiers' => $victimData['testifiers'] ?? null,
                    'relation'   => $victimData['relation'] ?? null,
                ]);
            }
        }
    }

    private function serializeInmate(Inmate $inmate): array
    {
        $personal = $inmate->personalInformation;

        return [
            'id'               => $inmate->id,
            'last_name'        => $inmate->last_name,
            'first_name'       => $inmate->first_name,
            'middle_name'      => $inmate->middle_name,
            'status'           => $inmate->status,
            'detention_type'   => $inmate->detention_type,
            'admission_date'   => $inmate->admission_date ? date('Y-m-d', strtotime($inmate->admission_date)) : null,
            'release_date'     => $inmate->release_date ? date('Y-m-d', strtotime($inmate->release_date)) : null,
            'commitment_order' => $inmate->commitment_order,
            'court_branch'     => $inmate->court_branch,
            'security_lvl'     => $inmate->security_lvl,
            'mugshot_url'      => $inmate->mugshot_path ? Storage::url($inmate->mugshot_path) : null,
            'cell_id'          => $inmate->cell_id,
            'cell_label'       => $inmate->cell?->cell_id,
            'personal'         => [
                'dob'               => $personal?->dob,
                'age'               => $personal?->age,
                'sex'               => $personal?->sex,
                'nationality'       => $personal?->nationality,
                'religion'          => $personal?->religion,
                'civil_status'      => $personal?->civil_status,
                'phone'             => $personal?->phone,
                'email'             => $personal?->email,
                'home_address'      => $personal?->home_address,
                'sss_number'        => $personal?->sss_number,
                'philhealth_number' => $personal?->philhealth_number,
                'pagibig_number'    => $personal?->pagibig_number,
                'ec_name'           => $personal?->ec_name,
                'ec_relation'       => $personal?->ec_relation,
                'ec_phone'          => $personal?->ec_phone,
            ],
            'crimes' => $inmate->crimes->map(function ($crime) {
                return [
                    'id'                => $crime->id,
                    'crime_name'        => $crime->crime_name,
                    'crime_date'        => $crime->crime_date,
                    'crime_location'    => $crime->crime_location,
                    'law_offended'      => $crime->law_offended,
                    'crime_description' => $crime->crime_description,
                    'sentence_years'    => $crime->sentence_years,
                    'sentence_months'   => $crime->sentence_months,
                    'verdict_date'      => $crime->verdict_date,
                    'case_number'       => $crime->case_number,
                    'prosecutor'        => $crime->prosecutor,
                    'judge'             => $crime->judge,
                    'victims' => $crime->victims->map(function ($victim) {
                        return [
                            'id'         => $victim->id,
                            'name'       => $victim->name,
                            'age'        => $victim->age,
                            'testifiers' => $victim->testifiers,
                            'relation'   => $victim->relation,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    private function serializeSummary(Inmate $inmate): array
    {
        return [
            'id'        => $inmate->id,
            'name'      => trim($inmate->last_name . ', ' . $inmate->first_name . ' ' . $inmate->middle_name),
            'cell'      => $inmate->cell?->cell_id ? strtoupper($inmate->cell->cell_id) : null,
            'status'    => $inmate->status,
            'security'  => $inmate->security_lvl,
            'detention' => $inmate->detention_type,
            'admitted'  => $inmate->admission_date ? date('M d, Y', strtotime($inmate->admission_date)) : null,
            'release'   => $inmate->release_date ? date('M d, Y', strtotime($inmate->release_date)) : null,
            'mugshot'   => $inmate->mugshot_path ? Storage::url($inmate->mugshot_path) : null,
        ];
    }
}