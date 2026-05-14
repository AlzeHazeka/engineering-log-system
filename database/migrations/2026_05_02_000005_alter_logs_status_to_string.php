<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildForSqlite();
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `logs` MODIFY COLUMN `status` VARCHAR(255) NULL DEFAULT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            // Laravel enum on Postgres uses a CHECK constraint: drop it to allow free-form string values.
            DB::statement('ALTER TABLE logs DROP CONSTRAINT IF EXISTS logs_status_check');
            DB::statement('ALTER TABLE logs ALTER COLUMN status TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE logs ALTER COLUMN status DROP NOT NULL');
            return;
        }

        // Fallback: try schema builder.
        Schema::table('logs', function (Blueprint $table) {
            $table->string('status')->nullable();
        });
    }

    public function down(): void
    {
        // Best-effort rollback: keep as string to avoid data loss.
    }

    private function rebuildForSqlite(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('logs', 'logs_old');

        Schema::create('logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('system_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('feature_id')
                ->nullable()
                ->constrained('features')
                ->nullOnDelete();

            // Deprecated: prefer pivot table log_references
            $table->foreignId('reference_log_id')
                ->nullable()
                ->constrained('logs')
                ->nullOnDelete();

            $table->enum('type', [
                'progress',
                'bug',
                'fix',
                'deployment',
                'maintenance',
                'decision',
                'idea',
            ])->default('progress');

            $table->enum('impact', [
                'low',
                'medium',
                'high',
                'critical',
            ])->nullable();

            $table->string('status')->nullable();

            $table->string('title');
            $table->longText('description');

            $table->timestamp('logged_at')->index();

            $table->timestamps();

            $table->index(['system_id', 'type']);
            $table->index(['system_id', 'impact']);
            $table->index(['system_id', 'feature_id']);
            $table->index('feature_id');
            $table->index('reference_log_id');
        });

        DB::statement("
            INSERT INTO logs (
                id, system_id, feature_id, reference_log_id,
                type, impact, status,
                title, description, logged_at, created_at, updated_at
            )
            SELECT
                id, system_id, feature_id, reference_log_id,
                type, impact, status,
                title, description, logged_at, created_at, updated_at
            FROM logs_old
        ");

        Schema::drop('logs_old');
        Schema::enableForeignKeyConstraints();
    }
};
