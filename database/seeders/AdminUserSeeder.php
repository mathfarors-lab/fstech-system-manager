<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@fstech.com'],
            [
                'name' => 'FS Tech Admin',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'developer@fstech.com'],
            [
                'name' => 'Demo Developer',
                'password' => 'password',
                'role' => 'developer',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'viewer@fstech.com'],
            [
                'name' => 'Demo Viewer',
                'password' => 'password',
                'role' => 'viewer',
                'email_verified_at' => now(),
            ]
        );
    }
}
