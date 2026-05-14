<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            if (!Schema::hasColumn('systems', 'kind')) {
                // System kind (app/web vs server/infrastructure)
                $table->string('kind')->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            if (Schema::hasColumn('systems', 'kind')) {
                $table->dropColumn('kind');
            }
        });
    }
};

