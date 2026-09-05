<?php

namespace App\Services;

use App\Models\Inmate;

class AiDataQueryService
{
    /**
     * Max rows returned in a table response. The frontend renders these in
     * a scrollable container, but we still cap it so one huge facility
     * doesn't send megabytes of JSON for a single chat message.
     */
    protected const MAX_TABLE_ROWS = 500;

    /**
     * Handles a "query_data" action from the AI.
     *
     * $limit is an optional user-requested row count (e.g. "first 5
     * inmates"). It's only honored by keys that actually return a
     * row-by-row table; ignored otherwise. Always clamped to a sane
     * max regardless of what the model passes, since it's just echoing
     * back whatever number the user said and shouldn't be trusted blindly.
     *
     * Returns an array shaped like:
     *   ['text' => 'plain-language summary', 'table' => null | ['columns' => [...], 'rows' => [...]]]
     *
     * 'table' is null for stats that don't have a meaningful row-by-row
     * breakdown (e.g. a single incident count). No LLM is involved here —
     * every number/row comes straight from the DB, so it's always accurate.
     *
     * Returns null if the requested key isn't supported, so the controller
     * can fall back gracefully instead of showing a broken answer.
     */
    public function answer(string $key, ?int $limit = null, ?string $name = null): ?array
    {
        return match ($key) {
            'total_inmates' => $this->totalInmates($limit),
            'inmates_by_status' => $this->inmatesByStatus(),
            'inmates_by_cell' => $this->inmatesByCell(),
            'inmates_by_name' => $this->inmatesByName($name, $limit),
            'total_staff' => $this->totalStaff(),
            'staff_by_role' => $this->staffByRole(),
            'total_cells' => $this->totalCells(),
            'cell_occupancy' => $this->cellOccupancy(),
            'incidents_total' => $this->incidentsTotal(),
            'incidents_unresolved' => $this->incidentsUnresolved(),
            'incidents_recent' => $this->incidentsRecent(),
            default => null,
        };
    }

    /**
     * Max rows shown in the inmate list table specifically (per-request:
     * capped small since this table renders inline in a chat bubble).
     */
    protected const MAX_INMATE_ROWS = 15;

    /**
     * Attribute keys we never want to show in the table even though
     * they're real columns — internal/system fields, not "information
     * about the inmate" a staff member would want to see.
     */
    protected const HIDDEN_INMATE_COLUMNS = [
        'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at', 'cell_id',
    ];

