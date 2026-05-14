<?php

namespace Database\Seeders;

use App\Models\System;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $systems = [
            [
                'name' => 'BEO Sales',
                'kind' => 'app',
                'status' => 'active',
                'stage' => 'production',
                'description' => 'Sales operations and transaction tracking (production).',
                'repository_url' => null,
                'released_at' => null,
            ],
            [
                'name' => 'Odoo HR',
                'kind' => 'app',
                'status' => 'active',
                'stage' => 'development',
                'description' => 'HR workflows powered by Odoo (in development).',
                'repository_url' => null,
                'released_at' => null,
            ],
            [
                'name' => 'Guest Profile',
                'kind' => 'app',
                'status' => 'active',
                'stage' => 'development',
                'description' => 'Guest identity/profile service (in development).',
                'repository_url' => null,
                'released_at' => null,
            ],
            [
                'name' => 'VPS',
                'kind' => 'server',
                'status' => 'active',
                'stage' => 'production',
                'description' => 'Infrastructure: VPS server environment.',
                'repository_url' => null,
                'released_at' => null,
            ],
            [
                'name' => 'Web Hosting',
                'kind' => 'server',
                'status' => 'maintenance',
                'stage' => 'maintenance',
                'description' => 'Infrastructure: shared hosting / web hosting environment.',
                'repository_url' => null,
                'released_at' => null,
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
