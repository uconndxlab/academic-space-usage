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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code');
            $table->string('class_descr');
            $table->string('catalog_number');
            $table->integer('wsch_max')->nullable();
            $table->text('class_duration_weekly');
            $table->integer('duration_minutes');
            // start_time, end_time, days
            $table->string('division');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            $table->integer('total_wsch')->nullable();
            $table->integer('total_capacity')->nullable();
            $table->integer('total_enrollment')->nullable();
            $table->integer('wsch_benchmark')->nullable();
            $table->integer('rooms_needed')->nullable();
            $table->integer('rooms_used')->nullable();
            $table->integer('delta')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
