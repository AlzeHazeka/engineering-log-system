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

            $table->enum('status', [
                'active',
                'maintenance',
                'paused',
                'deprecated'
            ])->default('active');

            $table->text('description')->nullable();
            $table->string('repository_url')->nullable();

            $table->timestamps();

            // Index penting untuk filtering cepat
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
