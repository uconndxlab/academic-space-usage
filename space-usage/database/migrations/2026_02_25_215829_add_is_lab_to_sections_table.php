<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('is_lab')->default(false)->after('room_id');
            $table->index('is_lab');
        });

        DB::table('sections')
            ->whereIn('room_id', DB::table('rooms')->where('sa_facility_type', 'like', '%Laboratory%')->pluck('id'))
            ->update(['is_lab' => true]);
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex(['is_lab']);
            $table->dropColumn('is_lab');
        });
    }
};
