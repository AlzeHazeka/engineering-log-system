<?php

namespace Database\Seeders;

use App\Models\System;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $systems = [
            [
                'name' => 'Guest Profile',
                'status' => 'active',
                'description' => 'Internal guest management system.',
                'repository_url' => null,
            ],
            [
                'name' => 'Odoo HR',
                'status' => 'active',
                'description' => 'Sistem Cuti dan Izin HRD.',
                'repository_url' => null,
            ],
            [
                'name' => 'Sistem BEO',
                'status' => 'active',
                'description' => 'Sistem Banquet Event Order milik Sales.',
                'repository_url' => null,
            ],
            [
                'name' => 'AI Agent Kamuspedia Atsiri',
                'status' => 'active',
                'description' => 'Sistem AI Agent untuk Telegram.',
                'repository_url' => null,
            ],
            [
                'name' => 'Sistem VPS',
                'status' => 'active',
                'description' => 'Virtual Private Server untuk Sistem Rumah Atsiri.',
                'repository_url' => null,
            ],
        ];

        foreach ($systems as $system) {
            System::updateOrCreate(
                ['slug' => Str::slug($system['name'])],
                array_merge($system, [
                    'slug' => Str::slug($system['name']),
                ])
            );
        }
    }
}