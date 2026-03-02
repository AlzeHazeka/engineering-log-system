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

            $table->enum('type', [
                'change',
                'error',
                'fix',
                'maintenance',
                'decision',
                'deployment',
                'idea'
            ]);

            $table->enum('impact', [
                'low',
                'medium',
                'high',
                'critical'
            ])->default('low');

            $table->string('title');
            $table->longText('description');

            $table->timestamp('logged_at')->index();

            $table->timestamps();

            // Composite index untuk filter cepat
            $table->index(['system_id', 'type']);
            $table->index(['system_id', 'impact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};