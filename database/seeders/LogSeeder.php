<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Log;
use App\Models\System;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    public function run(): void
    {
        // Intentionally disabled:
        // We only seed Systems for now (no Log dummy data).
    }
}
