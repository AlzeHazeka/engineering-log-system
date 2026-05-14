<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_references', function (Blueprint $table) {
            $table->id();

            $table->foreignId('log_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('reference_log_id')
                ->constrained('logs')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['log_id', 'reference_log_id']);
            $table->index(['reference_log_id', 'log_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_references');
    }
};

