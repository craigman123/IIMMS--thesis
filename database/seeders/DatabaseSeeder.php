<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = 'adminw@system.com';
        $badge = '12345';

        $exists = User::where('email', $email)
            ->orWhere('badge_number', $badge)
            ->exists();

        if (!$exists) {
            User::create([
                'name' => 'Admin',
                'email' => $email,
                'badge_number' => $badge,
                'role' => 'admin',
                'system_access' => 'active',
                'password' => Hash::make('12345'),
            ]);
        }
    }

}
