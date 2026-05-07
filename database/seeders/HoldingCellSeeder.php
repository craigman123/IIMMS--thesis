<?php

namespace Database\Seeders;

use App\Models\Cell;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HoldingCellSeeder extends Seeder
{
    /**
     * Seed the default "Holding Cell" record.
     *
     * Inmates with no assigned cell are automatically placed here
     * by InmateController@store.
     *
     * Usage:
     *   php artisan db:seed --class=HoldingCellSeeder
     *
     * The seeder will interactively ask for a capacity.
     * Press Enter (leave blank) to set capacity to 0, which the
     * application treats as unlimited.
     */
    public function run(): void
    {
        // ── Guard — skip if a Holding Cell already exists ─────────────
        if (DB::table('cells')->where('type', 'Holding Cell')->exists()) {
            $this->command->warn('Holding Cell already exists — skipping.');
            return;
        }

        // ── Ask for capacity ──────────────────────────────────────────
        $this->command->info('');
        $this->command->info('┌─────────────────────────────────────────┐');
        $this->command->info('│         Holding Cell Setup              │');
        $this->command->info('└─────────────────────────────────────────┘');
        $this->command->info('');

        $input = $this->command->ask(
            'Enter the maximum capacity for the Holding Cell (press Enter to set as Unlimited)'
        );

        // Blank / non-numeric input → 0 (treated as unlimited by the app)
        if ($input === null || trim($input) === '' || ! ctype_digit(trim($input))) {
            $capacity = 0;
        } else {
            $capacity = (int) trim($input);
        }

        $label = $capacity === 0 ? 'Unlimited' : $capacity;

        // ── Confirm before inserting ──────────────────────────────────
        $confirmed = $this->command->confirm(
            "Create Holding Cell with capacity: {$label}?",
            true   // default answer is yes
        );

        if (! $confirmed) {
            $this->command->warn('Seeder cancelled — no record created.');
            return;
        }

        // ── Insert ────────────────────────────────────────────────────
        Cell::create([
            'cell_id'    => 'HOLDING',     // human-readable identifier used in the UI
            'block'      => 'HD',           // first block letter
            'block_number' => 0,          // first block number
            'type'       => 'Holding Cell',
            'capacity'   => $capacity,           // 0 = unlimited
            'status'     => 'available',
            'occupancy'  => 0,

            // php artisan db:seed --class=HoldingCellSeeder
        ]);

        $this->command->info('');
        $this->command->info("✔  Holding Cell created successfully (capacity: {$label}).");
        $this->command->info('');
    }
}
