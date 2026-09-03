<?php

namespace App\Services;

use App\Models\Inmate;

class AiDataQueryService
{
    /**
     * Handles a "query_data" action from the AI and returns a plain-text
     * answer built directly from real DB queries — no LLM involved here,
     * so the number is always accurate and this stays fast.
     *
     * Returns null if the requested key isn't supported, so the controller
     * can fall back gracefully instead of showing a broken answer.
     */
    public function answer(string $key): ?string
    {
        return match ($key) {
            'total_inmates' => $this->totalInmates(),
            'inmates_by_status' => $this->inmatesByStatus(),
            'inmates_by_cell' => $this->inmatesByCell(),
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

    protected function totalInmates(): string
    {
        $count = Inmate::count();
        return "There are currently {$count} inmate" . ($count === 1 ? '' : 's') . " in the system.";
    }

    protected function inmatesByStatus(): string
    {
        $rows = Inmate::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        if ($rows->isEmpty()) {
            return "There are no inmate records yet.";
        }

        $parts = $rows->map(fn ($total, $status) => "{$total} {$status}")->values()->all();
        return "Inmate breakdown by status: " . implode(', ', $parts) . ".";
    }

    protected function inmatesByCell(): string
    {
        // Adjust the relationship/column names to match your actual Cell <-> Inmate schema.
        $rows = Inmate::selectRaw('cell_id, count(*) as total')
            ->whereNotNull('cell_id')
            ->groupBy('cell_id')
            ->with('cell')
            ->get();

        if ($rows->isEmpty()) {
            return "No inmates are currently assigned to a cell.";
        }

        $parts = $rows->map(function ($row) {
            $cellName = optional($row->cell)->name ?? "Cell #{$row->cell_id}";
            return "{$cellName}: {$row->total}";
        })->all();

        return "Inmate count by cell: " . implode(', ', $parts) . ".";
    }

    protected function totalStaff(): string
    {
        // Adjust to your actual Staff/User model + role scoping.
        $count = \App\Models\User::where('role', '!=', 'admin')->count();
        return "There are currently {$count} staff member" . ($count === 1 ? '' : 's') . " in the system.";
    }

    protected function staffByRole(): string
    {
        $rows = \App\Models\User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        if ($rows->isEmpty()) {
            return "There are no staff records yet.";
        }

        $parts = $rows->map(fn ($total, $role) => "{$total} {$role}")->values()->all();
        return "Staff breakdown by role: " . implode(', ', $parts) . ".";
    }

    protected function totalCells(): string
    {
        $count = \App\Models\Cell::count();
        return "There are currently {$count} cell" . ($count === 1 ? '' : 's') . " set up in the system.";
    }

    protected function cellOccupancy(): string
    {
        $totalCells = \App\Models\Cell::count();
        $occupied = Inmate::whereNotNull('cell_id')->distinct('cell_id')->count('cell_id');

        return "{$occupied} out of {$totalCells} cells currently have at least one inmate assigned.";
    }

    protected function incidentsTotal(): string
    {
        // Adjust to your actual Incident model name.
        $count = \App\Models\Incident::count();
        return "There are {$count} incident record" . ($count === 1 ? '' : 's') . " total.";
    }

    protected function incidentsUnresolved(): string
    {
        $count = \App\Models\Incident::where('status', '!=', 'resolved')->count();
        return "There " . ($count === 1 ? 'is' : 'are') . " {$count} unresolved incident" . ($count === 1 ? '' : 's') . ".";
    }

    protected function incidentsRecent(): string
    {
        $count = \App\Models\Incident::where('created_at', '>=', now()->subDays(7))->count();
        return "{$count} incident" . ($count === 1 ? '' : 's') . " reported in the last 7 days.";
    }
}
