<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g. bug_resolution
            $table->string('impact'); // low|medium|high|critical
            $table->unsignedSmallInteger('days');
            $table->timestamps();

            $table->unique(['type', 'impact']);
            $table->index(['type', 'impact']);
        });

        // Seed defaults from config to avoid breaking existing behavior.
        $defaults = (array) config('sla.bug_resolution_days', []);
        $payload = [
            ['type' => 'bug_resolution', 'impact' => 'low', 'days' => (int) ($defaults['low'] ?? 5)],
            ['type' => 'bug_resolution', 'impact' => 'medium', 'days' => (int) ($defaults['medium'] ?? 3)],
            ['type' => 'bug_resolution', 'impact' => 'high', 'days' => (int) ($defaults['high'] ?? 2)],
            ['type' => 'bug_resolution', 'impact' => 'critical', 'days' => (int) ($defaults['critical'] ?? 1)],
        ];

        foreach ($payload as $row) {
            DB::table('sla_rules')->updateOrInsert(
                ['type' => $row['type'], 'impact' => $row['impact']],
                ['days' => $row['days'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};

