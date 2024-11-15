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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->text('section_number');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->integer('enrol_cap');
            $table->text('component_code');
            $table->integer('day10_enrol');

            $table->time('start_time');
            $table->time('end_time');
            $table->string('days');

            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            
            $table->json('enrollments_by_dept')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
