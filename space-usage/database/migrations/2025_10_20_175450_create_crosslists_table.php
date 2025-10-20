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
        Schema::create('crosslists', function (Blueprint $table) {
            $table->id();
            $table->string('crosslist_id')->index(); // Groups related sections
            $table->string('crosslist_descr')->nullable();
            $table->string('crosslist_combination_type')->nullable();
            $table->integer('crosslisted_enrollment_cap')->nullable();
            $table->integer('crosslisted_enrollment_total')->nullable();
            $table->boolean('crosslist_dup')->default(false);
            $table->timestamps();
        });
        
        // Add crosslist relationship to sections
        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('crosslist_id')->nullable()->after('crosslisted_course_flag')->constrained('crosslists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['crosslist_id']);
            $table->dropColumn('crosslist_id');
        });
        
        Schema::dropIfExists('crosslists');
    }
};
