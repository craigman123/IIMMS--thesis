<?php

namespace App\Http\Controllers;

use App\Models\Cell;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CellController extends Controller
{
    // LIST  GET /admin/cells
    public function index(Request $request)
    {
        $cells = Cell::orderBy('block')->orderBy('block_number')->get();
        $stats = $this->buildStats();
        return view('admin.admin_dashboard', compact('cells', 'stats'));
    }

    // JSON — cell grid data  GET /admin/cells/data
    public function data()
    {
        $cells = Cell::orderBy('block')->orderBy('block_number')->get([
            'cell_id', 'block', 'block_number', 'type', 'capacity', 'occupancy', 'status',
        ]);

        return response()->json([
            'cells' => $cells,
            'stats' => $this->buildStats(),
        ]);
    }

    // JSON — next block letter  GET /admin/cells/next-block
    public function nextBlock()
    {
        return response()->json(['block' => Cell::nextBlock()]);
    }

    // STORE  POST /admin/cells  (JSON)
    public function store(Request $request)
    {
        $request->validate([
            'count'    => ['required', 'integer', 'min:1', 'max:50'],
            'type'     => ['required', Rule::in(['Luxury', 'Standard', 'Dormitory', 'Solitary'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $block   = Cell::nextBlock();
        $created = [];

        for ($i = 1; $i <= $request->count; $i++) {
            $created[] = Cell::create([
                'cell_id'      => "{$block}-{$i}",
                'block'        => $block,
                'block_number' => $i,
                'type'         => $request->type,
                'capacity'     => $request->capacity,
                'occupancy'    => 0,
                'status'       => 'available',
            ]);
        }

        return response()->json([
            'message' => count($created) . " cell(s) added as Block {$block}.",
            'block'   => $block,
            'cells'   => $created,
        ], 201);
    }

    // EDIT  GET /admin/cells/{cell}/edit
    public function edit(Cell $cell)
    {
        return view('admin.cells.edit', compact('cell'));
    }

    // UPDATE  PUT /admin/cells/{cell}
    public function update(Request $request, Cell $cell)
    {
        $request->validate([
            'type'     => ['required', Rule::in(['Luxury', 'Standard', 'Dormitory', 'Solitary'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'status'   => ['required', Rule::in(['available', 'full', 'maintenance', 'condemned'])],
        ]);

        if ($request->capacity < $cell->occupancy) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'capacity' => [
                        "Capacity cannot be less than current occupancy ({$cell->occupancy}). "
                        . "Transfer or release inmates first."
                    ],
                ],
            ], 422);
        }

        $cell->update([
            'type'     => $request->type,
            'capacity' => $request->capacity,
            'status'   => $request->status,
        ]);

        if (!in_array($request->status, ['maintenance', 'condemned'])) {
            $cell->syncStatus();
        }

        return redirect()->route('admin.cells.index')
            ->with('success', "Cell {$cell->cell_id} updated successfully.");
    }

    // DESTROY  DELETE /admin/cells/{cell}
    public function destroy(Cell $cell)
    {
        if ($cell->occupancy > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete Cell {$cell->cell_id} — it still has {$cell->occupancy} occupant(s).",
            ]);
        }
        $cell->delete();
        return redirect()->route('admin.cells.index')
            ->with('success', "Cell {$cell->cell_id} has been removed.");
    }

    // TOGGLE MAINTENANCE  PATCH /admin/cells/{cell}/maintenance
    public function toggleMaintenance(Cell $cell)
    {
        if ($cell->status === 'maintenance') {
            $cell->syncStatus();
        } else {
            $cell->status = 'maintenance';
            $cell->save();
        }
        return back()->with('success', "Cell {$cell->cell_id} status updated.");
    }

    // ── Private helpers ───────────────────────────────────────────
    private function buildStats(): array
    {
        return [
            'total'       => Cell::count(),
            'available'   => Cell::where('status', 'available')->count(),
            'full'        => Cell::where('status', 'full')->count(),
            'maintenance' => Cell::where('status', 'maintenance')->count(),
        ];
    }
}