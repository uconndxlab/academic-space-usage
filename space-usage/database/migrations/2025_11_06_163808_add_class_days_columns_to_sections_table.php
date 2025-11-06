<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('sunday')->default(false)->after('days');
            $table->boolean('monday')->default(false)->after('sunday');
            $table->boolean('tuesday')->default(false)->after('monday');
            $table->boolean('wednesday')->default(false)->after('tuesday');
            $table->boolean('thursday')->default(false)->after('wednesday');
            $table->boolean('friday')->default(false)->after('thursday');
            $table->boolean('saturday')->default(false)->after('friday');
            $table->integer('total_class_days')->default(0)->after('saturday');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'total_class_days']);
        });
    }
};
