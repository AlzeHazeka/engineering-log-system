<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Logs: add resolved_at (SLA / KPI)
        if (Schema::hasTable('logs') && !Schema::hasColumn('logs', 'resolved_at')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->timestamp('resolved_at')->nullable()->after('logged_at');
            });
        }

        // Features: ensure completed_at supports time (timestamp) for KPI.
        if (!Schema::hasTable('features') || !Schema::hasColumn('features', 'completed_at')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $info = DB::select("PRAGMA table_info('features')");
            $completedAtType = null;
            foreach ($info as $col) {
                if (($col->name ?? null) === 'completed_at') {
                    $completedAtType = strtolower((string) ($col->type ?? ''));
                    break;
                }
            }

            // Rebuild only when the column is not already a timestamp/datetime.
            if ($completedAtType && !str_contains($completedAtType, 'timestamp') && !str_contains($completedAtType, 'datetime')) {
                DB::statement('PRAGMA foreign_keys=OFF');

                Schema::rename('features', 'features__old');

                Schema::create('features', function (Blueprint $table) {
                    $table->id();

                    $table->foreignId('system_id')
                        ->constrained()
                        ->cascadeOnDelete();

                    $table->string('title');
                    $table->text('description')->nullable();

                    $table->enum('status', [
                        'planned',
                        'in_progress',
                        'done',
                        'on_hold',
                    ])->default('planned');

                    $table->unsignedTinyInteger('progress')->default(0);

                    $table->enum('category', [
                        'feature',
                        'improvement',
                        'maintenance',
                    ])->default('feature');

                    $table->date('start_date')->nullable();
                    $table->date('due_date')->nullable();
                    $table->timestamp('completed_at')->nullable();

                    $table->string('assigned_team')->nullable();

                    $table->timestamps();

                    $table->index(['system_id', 'status']);
                    $table->index('due_date');
                });

                DB::table('features')->insertUsing(
                    [
                        'id',
                        'system_id',
                        'title',
                        'description',
                        'status',
                        'progress',
                        'category',
                        'start_date',
                        'due_date',
                        'completed_at',
                        'assigned_team',
                        'created_at',
                        'updated_at',
                    ],
                    DB::table('features__old')->selectRaw("
                        id,
                        system_id,
                        title,
                        description,
                        status,
                        progress,
                        category,
                        start_date,
                        due_date,
                        completed_at,
                        assigned_team,
                        created_at,
                        updated_at
                    ")
                );

                Schema::drop('features__old');

                DB::statement('PRAGMA foreign_keys=ON');
            }

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE features MODIFY completed_at TIMESTAMP NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE features ALTER COLUMN completed_at TYPE timestamp USING completed_at::timestamp');
            return;
        }

        // Fallback: attempt to change via schema builder (may require platform support).
        Schema::table('features', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('logs') && Schema::hasColumn('logs', 'resolved_at')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->dropColumn('resolved_at');
            });
        }

        // We intentionally don't downgrade `features.completed_at` type.
    }
};

