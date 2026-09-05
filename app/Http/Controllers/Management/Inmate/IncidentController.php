<?php

namespace App\Http\Controllers\Management\Inmate;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Inmate; // adjust namespace if your Inmate model lives elsewhere
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncidentController extends Controller
{
    /**
     * GET /admin/incidents
     * Returns all incidents for the table (JSON), newest first.
     */
    public function index()
    {
        $incidents = Incident::with('inmate')
            ->latest()
            ->get()
            ->map(fn (Incident $incident) => $this->formatIncident($incident));

        return response()->json($incidents);
    }

    /**
     * GET /admin/incidents/inmates
     * Powers the inmate picker modal. Supports the same filters the
     * frontend already sends: search, block, status.
     *
     * NOTE: adjust the column names below (first_name/last_name,
     * inmate_no, block, status) to match your actual inmates schema.
     */
    public function inmates(Request $request)
    {
        $query = Inmate::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
                  ->orWhere('inmate_no', 'like', "%{$search}%");
            });
        }

        if ($block = $request->query('block')) {
            $query->where('block', $block);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $inmates = $query->orderBy('last_name')->get()->map(fn (Inmate $inmate) => [
            'id' => $inmate->inmate_no,
            'db_id' => $inmate->id,
            'name' => trim($inmate->first_name . ' ' . $inmate->last_name),
            'block' => $inmate->block,
            'status' => $inmate->status,
        ]);

        return response()->json($inmates);
    }

    /**
     * POST /admin/incidents
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inmate_id' => ['required', 'exists:inmates,id'],
            'type' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:150'],
            'severity' => ['required', 'in:Low,Medium,High,Critical'],
            'occurred_at' => ['required', 'date'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $incident = Incident::create([
            'ref_number' => Incident::nextRefNumber(),
            'inmate_id' => $validated['inmate_id'],
            'type' => $validated['type'],
            'location' => $validated['location'],
            'severity' => $validated['severity'],
            'occurred_at' => $validated['occurred_at'],
            'description' => $validated['description'],
            'status' => 'Open',
            'reported_by' => $request->user()?->id,
        ]);

        return response()->json(
            $this->formatIncident($incident->load('inmate')),
            201
        );
    }

    private function formatIncident(Incident $incident): array
    {
        return [
            'id' => $incident->id,
            'ref' => $incident->ref_number,
            'type' => $incident->type,
            'inmate' => $incident->inmate?->first_name . ' ' . $incident->inmate?->last_name,
            'location' => $incident->location,
            'severity' => $incident->severity,
            'date' => $incident->occurred_at->format('M j, Y g:i A'),
            'status' => $incident->status,
        ];
    }
}
