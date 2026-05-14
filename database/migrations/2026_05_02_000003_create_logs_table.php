<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('system_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('feature_id')
                ->nullable()
                ->constrained('features')
                ->nullOnDelete();

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

            $table->enum('status', [
                'open',
                'resolved',
                'ignored',
            ])->nullable();

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
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

