<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'ADMIN',
            'dept' => 'MULTI',
            'initials' => 'ADM',
            'active' => true,
        ]);

        // COCR Lead
        User::create([
            'name' => 'COCR Lead',
            'email' => 'cocr.lead@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'LEAD',
            'dept' => 'COCR',
            'initials' => 'CL',
            'active' => true,
        ]);

        // COCR Worker
        User::create([
            'name' => 'John Smith',
            'email' => 'john.smith@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'WORKER',
            'dept' => 'COCR',
            'initials' => 'JS',
            'active' => true,
        ]);

        // SOLIDEX Lead
        User::create([
            'name' => 'SOLIDEX Lead',
            'email' => 'solidex.lead@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'LEAD',
            'dept' => 'SOLIDEX',
            'initials' => 'SL',
            'active' => true,
        ]);

        // SOLIDEX Worker
        User::create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'WORKER',
            'dept' => 'SOLIDEX',
            'initials' => 'JD',
            'active' => true,
        ]);

        // 3D Print Lead
        User::create([
            'name' => 'Print Lead',
            'email' => 'print.lead@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'LEAD',
            'dept' => 'PRINT',
            'initials' => 'PL',
            'active' => true,
        ]);

        // 3D Print Worker
        User::create([
            'name' => 'Mike Johnson',
            'email' => 'mike.johnson@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'WORKER',
            'dept' => 'PRINT',
            'initials' => 'MJ',
            'active' => true,
        ]);

        // Front Desk
        User::create([
            'name' => 'Front Desk',
            'email' => 'frontdesk@cadcam.local',
            'password' => Hash::make('password123'),
            'role' => 'WORKER',
            'dept' => 'FD',
            'initials' => 'FD',
            'active' => true,
        ]);
    }
}