    protected function inmatesByName(?string $name, ?int $requestedLimit = null): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['text' => "I didn't catch a name to search for — could you tell me who you're looking for?", 'table' => null];
        }

        // Adjust this if your table splits name into first_name/last_name
        // instead of a single `name` column.
        $matches = Inmate::with('cell')
            ->where('name', 'like', "%{$name}%")
            ->orderBy('id')
            ->get();

        $totalMatches = $matches->count();

        if ($totalMatches === 0) {
            return ['text' => "No inmates found matching \"{$name}\".", 'table' => null];
        }

        $limit = $requestedLimit !== null
            ? max(1, min($requestedLimit, self::MAX_INMATE_ROWS))
            : min($totalMatches, self::MAX_INMATE_ROWS);

        $shown = $matches->take($limit);

        $rawKeys = collect($shown->first()->getAttributes())
            ->keys()
            ->reject(fn ($key) => in_array($key, self::HIDDEN_INMATE_COLUMNS, true))
            ->values();

        $columnLabels = $rawKeys
            ->map(fn ($key) => str($key)->replace('_', ' ')->title()->toString())
            ->push('Cell')
            ->all();

        $rows = $shown->map(function ($inmate) use ($rawKeys) {
            $row = [];
            foreach ($rawKeys as $key) {
                $label = str($key)->replace('_', ' ')->title()->toString();
                $value = $inmate->{$key};
                $row[$label] = ($value === null || $value === '') ? '—' : $value;
            }
            $row['Cell'] = optional($inmate->cell)->name ?? ($inmate->cell_id ? "Cell #{$inmate->cell_id}" : '—');
            return $row;
        })->all();

        $note = $totalMatches > $limit ? " Showing the first {$limit}." : '';

        return [
            'text' => "Found {$totalMatches} inmate" . ($totalMatches === 1 ? '' : 's') . " matching \"{$name}\".{$note}",
            'table' => [
                'title' => "Inmates matching \"{$name}\"",
                'columns' => $columnLabels,
                'rows' => $rows,
            ],
        ];
    }

    protected function totalInmates(?int $requestedLimit = null): array
    {
        $totalCount = Inmate::count();

        // Honor what the user asked for ("first 5 inmates"), but never let
        // it exceed our safety cap or drop below 1 — the model is just
        // echoing back a number the user typed, so we don't trust it blindly.
        $limit = $requestedLimit !== null
            ? max(1, min($requestedLimit, self::MAX_INMATE_ROWS))
            : self::MAX_INMATE_ROWS;

        $inmates = Inmate::with('cell')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($inmates->isEmpty()) {
            return ['text' => "There are no inmates in the system.", 'table' => null];
        }

        // Build the column list dynamically from the model's own attributes
        // instead of a hardcoded guess, so every real field on your Inmate
        // table shows up automatically ("all of its information") without
        // needing to hand-maintain this list as your schema changes.
        $rawKeys = collect($inmates->first()->getAttributes())
            ->keys()
            ->reject(fn ($key) => in_array($key, self::HIDDEN_INMATE_COLUMNS, true))
            ->values();

        $columnLabels = $rawKeys
            ->map(fn ($key) => str($key)->replace('_', ' ')->title()->toString())
            ->push('Cell') // friendly, resolved cell name/number instead of a raw cell_id
            ->all();

        $rows = $inmates->map(function ($inmate) use ($rawKeys) {
            $row = [];
            foreach ($rawKeys as $key) {
                $label = str($key)->replace('_', ' ')->title()->toString();
                $value = $inmate->{$key};
                $row[$label] = ($value === null || $value === '') ? '—' : $value;
            }
            $row['Cell'] = optional($inmate->cell)->name ?? ($inmate->cell_id ? "Cell #{$inmate->cell_id}" : '—');
            return $row;
        })->all();

        $note = $totalCount > $limit
            ? " Showing the first " . $limit . "."
            : '';

        return [
            'text' => "There are currently {$totalCount} inmate" . ($totalCount === 1 ? '' : 's') . " in the system.{$note}",
            'table' => [
                'title' => 'Inmates',
                'columns' => $columnLabels,
                'rows' => $rows,
            ],
        ];
    }

    protected function inmatesByStatus(): array
    {
        $rows = Inmate::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        if ($rows->isEmpty()) {
            return ['text' => "There are no inmate records yet.", 'table' => null];
        }

        $parts = $rows->map(fn ($total, $status) => "{$total} {$status}")->values()->all();

        return [
            'text' => "Inmate breakdown by status: " . implode(', ', $parts) . ".",
            'table' => [
                'title' => 'Inmates by Status',
                'columns' => ['Status', 'Count'],
                'rows' => $rows->map(fn ($total, $status) => ['Status' => $status, 'Count' => $total])->values()->all(),
            ],
        ];
    }

    protected function inmatesByCell(): array
    {
        // Adjust the relationship/column names to match your actual Cell <-> Inmate schema.
        $grouped = Inmate::selectRaw('cell_id, count(*) as total')
            ->whereNotNull('cell_id')
            ->groupBy('cell_id')
            ->with('cell')
            ->get();

        if ($grouped->isEmpty()) {
            return ['text' => "No inmates are currently assigned to a cell.", 'table' => null];
        }

        $parts = $grouped->map(function ($row) {
            $cellName = optional($row->cell)->name ?? "Cell #{$row->cell_id}";
            return "{$cellName}: {$row->total}";
        })->all();

        return [
            'text' => "Inmate count by cell: " . implode(', ', $parts) . ".",
            'table' => [
                'title' => 'Inmates by Cell',
                'columns' => ['Cell', 'Inmates'],
                'rows' => $grouped->map(function ($row) {
                    return [
                        'Cell' => optional($row->cell)->name ?? "Cell #{$row->cell_id}",
                        'Inmates' => $row->total,
                    ];
                })->all(),
            ],
        ];
    }

    protected function totalStaff(): array
    {
        // Adjust to your actual Staff/User model + role scoping.
        $staff = \App\Models\User::where('role', '!=', 'admin')
            ->orderBy('id')
            ->limit(self::MAX_TABLE_ROWS)
            ->get();

        $count = \App\Models\User::where('role', '!=', 'admin')->count();

        $rows = $staff->map(fn ($user) => [
            'ID' => $user->id,
            'Name' => $user->name ?? '—',
            'Role' => $user->role ?? '—',
            'Email' => $user->email ?? '—',
        ])->all();

        return [
            'text' => "There are currently {$count} staff member" . ($count === 1 ? '' : 's') . " in the system.",
            'table' => $count > 0 ? [
                'title' => 'Staff',
                'columns' => ['ID', 'Name', 'Role', 'Email'],
                'rows' => $rows,
            ] : null,
        ];
    }

    protected function staffByRole(): array
    {
        $rows = \App\Models\User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        if ($rows->isEmpty()) {
            return ['text' => "There are no staff records yet.", 'table' => null];
        }

        $parts = $rows->map(fn ($total, $role) => "{$total} {$role}")->values()->all();

        return [
            'text' => "Staff breakdown by role: " . implode(', ', $parts) . ".",
            'table' => [
                'title' => 'Staff by Role',
                'columns' => ['Role', 'Count'],
                'rows' => $rows->map(fn ($total, $role) => ['Role' => $role, 'Count' => $total])->values()->all(),
            ],
        ];
    }

    protected function totalCells(): array
    {
        $count = \App\Models\Cell::count();
        return [
            'text' => "There are currently {$count} cell" . ($count === 1 ? '' : 's') . " set up in the system.",
            'table' => null,
        ];
    }

    protected function cellOccupancy(): array
    {
        $totalCells = \App\Models\Cell::count();
        $occupied = Inmate::whereNotNull('cell_id')->distinct('cell_id')->count('cell_id');

        return [
            'text' => "{$occupied} out of {$totalCells} cells currently have at least one inmate assigned.",
            'table' => null,
        ];
    }

    protected function incidentsTotal(): array
    {
        // Adjust to your actual Incident model name.
        $count = \App\Models\Incident::count();
        return [
            'text' => "There are {$count} incident record" . ($count === 1 ? '' : 's') . " total.",
            'table' => null,
        ];
    }

    protected function incidentsUnresolved(): array
    {
        $incidents = \App\Models\Incident::where('status', '!=', 'resolved')
            ->orderByDesc('created_at')
            ->limit(self::MAX_TABLE_ROWS)
            ->get();

        $count = $incidents->count();

        $rows = $incidents->map(fn ($incident) => [
            'ID' => $incident->id,
            'Description' => $incident->description ?? $incident->title ?? '—',
            'Status' => $incident->status ?? '—',
            'Reported' => optional($incident->created_at)->format('M d, Y g:i A') ?? '—',
        ])->all();

        return [
            'text' => "There " . ($count === 1 ? 'is' : 'are') . " {$count} unresolved incident" . ($count === 1 ? '' : 's') . ".",
            'table' => $count > 0 ? [
                'title' => 'Unresolved Incidents',
                'columns' => ['ID', 'Description', 'Status', 'Reported'],
                'rows' => $rows,
            ] : null,
        ];
    }

    protected function incidentsRecent(): array
    {
        $count = \App\Models\Incident::where('created_at', '>=', now()->subDays(7))->count();
        return [
            'text' => "{$count} incident" . ($count === 1 ? '' : 's') . " reported in the last 7 days.",
            'table' => null,
        ];
    }
}