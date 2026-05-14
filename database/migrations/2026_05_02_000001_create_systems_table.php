<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // Operational status (runtime condition)
            $table->enum('status', [
                'active',
                'maintenance',
                'paused',
                'deprecated',
            ])->default('active');

            // Lifecycle stage (manual, nullable for backward compatibility)
            $table->string('stage')->nullable();

            // Release marker (nullable)
            $table->date('released_at')->nullable();

            $table->text('description')->nullable();
            $table->string('repository_url')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};

