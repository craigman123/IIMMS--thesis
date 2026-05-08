<?php

namespace App\Http\Controllers;

use App\Models\Cell;
use App\Models\Inmate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CellController extends Controller
{
    public function index(Request $request)
    {
        $cells = Cell::orderBy('block')->orderBy('block_number')->get();
        $stats = $this->buildStats();

        return view('admin.admin_dashboard', compact('cells', 'stats'));
    }

    public function data()
    {
        $cells = Cell::orderBy('block')->orderBy('block_number')->get([
            'id', 'cell_id', 'block', 'block_number', 'type', 'capacity', 'occupancy', 'status',
        ]);

        return response()->json([
            'cells' => $cells,
            'stats' => $this->buildStats(),
        ]);
    }

    public function nextBlock()
    {
        return response()->json(['block' => Cell::nextBlock()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'count'    => ['required', 'integer', 'min:1', 'max:50'],
            'type'     => ['required', Rule::in(['Luxury', 'Standard', 'Dormitory', 'Solitary'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $block = Cell::nextBlock();
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

    public function updateBlock(Request $request, string $block)
    {
        $cells = Cell::where('block', strtoupper($block))
            ->orderBy('block_number')
            ->get();

        if ($cells->isEmpty()) {
            return response()->json([
                'message' => 'Block not found.',
            ], 404);
        }

        if ($cells->contains(fn (Cell $cell) => $this->isHoldingCell($cell))) {
            return response()->json([
                'message' => 'The Holding Cell is system-managed and cannot be edited.',
            ], 403);
        }

        $request->validate([
            'type'      => ['required', Rule::in(['Luxury', 'Standard', 'Dormitory', 'Solitary'])],
            'capacity'  => ['required', 'integer', 'min:1', 'max:50'],
            'add_count' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        $capacity = (int) $request->capacity;
        $maxOccupancy = (int) $cells->max('occupancy');

        if ($capacity < $maxOccupancy) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'capacity' => [
                        "Capacity cannot be lower than the highest occupied cell in Block {$block} ({$maxOccupancy}).",
                    ],
                ],
            ], 422);
        }

        foreach ($cells as $cell) {
            $cell->update([
                'type'     => $request->type,
                'capacity' => $capacity,
            ]);

            $cell->syncStatus();
        }

        $added = collect();
        $addCount = (int) $request->input('add_count', 0);

        if ($addCount > 0) {
            $nextNumber = ((int) $cells->max('block_number')) + 1;

            for ($i = 0; $i < $addCount; $i++) {
                $number = $nextNumber + $i;
                $added->push(Cell::create([
                    'cell_id'      => strtoupper($block) . "-{$number}",
                    'block'        => strtoupper($block),
                    'block_number' => $number,
                    'type'         => $request->type,
                    'capacity'     => $capacity,
                    'occupancy'    => 0,
                    'status'       => 'available',
                ]));
            }
        }

        return response()->json([
            'message' => "Block {$block} updated successfully.",
            'added'   => $added->count(),
        ]);
    }

    public function destroyBlock(string $block)
    {
        $cells = Cell::where('block', strtoupper($block))
            ->orderBy('block_number')
            ->get();

        if ($cells->isEmpty()) {
            return response()->json([
                'message' => 'Block not found.',
            ], 404);
        }

        if ($cells->contains(fn (Cell $cell) => $this->isHoldingCell($cell))) {
            return response()->json([
                'message' => 'The Holding Cell is system-managed and cannot be deleted.',
            ], 403);
        }

        $occupied = $cells->first(fn (Cell $cell) => (int) $cell->occupancy > 0);

        if ($occupied) {
            return response()->json([
                'message' => "Cannot delete Block {$block} while cell {$occupied->cell_id} still has inmates.",
            ], 422);
        }

        foreach ($cells as $cell) {
            $cell->delete();
        }

        return response()->json([
            'message' => "Block {$block} deleted successfully.",
        ]);
    }

    public function update(Request $request, Cell $cell)
    {
        if ($this->isHoldingCell($cell)) {
            return response()->json([
                'message' => 'The Holding Cell is system-managed and cannot be edited.',
            ], 403);
        }

        $request->validate([
            'type'     => ['required', Rule::in(['Luxury', 'Standard', 'Dormitory', 'Solitary'])],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'status'   => ['required', Rule::in(['available', 'full', 'maintenance', 'condemned'])],
        ]);

        if ((int) $request->capacity < (int) $cell->occupancy) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'capacity' => [
                        "Capacity cannot be less than current occupancy ({$cell->occupancy}). Transfer or release inmates first.",
                    ],
                ],
            ], 422);
        }

        $cell->update([
            'type'     => $request->type,
            'capacity' => (int) $request->capacity,
            'status'   => $request->status,
        ]);

        if (!in_array($request->status, ['maintenance', 'condemned'], true)) {
            $cell->syncStatus();
        }

        return response()->json([
            'message' => "Cell {$cell->cell_id} updated successfully.",
            'cell'    => $cell->fresh(['inmates']),
        ]);
    }

    public function inmates(int $id)
    {
        $cell = Cell::findOrFail($id);

        $inmates = Inmate::where('cell_id', $cell->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (Inmate $inmate) {
                return [
                    'id'        => $inmate->id,
                    'name'      => trim($inmate->last_name . ', ' . $inmate->first_name . ' ' . $inmate->middle_name),
                    'status'    => $inmate->status,
                    'inmate_id' => $inmate->id,
                    'crime'     => optional($inmate->crimes()->first())->crime_name,
                ];
            })
            ->values();

        return response()->json([
            'inmates' => $inmates,
        ]);
    }

    private function buildStats(): array
    {
        return [
            'total'       => Cell::count(),
            'available'   => Cell::where('status', 'available')->count(),
            'full'        => Cell::where('status', 'full')->count(),
            'maintenance' => Cell::where('status', 'maintenance')->count(),
        ];
    }

    private function isHoldingCell(Cell $cell): bool
    {
        return strtolower(trim((string) $cell->type)) === 'holding cell';
    }
}
