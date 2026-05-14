<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy support:
        // `logs.reference_log_id` is deprecated and no longer used by the app code.
        // This migration backfills `log_references` so old data remains visible.

        if (!DB::getSchemaBuilder()->hasTable('logs') || !DB::getSchemaBuilder()->hasTable('log_references')) {
            return;
        }

        if (!DB::getSchemaBuilder()->hasColumn('logs', 'reference_log_id')) {
            return;
        }

        $now = now();

        $payload = DB::table('logs')
            ->whereNotNull('reference_log_id')
            ->select(['id as log_id', 'reference_log_id'])
            ->get()
            ->map(fn ($row) => [
                'log_id' => $row->log_id,
                'reference_log_id' => $row->reference_log_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if (count($payload) === 0) {
            return;
        }

        // `log_references` has a unique index on (log_id, reference_log_id).
        DB::table('log_references')->upsert(
            $payload,
            ['log_id', 'reference_log_id'],
            ['updated_at']
        );
    }

    public function down(): void
    {
        // No-op: we do not want to remove references that might have been created later.
    }
};
