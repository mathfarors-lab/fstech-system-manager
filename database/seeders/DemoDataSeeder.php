<?php

namespace Database\Seeders;

use App\Models\Build;
use App\Models\OperationLog;
use App\Models\System;
use App\Models\Task;
use App\Models\User;
use App\Models\Version;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@fstech.com')->first();
        $developer = User::where('email', 'developer@fstech.com')->first();

        $systems = collect([
            [
                'name' => 'Poss-System',
                'description' => 'Point of sale system with inventory, branches, online orders and KHQR payments.',
                'stack' => 'laravel',
                'folder_path' => 'D:\\SYSTEM\\Poss-System',
                'status' => 'active',
                'environment' => 'production',
            ],
            [
                'name' => 'School System',
                'description' => 'School management: students, enrollments, grades, fees, dormitories.',
                'stack' => 'laravel',
                'folder_path' => 'D:\\school-system',
                'status' => 'active',
                'environment' => 'production',
            ],
            [
                'name' => 'CamQuran',
                'description' => 'Quran learning app with mobile frontend and backend services.',
                'stack' => 'flutter',
                'folder_path' => 'D:\\CamQuran',
                'status' => 'maintenance',
                'environment' => 'staging',
            ],
            [
                'name' => 'ChiliPosApp',
                'description' => '.NET Blazor WASM point of sale application.',
                'stack' => 'dotnet',
                'folder_path' => 'D:\\.Net System\\ChiliPosApp',
                'status' => 'planning',
                'environment' => 'development',
            ],
            [
                'name' => 'News CMS',
                'description' => 'News platform with market analysis and paper trading.',
                'stack' => 'laravel',
                'folder_path' => 'D:\\Website\\News\\news-cms',
                'status' => 'active',
                'environment' => 'development',
            ],
        ])->map(function ($data) use ($admin) {
            $system = System::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                $data + [
                    'owner_id' => $admin->id,
                    'repo_url' => null,
                    'notes' => null,
                ]
            );

            return $system;
        });

        $poss = $systems[0];
        $school = $systems[1];

        $v1 = Version::create([
            'system_id' => $poss->id,
            'version' => '1.8.0',
            'release_date' => now()->subDays(30),
            'changelog' => "Added product bundles\nOnline order workflow\nOutstanding goods tracking",
        ]);

        $v2 = Version::create([
            'system_id' => $poss->id,
            'version' => '1.9.0',
            'release_date' => now()->subDays(3),
            'changelog' => "ACLEDA KHQR verification\nInvoice correction approval chain",
        ]);

        Build::create([
            'system_id' => $poss->id,
            'version_id' => $v1->id,
            'version_label' => '1.8.0',
            'environment' => 'production',
            'status' => 'deployed',
            'built_by_id' => $developer->id,
            'built_at' => now()->subDays(29),
            'deployed_at' => now()->subDays(28),
            'notes' => 'Initial production deployment of 1.8.0',
        ]);

        Build::create([
            'system_id' => $poss->id,
            'version_id' => $v2->id,
            'version_label' => '1.9.0',
            'environment' => 'staging',
            'status' => 'building',
            'built_by_id' => $developer->id,
            'built_at' => now(),
            'notes' => 'Preparing staging release',
        ]);

        Task::create([
            'system_id' => $poss->id,
            'title' => 'Test KHQR refund flow',
            'description' => 'Verify refund flow against ACLEDA sandbox.',
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $admin->id,
            'created_by_id' => $admin->id,
            'due_date' => now()->addDays(2),
        ]);

        Task::create([
            'system_id' => $school->id,
            'title' => 'Import 2026 enrollment data',
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => $developer->id,
            'created_by_id' => $admin->id,
            'due_date' => now()->addDays(5),
        ]);

        OperationLog::create([
            'system_id' => $poss->id,
            'type' => 'backup',
            'title' => 'Weekly database backup',
            'details' => 'Full mysqldump of production database.',
            'performed_by_id' => $developer->id,
            'performed_at' => now()->subDays(1),
        ]);
    }
}
