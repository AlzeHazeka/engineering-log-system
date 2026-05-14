<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->date('completed_at')->nullable();

            $table->string('assigned_team')->nullable();

            $table->timestamps();

            $table->index(['system_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};

