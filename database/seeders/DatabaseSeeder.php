<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if ((bool) env('SEED_FROM_SNAPSHOT', false)) {
            $this->call(CurrentDatabaseSnapshotSeeder::class);
            return;
        }

        $organization = Organization::firstOrCreate(
            ['name' => 'Demo Organization'],
            ['settings' => ['notifications' => true]]
        );

        $seedUsers = [
            [
                'name' => 'Platform Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Hiring Recruiter',
                'email' => 'recruiter@example.com',
                'role' => 'recruiter',
            ],
            [
                'name' => 'Content Author',
                'email' => 'author@example.com',
                'role' => 'author',
            ],
        ];

        foreach ($seedUsers as $seedUser) {
            User::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'email' => $seedUser['email'],
                ],
                [
                    'name' => $seedUser['name'],
                    'role' => $seedUser['role'],
                    'is_active' => true,
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
